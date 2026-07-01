<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentAcademicRecord;
use App\Models\StudentFeeAssignment;
use App\Models\StudentOptionalFee;
use App\Models\FeeStructure;
use App\Models\AcademicYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentFeeAssignmentController extends Controller
{
    /**
     * Display a listing of students with their fee assignment.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('fee_structure.view');

        $user = $request->user();
        $schoolId = $user->isSuperAdmin() ? $request->input('school_id') : $user->school_id;

        if (!$schoolId) {
            return response()->json(['message' => 'School ID is required.'], 422);
        }

        // Active Academic Year defaults if not provided
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

        // Query academic records for the year
        $recordsQuery = StudentAcademicRecord::with(['student', 'class', 'section'])
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'active');

        if ($request->filled('class_id')) {
            $recordsQuery->where('class_id', $request->input('class_id'));
        }

        if ($request->filled('section_id')) {
            $recordsQuery->where('section_id', $request->input('section_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $recordsQuery->whereHas('student', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('admission_no', 'like', "%{$search}%");
            });
        }

        $records = $recordsQuery->get();

        $studentIds = $records->pluck('student_id')->toArray();

        // Fetch assignments and optional fee types for the student list
        $assignments = StudentFeeAssignment::with('feeStructure')
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        $optionalFees = StudentOptionalFee::with('feeType')
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->groupBy('student_id');

        $data = [];
        foreach ($records as $rec) {
            $student = $rec->student;
            if (!$student) continue;

            $assigned = $assignments->get($student->id);
            $optSelected = $optionalFees->get($student->id, collect());

            $data[] = [
                'student_id' => $student->id,
                'admission_no' => $student->admission_no,
                'name' => $student->first_name . ' ' . $student->last_name,
                'class_id' => $rec->class_id,
                'academic_year_id' => $rec->academic_year_id,
                'class' => $rec->class ? $rec->class->name : '',
                'section' => $rec->section ? $rec->section->name : '',
                'assignment' => $assigned ? [
                    'id' => $assigned->id,
                    'fee_structure_id' => $assigned->fee_structure_id,
                    'fee_structure_name' => $assigned->feeStructure ? $assigned->feeStructure->name : 'N/A',
                    'assigned_date' => $assigned->assigned_date->format('Y-m-d'),
                    'remarks' => $assigned->remarks,
                ] : null,
                'optional_fees' => $optSelected->map(function ($opt) {
                    return [
                        'fee_type_id' => $opt->fee_type_id,
                        'name' => $opt->feeType ? $opt->feeType->name : 'N/A'
                    ];
                })
            ];
        }

        return response()->json([
            'students' => $data
        ]);
    }

    /**
     * Store/Update a student fee assignment individually, syncing optional fees.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('fee_structure.create');

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
            'fee_structure_id' => 'required|exists:fee_structures,id',
            'assigned_date' => 'required|date',
            'remarks' => 'nullable|string',
            'optional_fee_type_ids' => 'nullable|array',
            'optional_fee_type_ids.*' => 'exists:fee_types,id',
        ]);

        $studentId = $request->input('student_id');
        $academicYearId = $request->input('academic_year_id');
        $feeStructureId = $request->input('fee_structure_id');
        $optionalFeeIds = $request->input('optional_fee_type_ids', []);

        // Validate the fee structure belongs to the student's class, school, and academic year
        $academicRecord = StudentAcademicRecord::where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->first();

        if (!$academicRecord) {
            return response()->json(['message' => 'No active academic record found for this student in the selected academic year.'], 422);
        }

        $feeStructure = FeeStructure::where('id', $feeStructureId)
            ->where('school_id', $schoolId)
            ->where('is_delete', false)
            ->first();

        if (!$feeStructure) {
            return response()->json(['message' => 'Selected fee structure does not exist.'], 422);
        }

        if ($feeStructure->class_id !== $academicRecord->class_id) {
            return response()->json(['message' => 'The selected fee structure does not belong to the student\'s class.'], 422);
        }

        if ($feeStructure->academic_year_id !== $academicYearId) {
            return response()->json(['message' => 'The selected fee structure does not belong to the selected academic year.'], 422);
        }

        DB::transaction(function () use ($schoolId, $studentId, $academicYearId, $feeStructureId, $optionalFeeIds, $request) {
            // 1. Create or Update Student Fee Assignment
            StudentFeeAssignment::updateOrCreate(
                [
                    'school_id' => $schoolId,
                    'academic_year_id' => $academicYearId,
                    'student_id' => $studentId,
                ],
                [
                    'fee_structure_id' => $feeStructureId,
                    'assigned_date' => $request->input('assigned_date'),
                    'remarks' => $request->input('remarks'),
                ]
            );

            // 2. Sync Optional Fees
            StudentOptionalFee::where('student_id', $studentId)
                ->where('academic_year_id', $academicYearId)
                ->delete();

            foreach ($optionalFeeIds as $feeTypeId) {
                StudentOptionalFee::create([
                    'school_id' => $schoolId,
                    'academic_year_id' => $academicYearId,
                    'student_id' => $studentId,
                    'fee_type_id' => $feeTypeId,
                ]);
            }
        });

        return response()->json([
            'message' => 'Student fee structure assigned successfully'
        ]);
    }

    /**
     * Bulk assign fee structure to a class.
     */
    public function bulkAssign(Request $request): JsonResponse
    {
        $this->authorize('fee_structure.create');

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
            'class_id' => 'required|exists:classes,id',
            'fee_structure_id' => 'required|exists:fee_structures,id',
            'assigned_date' => 'required|date',
            'remarks' => 'nullable|string',
            'overwrite_existing' => 'boolean',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id'
        ]);

        $academicYearId = $request->input('academic_year_id');
        $classId = $request->input('class_id');
        $feeStructureId = $request->input('fee_structure_id');
        $assignedDate = $request->input('assigned_date');
        $remarks = $request->input('remarks');
        $overwrite = $request->boolean('overwrite_existing', false);
        $studentIds = $request->input('student_ids');

        // Validate the fee structure belongs to the class, school, and academic year
        $feeStructure = FeeStructure::where('id', $feeStructureId)
            ->where('school_id', $schoolId)
            ->where('is_delete', false)
            ->first();

        if (!$feeStructure) {
            return response()->json(['message' => 'Selected fee structure does not exist.'], 422);
        }

        if ($feeStructure->class_id !== $classId) {
            return response()->json(['message' => 'The selected fee structure does not belong to the selected class.'], 422);
        }

        if ($feeStructure->academic_year_id !== $academicYearId) {
            return response()->json(['message' => 'The selected fee structure does not belong to the selected academic year.'], 422);
        }

        // Fetch active students in class for the year
        $recordsQuery = StudentAcademicRecord::where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->where('class_id', $classId)
            ->where('status', 'active');

        if (!empty($studentIds)) {
            $recordsQuery->whereIn('student_id', $studentIds);
        }

        $records = $recordsQuery->get();

        if ($records->isEmpty()) {
            return response()->json(['message' => 'No active students found matching the assignment target.'], 404);
        }

        $count = DB::transaction(function () use ($records, $schoolId, $academicYearId, $feeStructureId, $assignedDate, $remarks, $overwrite) {
            $inserted = 0;
            foreach ($records as $rec) {
                if ($overwrite) {
                    StudentFeeAssignment::updateOrCreate(
                        [
                            'school_id' => $schoolId,
                            'academic_year_id' => $academicYearId,
                            'student_id' => $rec->student_id,
                        ],
                        [
                            'fee_structure_id' => $feeStructureId,
                            'assigned_date' => $assignedDate,
                            'remarks' => $remarks,
                        ]
                    );
                    $inserted++;
                } else {
                    // Only create if not exists
                    $exists = StudentFeeAssignment::where('student_id', $rec->student_id)
                        ->where('academic_year_id', $academicYearId)
                        ->exists();
                    if (!$exists) {
                        StudentFeeAssignment::create([
                            'school_id' => $schoolId,
                            'academic_year_id' => $academicYearId,
                            'student_id' => $rec->student_id,
                            'fee_structure_id' => $feeStructureId,
                            'assigned_date' => $assignedDate,
                            'remarks' => $remarks,
                        ]);
                        $inserted++;
                    }
                }
            }
            return $inserted;
        });

        return response()->json([
            'message' => "Fee structure assigned successfully to {$count} student(s)."
        ]);
    }
}
