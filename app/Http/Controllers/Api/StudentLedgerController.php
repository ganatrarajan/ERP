<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\StudentFeeAssignment;
use App\Models\FeeCollection;
use App\Services\FeeCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StudentLedgerController extends Controller
{
    protected FeeCalculationService $calcService;

    public function __construct(FeeCalculationService $calcService)
    {
        $this->calcService = $calcService;
    }

    /**
     * Display student ledger.
     */
    public function show(Request $request, $studentId): JsonResponse
    {
        $this->authorize('ledger.view');

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

        $student = Student::findOrFail($studentId);

        if (!$user->isSuperAdmin() && $student->school_id !== $user->school_id) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        // 1. Calculate stats using Calculation Engine
        $duesData = $this->calcService->getStudentFeeDues((int) $studentId, (int) $academicYearId);

        // 2. Build transaction history with running balance
        $transactions = [];

        if ($duesData['has_assignment']) {
            // Get installment debits
            foreach ($duesData['installments'] as $inst) {
                $transactions[] = [
                    'date' => $inst['due_date'],
                    'type' => 'debit',
                    'description' => "Installment Due: {$inst['name']}",
                    'reference' => '',
                    'amount' => (float) $inst['amount'],
                ];
            }

            // Get collections
            $collections = FeeCollection::with('receipt')
                ->where('student_id', $studentId)
                ->where('academic_year_id', $academicYearId)
                ->orderBy('payment_date', 'asc')
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($collections as $col) {
                $ref = $col->receipt ? $col->receipt->receipt_number : 'TXN-' . $col->id;

                // Fine debit
                if ($col->fine_amount > 0) {
                    $transactions[] = [
                        'date' => $col->payment_date->format('Y-m-d'),
                        'type' => 'debit',
                        'description' => "Overdue Fine Charged ({$col->installment->installment_name})",
                        'reference' => $ref,
                        'amount' => (float) $col->fine_amount,
                    ];
                }

                // Discount credit
                if ($col->discount_amount > 0) {
                    $transactions[] = [
                        'date' => $col->payment_date->format('Y-m-d'),
                        'type' => 'credit',
                        'description' => "Fee Discount Applied ({$col->installment->installment_name})",
                        'reference' => $ref,
                        'amount' => (float) $col->discount_amount,
                    ];
                }

                // Payment credit
                $transactions[] = [
                    'date' => $col->payment_date->format('Y-m-d'),
                    'type' => 'credit',
                    'description' => "Payment Received ({$col->installment->installment_name})",
                    'reference' => $ref,
                    'amount' => (float) $col->amount_paid,
                ];
            }
        }

        // Sort chronologically by date and transaction order
        // Sort keys: date ascending, then debits first (to show charge before payment if on same day)
        usort($transactions, function ($a, $b) {
            $dateA = Carbon::parse($a['date']);
            $dateB = Carbon::parse($b['date']);
            if ($dateA->equalTo($dateB)) {
                // Same day: debits first
                if ($a['type'] === $b['type']) return 0;
                return $a['type'] === 'debit' ? -1 : 1;
            }
            return $dateA->lessThan($dateB) ? -1 : 1;
        });

        // Compute running balance
        $runningBalance = 0.00;
        foreach ($transactions as &$tx) {
            if ($tx['type'] === 'debit') {
                $runningBalance += $tx['amount'];
            } else {
                $runningBalance -= $tx['amount'];
            }
            $tx['running_balance'] = round($runningBalance, 2);
        }

        return response()->json([
            'student' => [
                'id' => $student->id,
                'name' => $student->first_name . ' ' . $student->last_name,
                'admission_no' => $student->admission_no,
            ],
            'summary' => [
                'total_fees' => $duesData['total_fee'],
                'total_paid' => $duesData['total_paid'],
                'total_discount' => $duesData['total_discount'],
                'total_fine' => $duesData['total_fine'],
                'outstanding_balance' => $duesData['outstanding_balance'],
            ],
            'transactions' => $transactions
        ]);
    }
}
