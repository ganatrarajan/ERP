<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeeCollection;
use App\Models\StudentAcademicRecord;
use App\Models\StudentFeeAssignment;
use App\Models\FeeInstallment;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Services\FeeCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FeeReportController extends Controller
{
    protected FeeCalculationService $calcService;

    public function __construct(FeeCalculationService $calcService)
    {
        $this->calcService = $calcService;
    }

    /**
     * Get report data based on report type and filters.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('report.view');

        $user = $request->user();
        $schoolId = $user->isSuperAdmin() ? $request->input('school_id') : $user->school_id;

        if (!$schoolId) {
            return response()->json(['message' => 'School ID is required.'], 422);
        }

        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'report_type' => 'required|in:collection,pending,installment_wise,class_wise,student_wise,daily,monthly,discount,fine',
            'class_id' => 'nullable|integer',
            'section_id' => 'nullable|integer',
            'student_id' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $reportType = $request->input('report_type');
        $academicYearId = (int) $request->input('academic_year_id');
        $classId = $request->input('class_id');
        $sectionId = $request->input('section_id');
        $studentId = $request->input('student_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Base query for collections
        $collectionsQuery = FeeCollection::with(['student', 'installment.feeStructure.class', 'collectedBy', 'receipt'])
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId);

        if ($studentId) {
            $collectionsQuery->where('student_id', $studentId);
        }

        if ($classId || $sectionId) {
            $collectionsQuery->whereHas('student.academicRecords', function ($q) use ($academicYearId, $classId, $sectionId) {
                $q->where('academic_year_id', $academicYearId);
                if ($classId) $q->where('class_id', $classId);
                if ($sectionId) $q->where('section_id', $sectionId);
            });
        }

        if ($startDate && $endDate) {
            $collectionsQuery->whereBetween('payment_date', [$startDate, $endDate]);
        }

        $collections = $collectionsQuery->get();

        $data = [];

        switch ($reportType) {
            case 'collection':
                // List of collections with details
                foreach ($collections as $col) {
                    $classRec = $col->student ? $col->student->academicRecords->where('academic_year_id', $academicYearId)->first() : null;
                    $data[] = [
                        'receipt_number' => $col->receipt ? $col->receipt->receipt_number : 'N/A',
                        'student_name' => $col->student ? $col->student->first_name . ' ' . $col->student->last_name : 'N/A',
                        'admission_no' => $col->student ? $col->student->admission_no : 'N/A',
                        'class_name' => $classRec && $classRec->class ? $classRec->class->name : 'N/A',
                        'installment' => $col->installment ? $col->installment->installment_name : 'N/A',
                        'payment_date' => $col->payment_date->format('Y-m-d'),
                        'payment_method' => $col->payment_method,
                        'amount_due' => $col->amount_due,
                        'amount_paid' => $col->amount_paid,
                        'discount_amount' => $col->discount_amount,
                        'fine_amount' => $col->fine_amount,
                    ];
                }
                break;

            case 'pending':
                // List students and their outstanding balance
                $recordsQuery = StudentAcademicRecord::with(['student', 'class', 'section'])
                    ->where('school_id', $schoolId)
                    ->where('academic_year_id', $academicYearId)
                    ->where('status', 'active');

                if ($classId) $recordsQuery->where('class_id', $classId);
                if ($sectionId) $recordsQuery->where('section_id', $sectionId);
                if ($studentId) $recordsQuery->where('student_id', $studentId);

                $records = $recordsQuery->get();

                foreach ($records as $rec) {
                    if (!$rec->student || $rec->student->is_delete) continue;
                    
                    $dues = $this->calcService->getStudentFeeDues($rec->student_id, $academicYearId);
                    
                    if ($dues['has_assignment']) {
                        $data[] = [
                            'student_id' => $rec->student_id,
                            'student_name' => $rec->student->first_name . ' ' . $rec->student->last_name,
                            'admission_no' => $rec->student->admission_no,
                            'class_name' => $rec->class ? $rec->class->name : 'N/A',
                            'section_name' => $rec->section ? $rec->section->name : 'N/A',
                            'total_fee' => $dues['total_fee'],
                            'total_paid' => $dues['total_paid'],
                            'total_discount' => $dues['total_discount'],
                            'total_fine' => $dues['total_fine'],
                            'outstanding_balance' => $dues['outstanding_balance'],
                        ];
                    }
                }
                break;

            case 'installment_wise':
                // Installment wise collections
                $instGroup = $collections->groupBy('installment_id');
                foreach ($instGroup as $instId => $cols) {
                    $inst = FeeInstallment::find($instId);
                    if ($inst) {
                        $data[] = [
                            'installment_name' => $inst->installment_name,
                            'due_date' => $inst->due_date->format('Y-m-d'),
                            'total_collected' => $cols->sum('amount_paid'),
                            'total_discount' => $cols->sum('discount_amount'),
                            'total_fine' => $cols->sum('fine_amount'),
                            'transactions_count' => $cols->count(),
                        ];
                    }
                }
                break;

            case 'class_wise':
                // Class wise collections
                $classGroup = $collections->groupBy(function ($col) use ($academicYearId) {
                    if ($col->student) {
                        $record = $col->student->academicRecords->where('academic_year_id', $academicYearId)->first();
                        return $record && $record->class ? $record->class->name : 'N/A';
                    }
                    return 'N/A';
                });

                foreach ($classGroup as $className => $cols) {
                    $data[] = [
                        'class_name' => $className,
                        'total_collected' => $cols->sum('amount_paid'),
                        'total_discount' => $cols->sum('discount_amount'),
                        'total_fine' => $cols->sum('fine_amount'),
                        'transactions_count' => $cols->count(),
                    ];
                }
                break;

            case 'student_wise':
                // Student wise collections
                $studentGroup = $collections->groupBy('student_id');
                foreach ($studentGroup as $sId => $cols) {
                    $firstCol = $cols->first();
                    $student = $firstCol ? $firstCol->student : null;
                    if ($student) {
                        $classRec = $student->academicRecords->where('academic_year_id', $academicYearId)->first();
                        $data[] = [
                            'student_name' => $student->first_name . ' ' . $student->last_name,
                            'admission_no' => $student->admission_no,
                            'class_name' => $classRec && $classRec->class ? $classRec->class->name : 'N/A',
                            'total_collected' => $cols->sum('amount_paid'),
                            'total_discount' => $cols->sum('discount_amount'),
                            'total_fine' => $cols->sum('fine_amount'),
                            'transactions_count' => $cols->count(),
                        ];
                    }
                }
                break;

            case 'daily':
                // Daily collection
                $dailyGroup = $collections->groupBy(function ($col) {
                    return $col->payment_date->format('Y-m-d');
                })->sortKeys();
                foreach ($dailyGroup as $date => $cols) {
                    $data[] = [
                        'date' => $date,
                        'total_collected' => $cols->sum('amount_paid'),
                        'total_discount' => $cols->sum('discount_amount'),
                        'total_fine' => $cols->sum('fine_amount'),
                        'transactions_count' => $cols->count(),
                    ];
                }
                break;

            case 'monthly':
                // Monthly collection
                $monthlyGroup = $collections->groupBy(function ($col) {
                    return $col->payment_date->format('Y-m');
                })->sortKeys();
                foreach ($monthlyGroup as $month => $cols) {
                    $data[] = [
                        'month' => $month,
                        'total_collected' => $cols->sum('amount_paid'),
                        'total_discount' => $cols->sum('discount_amount'),
                        'total_fine' => $cols->sum('fine_amount'),
                        'transactions_count' => $cols->count(),
                    ];
                }
                break;

            case 'discount':
                // Discounts report
                $discountCols = $collections->where('discount_amount', '>', 0);
                foreach ($discountCols as $col) {
                    $classRec = $col->student ? $col->student->academicRecords->where('academic_year_id', $academicYearId)->first() : null;
                    $data[] = [
                        'receipt_number' => $col->receipt ? $col->receipt->receipt_number : 'N/A',
                        'student_name' => $col->student ? $col->student->first_name . ' ' . $col->student->last_name : 'N/A',
                        'admission_no' => $col->student ? $col->student->admission_no : 'N/A',
                        'class_name' => $classRec && $classRec->class ? $classRec->class->name : 'N/A',
                        'installment' => $col->installment ? $col->installment->installment_name : 'N/A',
                        'payment_date' => $col->payment_date->format('Y-m-d'),
                        'discount_amount' => $col->discount_amount,
                        'remarks' => $col->remarks,
                    ];
                }
                break;

            case 'fine':
                // Fine report
                $fineCols = $collections->where('fine_amount', '>', 0);
                foreach ($fineCols as $col) {
                    $classRec = $col->student ? $col->student->academicRecords->where('academic_year_id', $academicYearId)->first() : null;
                    $data[] = [
                        'receipt_number' => $col->receipt ? $col->receipt->receipt_number : 'N/A',
                        'student_name' => $col->student ? $col->student->first_name . ' ' . $col->student->last_name : 'N/A',
                        'admission_no' => $col->student ? $col->student->admission_no : 'N/A',
                        'class_name' => $classRec && $classRec->class ? $classRec->class->name : 'N/A',
                        'installment' => $col->installment ? $col->installment->installment_name : 'N/A',
                        'payment_date' => $col->payment_date->format('Y-m-d'),
                        'fine_amount' => $col->fine_amount,
                        'remarks' => $col->remarks,
                    ];
                }
                break;
        }

        return response()->json([
            'report_type' => $reportType,
            'data' => $data
        ]);
    }
}
