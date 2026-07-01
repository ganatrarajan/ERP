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

        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'report_type' => 'required|in:collection,pending,installment_wise,class_wise,student_wise,daily,monthly,discount,fine',
            'class_id' => 'nullable|integer',
            'section_id' => 'nullable|integer',
            'student_id' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        try {
            $data = $this->compileReportData($request);
            return response()->json([
                'report_type' => $request->input('report_type'),
                'data' => $data
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function downloadPdf(Request $request)
    {
        $this->authorize('report.view');

        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'report_type' => 'required|in:collection,pending,installment_wise,class_wise,student_wise,daily,monthly,discount,fine',
            'class_id' => 'nullable|integer',
            'section_id' => 'nullable|integer',
            'student_id' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'selected_columns' => 'nullable|string',
        ]);

        $reportType = $request->input('report_type');
        $academicYearId = (int) $request->input('academic_year_id');
        $academicYear = AcademicYear::find($academicYearId);
        $academicYearTitle = $academicYear ? $academicYear->title : 'N/A';

        $columnLabels = [
            'receipt_number' => 'Receipt #',
            'student_name' => 'Student Name',
            'admission_no' => 'Adm No',
            'class_name' => 'Class',
            'section_name' => 'Section',
            'installment' => 'Installment',
            'payment_date' => 'Payment Date',
            'payment_method' => 'Mode',
            'amount_due' => 'Due',
            'amount_paid' => 'Paid',
            'discount_amount' => 'Discount',
            'fine_amount' => 'Fine',
            'total_fee' => 'Total Fee',
            'total_paid' => 'Total Paid',
            'total_discount' => 'Total Discount',
            'total_fine' => 'Total Fine',
            'outstanding_balance' => 'Outstanding',
            'due_date' => 'Due Date',
            'total_collected' => 'Total Collected',
            'transactions_count' => 'Transactions',
            'date' => 'Date',
            'month' => 'Month',
            'remarks' => 'Remarks',
        ];

        $selectedKeys = array_filter(explode(',', $request->input('selected_columns', '')));
        $columns = [];
        foreach ($selectedKeys as $key) {
            $key = trim($key);
            if (isset($columnLabels[$key])) {
                $columns[] = [
                    'key' => $key,
                    'label' => $columnLabels[$key]
                ];
            }
        }

        if (empty($columns)) {
            $fallbackKeys = [
                'collection' => ['receipt_number', 'student_name', 'class_name', 'installment', 'payment_date', 'payment_method', 'amount_paid'],
                'pending' => ['student_name', 'admission_no', 'class_name', 'total_fee', 'total_paid', 'outstanding_balance'],
                'installment_wise' => ['installment_name', 'due_date', 'total_collected', 'total_discount', 'total_fine'],
                'class_wise' => ['class_name', 'total_collected', 'total_discount', 'total_fine', 'transactions_count'],
                'student_wise' => ['student_name', 'admission_no', 'class_name', 'total_collected', 'total_discount', 'total_fine'],
                'daily' => ['date', 'total_collected', 'total_discount', 'total_fine', 'transactions_count'],
                'monthly' => ['month', 'total_collected', 'total_discount', 'total_fine', 'transactions_count'],
                'discount' => ['receipt_number', 'student_name', 'payment_date', 'discount_amount', 'remarks'],
                'fine' => ['receipt_number', 'student_name', 'payment_date', 'fine_amount', 'remarks'],
            ];
            $keys = $fallbackKeys[$reportType] ?? [];
            foreach ($keys as $key) {
                $columns[] = [
                    'key' => $key,
                    'label' => $columnLabels[$key] ?? ucfirst(str_replace('_', ' ', $key))
                ];
            }
        }

        try {
            $data = $this->compileReportData($request);
        } catch (\InvalidArgumentException $e) {
            abort(422, $e->getMessage());
        }

        // Get user school details
        $user = $request->user();
        $school = $user->school;
        
        $reportTitles = [
            'collection' => 'Fee Collection Register',
            'pending' => 'Outstanding Fee Dues Report',
            'installment_wise' => 'Installment-wise Collection Summary',
            'class_wise' => 'Class-wise Fee Collection Report',
            'student_wise' => 'Student-wise Collection Summary',
            'daily' => 'Daily Fee Collection Register',
            'monthly' => 'Monthly Fee Collection Summary',
            'discount' => 'Fee Discount Waiver Report',
            'fine' => 'Fee Fine / Penalty Register'
        ];
        $reportTitle = $reportTitles[$reportType] ?? 'Fee Report';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.fee_report', [
            'school' => $school,
            'reportTitle' => $reportTitle,
            'academicYearTitle' => $academicYearTitle,
            'startDate' => $request->input('start_date'),
            'endDate' => $request->input('end_date'),
            'columns' => $columns,
            'data' => $data,
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf->stream(str_replace(' ', '-', strtolower($reportTitle)) . '-' . date('Y-m-d') . '.pdf');
    }

    private function compileReportData(Request $request): array
    {
        $user = $request->user();
        $schoolId = $user->isSuperAdmin() ? $request->input('school_id') : $user->school_id;

        if (!$schoolId) {
            throw new \InvalidArgumentException('School ID is required.');
        }

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

        return $data;
    }
}
