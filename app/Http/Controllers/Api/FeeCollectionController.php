<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeeCollection;
use App\Models\FeeReceipt;
use App\Models\AcademicYear;
use App\Services\FeeCalculationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeeCollectionController extends Controller
{
    protected FeeCalculationService $calcService;

    public function __construct(FeeCalculationService $calcService)
    {
        $this->calcService = $calcService;
    }

    /**
     * Get dues and active discounts for a student.
     */
    public function getStudentDues(Request $request, $studentId): JsonResponse
    {
        $this->authorize('fee_collection.view');

        $user = $request->user();
        $schoolId = $user->isSuperAdmin() ? $request->input('school_id') : $user->school_id;

        if (!$schoolId) {
            return response()->json(['message' => 'School ID is required.'], 422);
        }

        $academicYearId = $request->input('academic_year_id');
        if (!$academicYearId) {
            $activeYear = AcademicYear::where('school_id', $schoolId)
                ->where('is_current', true)
                ->where('is_delete', 0)
                ->first();
            if ($activeYear) {
                $academicYearId = $activeYear->id;
            }
        }

        if (!$academicYearId) {
            return response()->json(['message' => 'Academic Year is required.'], 422);
        }

        $dues = $this->calcService->getStudentFeeDues((int) $studentId, (int) $academicYearId);
        $discounts = $this->calcService->getStudentActiveDiscounts((int) $studentId, (int) $academicYearId);

        return response()->json([
            'dues' => $dues,
            'available_discounts' => $discounts
        ]);
    }

    /**
     * Collect a fee payment and generate a receipt.
     */
    public function collect(Request $request): JsonResponse
    {
        $this->authorize('fee_collection.create');

        $user = $request->user();
        $schoolId = $user->isSuperAdmin() ? $request->input('school_id') : $user->school_id;

        if (!$schoolId) {
            return response()->json(['message' => 'School ID is required.'], 422);
        }

        $academicYearId = $request->input('academic_year_id');
        $academicYear = AcademicYear::find($academicYearId);
        if (!$academicYear || !$academicYear->is_current) {
            return response()->json([
                'message' => 'This academic session is locked. Modifications are only allowed in the active session.'
            ], 422);
        }

        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'student_id' => 'required|exists:students,id',
            'installment_id' => 'required|exists:fee_installments,id',
            'amount_paid' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_id' => 'nullable|exists:fee_discounts,id',
            'fine_amount' => 'nullable|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:Cash,UPI,Cheque,Bank Transfer',
            'transaction_reference' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
        ]);

        $studentId = (int) $request->input('student_id');
        $academicYearId = (int) $request->input('academic_year_id');
        $installmentId = (int) $request->input('installment_id');

        $amountPaid = (float) $request->input('amount_paid');
        $discountAmount = (float) $request->input('discount_amount', 0);
        $fineAmount = (float) $request->input('fine_amount', 0);

        // Fetch current dues to determine amount_due
        $duesData = $this->calcService->getStudentFeeDues($studentId, $academicYearId);
        
        $instDue = null;
        foreach ($duesData['installments'] as $inst) {
            if ($inst['id'] === $installmentId) {
                $instDue = $inst;
                break;
            }
        }

        if (!$instDue) {
            return response()->json(['message' => 'The selected installment is not active or assigned to this student.'], 422);
        }

        if ($amountPaid + $discountAmount > $instDue['remaining_due'] + 0.05) { // allow a small rounding tolerance
            return response()->json([
                'message' => 'The payment + discount amount exceeds the remaining installment dues of ' . $instDue['remaining_due']
            ], 422);
        }

        $collection = DB::transaction(function () use ($request, $schoolId, $user, $instDue) {
            $paymentDate = Carbon::parse($request->input('payment_date'));
            
            // 1. Save collection
            $col = FeeCollection::create([
                'school_id' => $schoolId,
                'academic_year_id' => $request->input('academic_year_id'),
                'student_id' => $request->input('student_id'),
                'installment_id' => $request->input('installment_id'),
                'amount_due' => $instDue['remaining_due'],
                'amount_paid' => $request->input('amount_paid'),
                'discount_amount' => $request->input('discount_amount', 0.00),
                'fine_amount' => $request->input('fine_amount', 0.00),
                'payment_date' => $paymentDate,
                'payment_method' => $request->input('payment_method'),
                'transaction_reference' => $request->input('transaction_reference'),
                'remarks' => $request->input('remarks'),
                'collected_by' => $user->id,
            ]);

            // 2. Increment discount used_count if a discount is applied
            $discountAmount = (float) $request->input('discount_amount', 0.00);
            $discountId = $request->input('discount_id');
            if ($discountAmount > 0) {
                $discountQuery = \App\Models\FeeDiscount::where('student_id', $request->input('student_id'))
                    ->where('academic_year_id', $request->input('academic_year_id'))
                    ->where('status', 'active')
                    ->where('is_delete', false)
                    ->whereColumn('used_count', '<', 'max_uses');
                
                if ($discountId) {
                    $activeDiscount = $discountQuery->where('id', $discountId)->first();
                } else {
                    $activeDiscount = $discountQuery->first();
                }
                
                if ($activeDiscount) {
                    $activeDiscount->increment('used_count');
                }
            }

            // 3. Generate Receipt
            $receiptNum = 'REC-' . $schoolId . '-' . str_pad($col->id, 6, '0', STR_PAD_LEFT);
            FeeReceipt::create([
                'school_id' => $schoolId,
                'receipt_number' => $receiptNum,
                'collection_id' => $col->id,
                'generated_at' => Carbon::now(),
            ]);

            return $col;
        });

        try {
            $fcmService = app(\App\Services\FcmService::class);
            $collection->load('installment');
            $installmentName = $collection->installment ? $collection->installment->name : 'Fee Installment';
            $title = "Fee Payment Received";
            $body = "A payment of " . number_format($amountPaid, 2) . " has been successfully collected for " . $installmentName . ".";
            
            $fcmService->sendToStudent(
                $studentId,
                $title,
                $body,
                [
                    'type' => 'fees',
                    'id' => $collection->id,
                ]
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("FCM Fee Notification Error: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Payment collected and receipt generated successfully',
            'collection' => $collection->load(['receipt', 'installment'])
        ], 201);
    }

    /**
     * Get payment collection history.
     */
    public function history(Request $request): JsonResponse
    {
        $this->authorize('fee_collection.view');

        $user = $request->user();
        $query = FeeCollection::with(['student', 'installment', 'collectedBy', 'receipt']);

        if (!$user->isSuperAdmin()) {
            $query->where('school_id', $user->school_id);
        } elseif ($request->filled('school_id')) {
            $query->where('school_id', $request->input('school_id'));
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->input('academic_year_id'));
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('payment_date', [$request->input('start_date'), $request->input('end_date')]);
        }

        $collections = $query->orderBy('payment_date', 'desc')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'collections' => $collections
        ]);
    }
}
