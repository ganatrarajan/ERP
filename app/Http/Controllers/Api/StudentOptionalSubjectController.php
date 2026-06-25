<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\StudentAcademicRecord;
use App\Models\StudentOptionalSubject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentOptionalSubjectController extends Controller
{
    /**
     * Load students and optional subjects for assignment grid.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('subject.view');

        $request->validate([
            'academic_year_id' => 'required|integer',
            'class_id' => 'required|integer',
            'section_id' => 'required|integer',
        ]);

        $currentUser = $request->user();
        $schoolId = $currentUser->isSuperAdmin()
            ? \App\Models\ClassModel::where('id', $request->class_id)->value('school_id')
            : $currentUser->school_id;

        $academicYearId = $request->input('academic_year_id');
        $classId = $request->input('class_id');
        $sectionId = $request->input('section_id');

        // 1. Fetch active students in class & section
        $recordsQuery = StudentAcademicRecord::with(['student'])
            ->where('academic_year_id', $academicYearId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('status', 'active');

        if (!$currentUser->isSuperAdmin()) {
            $recordsQuery->where('school_id', $schoolId);
        }

        $records = $recordsQuery->whereHas('student', function ($q) {
            $q->where('is_delete', 0)->where('status', 'active');
        })->get();

        $students = $records->map(function ($record) {
            return [
                'student_id' => $record->student_id,
                'name' => $record->student->first_name . ' ' . $record->student->last_name,
                'roll_no' => $record->roll_no,
                'admission_no' => $record->student->admission_no,
            ];
        })->sortBy('roll_no')->values();

        // 2. Fetch optional subjects for class & section
        $subjectsQuery = Subject::where('academic_year_id', $academicYearId)
            ->where('class_id', $classId)
            ->where('is_optional', true)
            ->where('is_delete', 0)
            ->where('status', 'active');

        if ($sectionId) {
            $subjectsQuery->where(function ($q) use ($sectionId) {
                $q->where('section_id', $sectionId)->orWhereNull('section_id');
            });
        }

        if (!$currentUser->isSuperAdmin()) {
            $subjectsQuery->where('school_id', $schoolId);
        }

        $optionalSubjects = $subjectsQuery->orderBy('name', 'asc')->get();

        // 3. Fetch currently assigned optional subjects
        $studentIds = $students->pluck('student_id')->toArray();
        $assignments = StudentOptionalSubject::where('academic_year_id', $academicYearId)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->groupBy('student_id')
            ->map(function ($items) {
                return $items->pluck('subject_id')->toArray();
            });

        return response()->json([
            'students' => $students,
            'optional_subjects' => $optionalSubjects,
            'assignments' => $assignments,
        ]);
    }

    /**
     * Save/sync optional subject assignments.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('subject.edit');

        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'assignments' => 'required|array',
            'assignments.*' => 'array|max:4',
            'assignments.*.*' => 'exists:subjects,id',
        ]);

        $currentUser = $request->user();
        $schoolId = $currentUser->isSuperAdmin()
            ? $request->input('school_id')
            : $currentUser->school_id;

        if (!$schoolId) {
            $firstStudentId = key($request->assignments);
            if ($firstStudentId) {
                $schoolId = DB::table('students')->where('id', $firstStudentId)->value('school_id');
            }
        }

        if (!$schoolId) {
            return response()->json(['message' => 'School ID is required.'], 422);
        }

        $academicYearId = $request->input('academic_year_id');

        DB::transaction(function () use ($schoolId, $academicYearId, $request) {
            foreach ($request->assignments as $studentId => $subjectIds) {
                // Delete current optional subjects for this student and academic year
                StudentOptionalSubject::where('academic_year_id', $academicYearId)
                    ->where('student_id', $studentId)
                    ->delete();

                if (!empty($subjectIds)) {
                    // Sync up to 4 subjects
                    $subjectIds = array_slice(array_unique($subjectIds), 0, 4);
                    foreach ($subjectIds as $subjectId) {
                        StudentOptionalSubject::create([
                            'school_id' => $schoolId,
                            'academic_year_id' => $academicYearId,
                            'student_id' => $studentId,
                            'subject_id' => $subjectId,
                        ]);
                    }
                }
            }
        });

        return response()->json([
            'message' => 'Optional subjects assigned successfully.',
        ]);
    }
}
