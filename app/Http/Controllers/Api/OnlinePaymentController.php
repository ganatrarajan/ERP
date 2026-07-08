<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Module;
use App\Models\SchoolModule;
use App\Models\PaymentGatewayConfig;
use App\Models\OnlinePaymentTransaction;
use App\Models\AcademicYear;
use App\Models\FeeCollection;
use App\Models\FeeReceipt;
use App\Services\OnlinePaymentService;
use App\Services\Payment\PaymentGatewayFactory;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class OnlinePaymentController extends Controller
{
    protected OnlinePaymentService $paymentService;

    public function __construct(OnlinePaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /* -------------------------------------------------------------------------- */
    /* SUPER ADMIN ENDPOINTS                                                      */
    /* -------------------------------------------------------------------------- */

    /**
     * Get list of schools with online module and gateway configuration statuses.
     */
    public function superAdminListSchools(Request $request): JsonResponse
    {
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $schools = School::all();
        $configs = PaymentGatewayConfig::all()->keyBy('school_id');

        $data = $schools->map(function ($school) use ($configs) {
            // Check module active status
            $isModuleEnabled = DB::table('school_modules')
                ->join('modules', 'modules.id', '=', 'school_modules.module_id')
                ->where('school_modules.school_id', $school->id)
                ->where('modules.slug', 'online-payments')
                ->where('school_modules.is_active', true)
                ->exists();

            $config = $configs->get($school->id);

            return [
                'id' => $school->id,
                'name' => $school->name,
                'school_code' => $school->school_code,
                'module_enabled' => $isModuleEnabled,
                'gateway_name' => $config ? $config->gateway_name : 'Not Configured',
                'active' => $config ? (bool) $config->active : false,
                'mode' => $config ? $config->mode : null,
                'last_updated' => $config ? $config->updated_at->toDateTimeString() : null,
            ];
        });

        return response()->json(['schools' => $data]);
    }

    /**
     * Enable or disable the Online Payment module for a school.
     */
    public function superAdminToggleModule(Request $request, School $school): JsonResponse
    {
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'enabled' => 'required|boolean'
        ]);

        $enabled = (bool) $request->input('enabled');
        $module = Module::where('slug', 'online-payments')->firstOrFail();

        // Toggle in school_modules pivot table
        DB::table('school_modules')->updateOrInsert(
            [
                'school_id' => $school->id,
                'module_id' => $module->id,
            ],
            [
                'is_active' => $enabled,
                'updated_at' => Carbon::now()
            ]
        );

        // Sync School Admin permissions depending on whether module is active
        $schoolAdminRole = \App\Models\Role::where('school_id', $school->id)
            ->where('name', 'School Admin')
            ->first();

        if ($schoolAdminRole) {
            $paymentPermissions = ['payment_gateway.manage', 'payment_gateway.view'];
            if ($enabled) {
                $schoolAdminRole->givePermissionTo($paymentPermissions);
            } else {
                $schoolAdminRole->revokePermissionTo($paymentPermissions);
            }
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }

        return response()->json([
            'message' => 'Online payment module ' . ($enabled ? 'enabled' : 'disabled') . ' successfully for school.'
        ]);
    }

    /* -------------------------------------------------------------------------- */
    /* SCHOOL ADMIN ENDPOINTS                                                     */
    /* -------------------------------------------------------------------------- */

    /**
     * Get the active gateway configuration (excluding secrets).
     */
    public function getGatewaySettings(Request $request): JsonResponse
    {
        $this->authorize('payment_gateway.manage');

        $schoolId = $request->user()->school_id;

        if (!schoolHasModule('online-payments')) {
            return response()->json(['message' => 'Online payment module is disabled by the Super Admin.'], 403);
        }

        $config = PaymentGatewayConfig::where('school_id', $schoolId)->first();

        return response()->json([
            'config' => $config ? [
                'gateway_name' => $config->gateway_name,
                'key_id' => $config->key_id,
                'mode' => $config->mode,
                'currency' => $config->currency,
                'active' => (bool)$config->active,
                'key_secret_configured' => !empty($config->key_secret),
                'webhook_secret_configured' => !empty($config->webhook_secret),
            ] : null
        ]);
    }

    /**
     * Save or update the gateway configuration.
     */
    public function saveGatewaySettings(Request $request): JsonResponse
    {
        $this->authorize('payment_gateway.manage');

        $schoolId = $request->user()->school_id;

        if (!schoolHasModule('online-payments')) {
            return response()->json(['message' => 'Online payment module is disabled by the Super Admin.'], 403);
        }

        $request->validate([
            'gateway_name' => 'required|string|max:50',
            'key_id' => 'required|string|max:100',
            'key_secret' => 'nullable|string|max:255',
            'webhook_secret' => 'nullable|string|max:255',
            'mode' => 'required|in:test,live',
            'currency' => 'required|string|max:10',
            'active' => 'required|boolean',
        ]);

        $config = PaymentGatewayConfig::firstOrNew(['school_id' => $schoolId]);
        
        $config->gateway_name = $request->input('gateway_name');
        $config->key_id = $request->input('key_id');
        $config->mode = $request->input('mode');
        $config->currency = $request->input('currency');
        $config->active = $request->input('active');

        // Only overwrite secrets if provided
        if ($request->filled('key_secret')) {
            $config->key_secret = $request->input('key_secret');
        }
        if ($request->filled('webhook_secret')) {
            $config->webhook_secret = $request->input('webhook_secret');
        }

        $userId = $request->user()->id;
        if (!$config->exists) {
            $config->created_by = $userId;
        }
        $config->updated_by = $userId;

        $config->save();

        return response()->json([
            'message' => 'Payment gateway settings saved successfully.',
            'config' => [
                'gateway_name' => $config->gateway_name,
                'key_id' => $config->key_id,
                'mode' => $config->mode,
                'currency' => $config->currency,
                'active' => (bool)$config->active,
                'key_secret_configured' => !empty($config->key_secret),
                'webhook_secret_configured' => !empty($config->webhook_secret),
            ]
        ]);
    }

    /**
     * Test connection to Razorpay.
     */
    public function testConnection(Request $request): JsonResponse
    {
        $this->authorize('payment_gateway.manage');

        $schoolId = $request->user()->school_id;

        $keyId = $request->input('key_id');
        $keySecret = $request->input('key_secret');

        // Use stored key_secret if not provided
        if (!$keySecret) {
            $config = PaymentGatewayConfig::where('school_id', $schoolId)->first();
            $keySecret = $config ? $config->key_secret : null;
        }

        if (!$keyId || !$keySecret) {
            return response()->json(['message' => 'Key ID and Key Secret are required to test connection.'], 422);
        }

        try {
            // List payments endpoint on Razorpay serves as a credentials validator
            $response = Http::withBasicAuth($keyId, $keySecret)
                ->get('https://api.razorpay.com/v1/payments', ['count' => 1]);

            if ($response->status() === 401) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication failed. Please verify your Key ID and Key Secret.'
                ], 400);
            }

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Connection test passed! Credentials verified successfully.'
                ]);
            }

            $errorMsg = $response->json('error.description') ?? 'Gateway returned response status code ' . $response->status();
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $errorMsg
            ], 400);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reach Razorpay API: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transaction logs.
     */
    public function listTransactions(Request $request): JsonResponse
    {
        $this->authorize('payment_gateway.view');

        $schoolId = $request->user()->school_id;
        $query = OnlinePaymentTransaction::with(['student', 'installment'])
            ->where('school_id', $schoolId);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->input('start_date') . ' 00:00:00', $request->input('end_date') . ' 23:59:59']);
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'transactions' => $transactions
        ]);
    }

    /**
     * Get online fee collection reports.
     */
    public function getReports(Request $request): JsonResponse
    {
        $this->authorize('payment_gateway.view');

        $schoolId = $request->user()->school_id;

        // 1. Gather all collections
        $collections = FeeCollection::where('school_id', $schoolId)->get();

        $onlineSum = $collections->filter(function($c) {
            return str_starts_with(strtolower($c->payment_method), 'online') || $c->payment_method === 'Online';
        })->sum(fn($c) => $c->amount_paid + $c->fine_amount);

        $offlineSum = $collections->filter(function($c) {
            return !str_starts_with(strtolower($c->payment_method), 'online') && $c->payment_method !== 'Online';
        })->sum(fn($c) => $c->amount_paid + $c->fine_amount);

        // 2. Method-wise breakdown
        $methodWise = [];
        foreach ($collections->groupBy('payment_method') as $method => $group) {
            $methodWise[] = [
                'method' => $method,
                'total' => $group->sum(fn($c) => $c->amount_paid + $c->fine_amount),
                'count' => $group->count()
            ];
        }

        // 3. Gateway wise breakdown (from successful transactions)
        $txns = OnlinePaymentTransaction::where('school_id', $schoolId)->get();
        $gatewayWise = [];
        foreach ($txns->where('status', 'successful')->groupBy('gateway_name') as $gateway => $group) {
            $gatewayWise[] = [
                'gateway' => $gateway,
                'total' => $group->sum('amount'),
                'count' => $group->count()
            ];
        }

        // 4. Daily collection (last 15 days)
        $daily = [];
        $dailyGroup = FeeCollection::where('school_id', $schoolId)
            ->where('payment_date', '>=', Carbon::now()->subDays(15))
            ->get()
            ->groupBy(fn($c) => $c->payment_date->format('Y-m-d'));

        foreach ($dailyGroup as $date => $group) {
            $daily[] = [
                'date' => $date,
                'total' => $group->sum(fn($c) => $c->amount_paid + $c->fine_amount)
            ];
        }

        // 5. Monthly collection (last 6 months)
        $monthly = [];
        $monthlyGroup = FeeCollection::where('school_id', $schoolId)
            ->where('payment_date', '>=', Carbon::now()->subMonths(6))
            ->get()
            ->groupBy(fn($c) => $c->payment_date->format('Y-m'));

        foreach ($monthlyGroup as $month => $group) {
            $monthly[] = [
                'month' => $month,
                'total' => $group->sum(fn($c) => $c->amount_paid + $c->fine_amount)
            ];
        }

        return response()->json([
            'summary' => [
                'total_online' => (float)$onlineSum,
                'total_offline' => (float)$offlineSum,
                'total_collection' => (float)($onlineSum + $offlineSum),
                'successful_count' => $txns->where('status', 'successful')->count(),
                'failed_count' => $txns->where('status', 'failed')->count(),
            ],
            'method_wise' => $methodWise,
            'gateway_wise' => $gatewayWise,
            'daily' => $daily,
            'monthly' => $monthly,
        ]);
    }

    /* -------------------------------------------------------------------------- */
    /* PUBLIC WEBHOOK ENDPOINT                                                    */
    /* -------------------------------------------------------------------------- */

    /**
     * Handle public webhook updates from Razorpay or other gateways.
     */
    public function handleWebhook(Request $request, string $gateway): JsonResponse
    {
        Log::info("Payment Webhook Received: {$gateway}", [
            'headers' => $request->headers->all(),
            'payload' => $request->all()
        ]);

        try {
            // Find school from order payload or fallback
            // In Razorpay, payment entity contains order_id, notes, or school metadata
            $data = $request->all();
            $orderId = $data['payload']['payment']['entity']['order_id'] ?? null;

            if (!$orderId) {
                return response()->json(['message' => 'No order reference found in payload.'], 400);
            }

            $transaction = OnlinePaymentTransaction::where('order_id', $orderId)->first();
            if (!$transaction) {
                return response()->json(['message' => 'Transaction not found for order.'], 404);
            }

            $driver = PaymentGatewayFactory::createForSchool($transaction->school_id);
            $result = $driver->handleWebhook($request);

            if (!$result['verified']) {
                Log::warning("Payment Webhook Verification Failed for order {$orderId}", ['result' => $result]);
                return response()->json(['message' => $result['message']], 400);
            }

            if ($result['status'] === 'successful') {
                $this->paymentService->processSuccessfulPayment(
                    $transaction,
                    $result['payment_id'],
                    $request->header('X-Razorpay-Signature') ?? '',
                    $result['payment_method'],
                    $result['gateway_response']
                );
                return response()->json(['message' => 'Payment processed successfully.']);
            }

            return response()->json(['message' => 'Webhook received, but status ignored or handled elsewhere.']);

        } catch (Exception $e) {
            Log::error("Webhook error processing: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Internal server error: ' . $e->getMessage()], 500);
        }
    }

    /* -------------------------------------------------------------------------- */
    /* PARENT APP ENDPOINTS                                                       */
    /* -------------------------------------------------------------------------- */

    /**
     * Prepares standard checkout details and initiates the gateway order.
     */
    public function parentCreateOrder(Request $request): JsonResponse
    {
        $student = $request->user(); // In mobile auth context, this returns Student
        if (!$student || !($student instanceof \App\Models\Student)) {
            return response()->json(['message' => 'Unauthorized student context.'], 401);
        }

        $request->validate([
            'installment_id' => 'required|integer|exists:fee_installments,id',
            'amount' => 'required|numeric|min:0.5',
        ]);

        $academicYearId = $request->attributes->get('academic_year_id');
        if (!$academicYearId) {
            // Find current active academic year for student's school
            $activeYear = AcademicYear::where('school_id', $student->school_id)
                ->where('is_current', true)
                ->where('is_delete', 0)
                ->first();
            $academicYearId = $activeYear ? $activeYear->id : null;
        }

        if (!$academicYearId) {
            return response()->json(['message' => 'Active academic year is required.'], 422);
        }

        try {
            $transaction = $this->paymentService->createOrder(
                $student->id,
                $academicYearId,
                (int)$request->input('installment_id'),
                (float)$request->input('amount')
            );

            // Fetch active gateway config to pass back public Key ID
            $config = PaymentGatewayConfig::where('school_id', $student->school_id)
                ->where('active', true)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'order_id' => $transaction->order_id,
                'amount' => (float)$transaction->amount,
                'currency' => $transaction->currency,
                'key_id' => $config->key_id,
                'gateway' => $config->gateway_name,
                'school_name' => $student->school ? $student->school->name : 'ERP fee payments',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Post-checkout verification API to confirm payment and issue receipts.
     */
    public function parentVerifyPayment(Request $request): JsonResponse
    {
        $student = $request->user();
        if (!$student || !($student instanceof \App\Models\Student)) {
            return response()->json(['message' => 'Unauthorized student context.'], 401);
        }

        $request->validate([
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $orderId = $request->input('razorpay_order_id');
        $paymentId = $request->input('razorpay_payment_id');
        $signature = $request->input('razorpay_signature');

        try {
            $transaction = OnlinePaymentTransaction::where('order_id', $orderId)
                ->where('student_id', $student->id)
                ->firstOrFail();

            $driver = PaymentGatewayFactory::createForSchool($student->school_id);
            $verified = $driver->verifySignature([
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature,
            ]);

            if (!$verified) {
                $this->paymentService->failTransaction($transaction, ['message' => 'Signature verification failed.']);
                return response()->json([
                    'success' => false,
                    'message' => 'Payment signature verification failed.'
                ], 400);
            }

            // Get payment details from Razorpay to fetch payment method
            $method = 'Online';
            try {
                $payResponse = Http::withBasicAuth($driver->getConfig()->key_id, $driver->getConfig()->key_secret)
                    ->get("https://api.razorpay.com/v1/payments/{$paymentId}");
                if ($payResponse->successful()) {
                    $method = 'Online - ' . ucfirst($payResponse->json('method', 'Online'));
                }
            } catch (Exception $e) {
                // Keep default payment method
            }

            $transaction = $this->paymentService->processSuccessfulPayment(
                $transaction,
                $paymentId,
                $signature,
                $method,
                $request->all()
            );

            // Fetch generated receipt reference
            $receipt = FeeReceipt::where('receipt_number', $transaction->receipt_no)->first();

            return response()->json([
                'success' => true,
                'message' => 'Payment verified and captured successfully.',
                'receipt_number' => $transaction->receipt_no,
                'receipt_id' => $receipt ? $receipt->id : null,
            ]);

        } catch (Exception $e) {
            Log::error("Verify Payment API Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get student payment history logs.
     */
    public function parentPaymentHistory(Request $request): JsonResponse
    {
        $student = $request->user();
        if (!$student || !($student instanceof \App\Models\Student)) {
            return response()->json(['message' => 'Unauthorized student context.'], 401);
        }

        $transactions = OnlinePaymentTransaction::with(['installment'])
            ->where('student_id', $student->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $data = $transactions->map(function ($txn) {
            return [
                'id' => $txn->id,
                'installment_name' => $txn->installment ? $txn->installment->installment_name : 'Fee Installment',
                'amount' => (float)$txn->amount,
                'status' => $txn->status,
                'receipt_no' => $txn->receipt_no,
                'transaction_date' => $txn->transaction_date ? $txn->transaction_date->format('Y-m-d H:i:s') : $txn->created_at->format('Y-m-d H:i:s'),
                'payment_id' => $txn->payment_id,
            ];
        });

        return response()->json([
            'success' => true,
            'history' => $data
        ]);
    }
}
