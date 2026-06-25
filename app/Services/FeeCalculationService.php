<?php

namespace App\Services;

use App\Models\StudentFeeAssignment;
use App\Models\FeeStructure;
use App\Models\StudentOptionalFee;
use App\Models\FeeCollection;
use App\Models\FeeFineRule;
use App\Models\FeeDiscount;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FeeCalculationService
{
    /**
     * Get detailed fees and installment status for a student.
     */
    public function getStudentFeeDues(int $studentId, int $academicYearId): array
    {
        // 1. Get student fee assignment
        $assignment = StudentFeeAssignment::with(['feeStructure.items.feeType', 'feeStructure.installments'])
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->first();

        if (!$assignment || !$assignment->feeStructure) {
            return [
                'has_assignment' => false,
                'total_fee' => 0.00,
                'total_paid' => 0.00,
                'total_discount' => 0.00,
                'total_fine' => 0.00,
                'outstanding_balance' => 0.00,
                'installments' => []
            ];
        }

        $structure = $assignment->feeStructure;
        $schoolId = $structure->school_id;

        // 2. Fetch student's assigned optional fee types
        $optionalFeeTypeIds = StudentOptionalFee::where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->pluck('fee_type_id')
            ->toArray();

        // 3. Calculate actual student fee total vs total potential fee of structure
        $structureTotal = 0.00;
        $studentActualTotal = 0.00;

        foreach ($structure->items as $item) {
            $amount = (float) $item->amount;
            $structureTotal += $amount;

            // Optional items only count if student has opted in
            if ($item->feeType->is_optional) {
                if (in_array($item->fee_type_id, $optionalFeeTypeIds)) {
                    $studentActualTotal += $amount;
                }
            } else {
                // Mandatory items always count
                $studentActualTotal += $amount;
            }
        }

        // Proportional scale factor for installments
        $ratio = 1.0;
        if ($structureTotal > 0) {
            $ratio = $studentActualTotal / $structureTotal;
        }

        // 4. Fetch the active fine rule for the school
        $fineRule = FeeFineRule::where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'active')
            ->where('is_delete', false)
            ->first();

        // 5. Calculate dues per installment
        $installmentsData = [];
        $overallTotalFee = 0.00;
        $overallTotalPaid = 0.00;
        $overallTotalDiscount = 0.00;
        $overallTotalFinePaid = 0.00;
        $overallTotalFineCalculated = 0.00;
        $overallOutstanding = 0.00;

        $today = Carbon::today();

        // Sort installments by sort_order
        $installments = $structure->installments->where('status', 'active')->where('is_delete', false)->sortBy('sort_order');

        foreach ($installments as $inst) {
            $instAmount = (float) $inst->amount;
            // Scale installment for the student's actual fees
            $studentInstAmount = round($instAmount * $ratio, 2);
            $overallTotalFee += $studentInstAmount;

            // Fetch payments already made for this installment
            $collections = FeeCollection::where('student_id', $studentId)
                ->where('installment_id', $inst->id)
                ->get();

            $paidAmount = (float) $collections->sum('amount_paid');
            $discountAmount = (float) $collections->sum('discount_amount');
            $finePaid = (float) $collections->sum('fine_amount');

            $overallTotalPaid += $paidAmount;
            $overallTotalDiscount += $discountAmount;
            $overallTotalFinePaid += $finePaid;

            $remainingDue = max(0.00, $studentInstAmount - ($paidAmount + $discountAmount));

            // Fine calculation
            $calculatedFine = 0.00;
            $fineDue = 0.00;
            $isOverdue = false;
            $overdueDays = 0;

            $dueDate = Carbon::parse($inst->due_date);

            if ($remainingDue > 0 && $today->greaterThan($dueDate)) {
                $isOverdue = true;
                $overdueDays = $today->diffInDays($dueDate);

                if ($fineRule && $overdueDays > $fineRule->grace_days) {
                    if ($fineRule->fine_type === 'fixed') {
                        $calculatedFine = (float) $fineRule->fine_value;
                    } elseif ($fineRule->fine_type === 'per_day') {
                        $calculatedFine = (float) $fineRule->fine_value * $overdueDays;
                    }
                }
            }

            // Fine due is calculated fine minus fine already paid
            $fineDue = max(0.00, $calculatedFine - $finePaid);
            $overallTotalFineCalculated += $calculatedFine;

            $instOutstanding = $remainingDue + $fineDue;
            $overallOutstanding += $instOutstanding;

            // Determine status
            if ($remainingDue <= 0) {
                $status = 'Paid';
            } elseif ($paidAmount > 0 || $discountAmount > 0) {
                $status = 'Partial';
            } else {
                $status = 'Unpaid';
            }

            $installmentsData[] = [
                'id' => $inst->id,
                'name' => $inst->installment_name,
                'due_date' => $inst->due_date->format('Y-m-d'),
                'is_overdue' => $isOverdue,
                'overdue_days' => $overdueDays,
                'amount' => $studentInstAmount,
                'paid' => $paidAmount,
                'discount' => $discountAmount,
                'fine_paid' => $finePaid,
                'fine_calculated' => $calculatedFine,
                'fine_due' => $fineDue,
                'remaining_due' => $remainingDue,
                'outstanding_balance' => $instOutstanding,
                'status' => $status,
            ];
        }

        // Handle rounding adjustments on final installment to match overall total fee
        // E.g., if sum of installments does not exactly equal studentActualTotal due to rounding
        if (count($installmentsData) > 0 && $studentActualTotal > 0) {
            $sumInsts = array_sum(array_column($installmentsData, 'amount'));
            $diff = round($studentActualTotal - $sumInsts, 2);
            if ($diff != 0) {
                // Adjust the last installment
                $lastIndex = count($installmentsData) - 1;
                $installmentsData[$lastIndex]['amount'] = round($installmentsData[$lastIndex]['amount'] + $diff, 2);
                $installmentsData[$lastIndex]['remaining_due'] = max(0.00, $installmentsData[$lastIndex]['amount'] - ($installmentsData[$lastIndex]['paid'] + $installmentsData[$lastIndex]['discount']));
                $installmentsData[$lastIndex]['outstanding_balance'] = $installmentsData[$lastIndex]['remaining_due'] + $installmentsData[$lastIndex]['fine_due'];

                // Recalculate overall totals
                $overallTotalFee = array_sum(array_column($installmentsData, 'amount'));
                $overallOutstanding = array_sum(array_column($installmentsData, 'outstanding_balance'));
            }
        }

        return [
            'has_assignment' => true,
            'fee_structure_name' => $structure->name,
            'fee_structure_id' => $structure->id,
            'total_fee' => $overallTotalFee,
            'total_paid' => $overallTotalPaid,
            'total_discount' => $overallTotalDiscount,
            'total_fine' => $overallTotalFinePaid,
            'outstanding_balance' => $overallOutstanding,
            'fine_rule' => $fineRule,
            'installments' => $installmentsData
        ];
    }

    /**
     * Get active discounts for a student.
     */
    public function getStudentActiveDiscounts(int $studentId, ?int $academicYearId = null): array
    {
        $today = Carbon::today()->format('Y-m-d');
        $query = FeeDiscount::where('student_id', $studentId)
            ->where('status', 'active')
            ->whereColumn('used_count', '<', 'max_uses')
            ->where('is_delete', false)
            ->where(function ($query) use ($today) {
                $query->whereNull('start_date')
                    ->orWhere('start_date', '<=', $today);
            })
            ->where(function ($query) use ($today) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $today);
            });

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        return $query->get()->toArray();
    }
}
