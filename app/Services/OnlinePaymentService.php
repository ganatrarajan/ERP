<?php

namespace App\Services;

use App\Models\OnlinePaymentTransaction;
use App\Models\FeeCollection;
use App\Models\FeeReceipt;
use App\Models\AcademicYear;
use App\Models\FeeInstallment;
use App\Models\Student;
use App\Models\User;
use App\Services\Payment\PaymentGatewayFactory;
use App\Services\FcmService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class OnlinePaymentService
{
    protected FeeCalculationService $calcService;
    protected FcmService $fcmService;

    public function __construct(FeeCalculationService $calcService, FcmService $fcmService)
    {
        $this->calcService = $calcService;
        $this->fcmService = $fcmService;
    }

    /**
     * Create a pending online payment transaction and prepare gateway order details.
     */
    public function createOrder(int $studentId, int $academicYearId, int $installmentId, float $amount): OnlinePaymentTransaction
    {
        $student = Student::where('id', $studentId)
            ->where('is_delete', 0)
            ->where('status', 'active')
            ->firstOrFail();
        
        $schoolId = $student->school_id;

        // Double check module is active
        if (!schoolHasModule('online-payments')) {
            throw new Exception("Online payment module is not enabled for this school.");
        }

        // Fetch remaining dues to validate input amount
        $duesData = $this->calcService->getStudentFeeDues($studentId, $academicYearId);
        $instDue = null;
        foreach ($duesData['installments'] as $inst) {
            if ($inst['id'] === $installmentId) {
                $instDue = $inst;
                break;
            }
        }

        if (!$instDue) {
            throw new Exception("The selected installment is not active or assigned to this student.");
        }

        $outstanding = (float) $instDue['outstanding_balance'];
        if ($amount <= 0 || $amount > $outstanding + 0.05) {
            throw new Exception("Payment amount exceeds outstanding dues of " . number_format($outstanding, 2));
        }

        return DB::transaction(function () use ($schoolId, $studentId, $academicYearId, $installmentId, $amount) {
            // 1. Resolve gateway driver first
            $driver = PaymentGatewayFactory::createForSchool($schoolId);

            // 2. Create transaction ledger in database
            $transaction = OnlinePaymentTransaction::create([
                'school_id' => $schoolId,
                'student_id' => $studentId,
                'installment_id' => $installmentId,
                'amount' => $amount,
                'currency' => 'INR',
                'gateway_name' => $driver->getConfig()->gateway_name,
                'status' => 'pending',
            ]);

            // 3. Create order
            $orderData = $driver->createOrder($transaction);

            // 4. Update transaction record
            $transaction->update([
                'order_id' => $orderData['order_id'],
                'gateway_response' => $orderData['gateway_response'],
            ]);

            return $transaction;
        });
    }

    /**
     * Process successful signature verification or webhook update to capture fee in ERP.
     */
    public function processSuccessfulPayment(OnlinePaymentTransaction $transaction, string $paymentId, string $signature, string $paymentMethod, array $gatewayResponse): OnlinePaymentTransaction
    {
        return DB::transaction(function () use ($transaction, $paymentId, $signature, $paymentMethod, $gatewayResponse) {
            // Lock transaction row to prevent race conditions / duplicate captures
            $transaction = OnlinePaymentTransaction::where('id', $transaction->id)
                ->lockForUpdate()
                ->first();

            if ($transaction->status === 'successful') {
                return $transaction; // Already processed
            }

            // Update transaction status
            $transaction->update([
                'status' => 'successful',
                'payment_id' => $paymentId,
                'signature' => $signature,
                'payment_method' => $paymentMethod,
                'transaction_date' => Carbon::now(),
                'gateway_response' => $gatewayResponse,
            ]);

            $studentId = $transaction->student_id;
            $installmentId = $transaction->installment_id;

            // Resolve current active year
            $activeYear = AcademicYear::where('school_id', $transaction->school_id)
                ->where('is_current', true)
                ->where('is_delete', 0)
                ->first();

            if (!$activeYear) {
                throw new Exception("Active academic session not found.");
            }

            // Calculate exact breakdown of base vs fine dues
            $duesData = $this->calcService->getStudentFeeDues($studentId, $activeYear->id);
            $instDue = null;
            foreach ($duesData['installments'] as $inst) {
                if ($inst['id'] === $installmentId) {
                    $instDue = $inst;
                    break;
                }
            }

            if (!$instDue) {
                throw new Exception("The selected installment is not active or assigned to this student.");
            }

            $amountPaid = (float) $transaction->amount;
            $fineDue = (float) $instDue['fine_due'];

            $finePaid = 0.00;
            $basePaid = 0.00;

            if ($amountPaid >= $fineDue) {
                $finePaid = $fineDue;
                $basePaid = $amountPaid - $fineDue;
            } else {
                $finePaid = $amountPaid;
                $basePaid = 0.00;
            }

            // Find an active School Admin to act as collected_by
            $adminUser = User::where('school_id', $transaction->school_id)
                ->whereHas('roles', function ($q) {
                    $q->where('name', 'School Admin');
                })
                ->where('status', 'active')
                ->first();

            if (!$adminUser) {
                $adminUser = User::where('school_id', $transaction->school_id)
                    ->where('status', 'active')
                    ->first();
            }

            $collectedBy = $adminUser ? $adminUser->id : 1;

            // 1. Save fee collection record
            $collection = FeeCollection::create([
                'school_id' => $transaction->school_id,
                'academic_year_id' => $activeYear->id,
                'student_id' => $studentId,
                'installment_id' => $installmentId,
                'amount_due' => $instDue['remaining_due'],
                'amount_paid' => $basePaid,
                'discount_amount' => 0.00,
                'fine_amount' => $finePaid,
                'payment_date' => Carbon::now(),
                'payment_method' => $paymentMethod,
                'transaction_reference' => $paymentId,
                'remarks' => 'Online payment processed via ' . $transaction->gateway_name,
                'collected_by' => $collectedBy,
            ]);

            // 2. Generate Receipt
            $receiptNum = 'REC-' . $transaction->school_id . '-' . str_pad($collection->id, 6, '0', STR_PAD_LEFT);
            FeeReceipt::create([
                'school_id' => $transaction->school_id,
                'receipt_number' => $receiptNum,
                'collection_id' => $collection->id,
                'generated_at' => Carbon::now(),
            ]);

            // 3. Link receipt to transaction
            $transaction->update([
                'receipt_no' => $receiptNum
            ]);

            // 4. Send FCM Push Notification
            try {
                $title = "Fee Payment Successful";
                $body = "Online fee payment of ₹" . number_format($amountPaid, 2) . " has been received successfully.";
                $this->fcmService->sendToStudent($studentId, $title, $body, [
                    'type' => 'fees',
                    'id' => $collection->id
                ]);

                // Also notify school admins (using FCM sendToUser)
                if ($adminUser) {
                    $adminTitle = "Online Fee Received";
                    $adminBody = "Online fee payment of ₹" . number_format($amountPaid, 2) . " received from Student ID: " . $studentId . ".";
                    $this->fcmService->sendToUser($adminUser->id, $adminTitle, $adminBody, [
                        'type' => 'online_fees',
                        'id' => $transaction->id
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("FCM push delivery failed for transaction {$transaction->id}: " . $e->getMessage());
            }

            return $transaction;
        });
    }

    /**
     * Mark a transaction as failed.
     */
    public function failTransaction(OnlinePaymentTransaction $transaction, array $gatewayResponse): void
    {
        $transaction->update([
            'status' => 'failed',
            'gateway_response' => $gatewayResponse,
        ]);
    }
}
