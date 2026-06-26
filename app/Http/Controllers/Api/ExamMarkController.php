<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamMark;
use App\Models\ExamSchedule;
use App\Models\StudentAcademicRecord;
use App\Models\Subject;
use App\Models\GradeScale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamMarkController extends Controller
{
    public function loadStudentsForMarks(Request $request): JsonResponse
    {
        $this->authorize('marks.view');

        $request->validate([
            'academic_year_id' => 'required|integer',
            'exam_id' => 'required|integer',
            'class_id' => 'required|integer',
            'section_id' => 'required|integer',
            'subject_id' => 'required|integer',
        ]);

        $user = $request->user();
        $schoolId = $user->isSuperAdmin()
            ? \App\Models\ClassModel::where('id', $request->class_id)->value('school_id')
            : $user->school_id;

        $academicYearId = $request->academic_year_id;
        $examId = $request->exam_id;
        $classId = $request->class_id;
        $sectionId = $request->section_id;
        $subjectId = $request->subject_id;

        // Fetch subject to check evaluation type
        $subjectQuery = Subject::where('id', $subjectId);
        if (!$user->isSuperAdmin()) {
            $subjectQuery->where('school_id', $schoolId);
        }
        $subject = $subjectQuery->firstOrFail();

        // Fetch exam schedule
        $scheduleQuery = ExamSchedule::where('exam_id', $examId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('subject_id', $subjectId)
            ->where('is_delete', 0);
        if (!$user->isSuperAdmin()) {
            $scheduleQuery->where('school_id', $schoolId);
        }
        $schedule = $scheduleQuery->first();

        if (!$schedule) {
            return response()->json([
                'message' => 'No exam schedule found for this subject. Please schedule the exam first.'
            ], 422);
        }

        // Fetch active students
        $recordsQuery = StudentAcademicRecord::with(['student'])
            ->where('academic_year_id', $academicYearId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('status', 'active');
        if (!$user->isSuperAdmin()) {
            $recordsQuery->where('school_id', $schoolId);
        }

        // Filter by optional subjects if applicable
        if ($subject->is_optional) {
            $studentIds = DB::table('student_optional_subjects')
                ->where('academic_year_id', $academicYearId)
                ->where('subject_id', $subjectId)
                ->pluck('student_id')
                ->toArray();
            $recordsQuery->whereIn('student_id', $studentIds);
        }

        $records = $recordsQuery->whereHas('student', function ($q) {
            $q->where('is_delete', 0)->where('status', 'active');
        })->get();

        // Fetch existing marks
        $marksQuery = ExamMark::where('exam_id', $examId)
            ->where('exam_schedule_id', $schedule->id)
            ->where('subject_id', $subjectId);
        if (!$user->isSuperAdmin()) {
            $marksQuery->where('school_id', $schoolId);
        }
        $existingMarks = $marksQuery->get()->keyBy('student_id');

        // Fetch grade scales
        $gradesQuery = GradeScale::where('status', 'active');
        if (!$user->isSuperAdmin()) {
            $gradesQuery->where('school_id', $schoolId);
        }
        // If subject has specific grade scale, limit to it
        if (!empty($subject->grade_scale_id) && is_array($subject->grade_scale_id)) {
            $gradesQuery->whereIn('id', $subject->grade_scale_id);
        }
        $grades = $gradesQuery->orderBy('min_percentage', 'desc')->get();

        $students = $records->map(function ($record) use ($existingMarks) {
            $student = $record->student;
            $mark = $existingMarks->get($student->id);
            return [
                'student_id' => $student->id,
                'roll_no' => $record->roll_no,
                'name' => $student->first_name . ' ' . $student->last_name,
                'admission_no' => $student->admission_no,
                'marks_obtained' => $mark ? $mark->marks_obtained : null,
                'is_absent' => $mark ? (bool)$mark->is_absent : false,
                'grade_id' => $mark ? $mark->grade_id : null,
                'remarks' => $mark ? $mark->remarks : '',
                'mark_id' => $mark ? $mark->id : null,
            ];
        })->sortBy('roll_no')->values();

        return response()->json([
            'subject' => $subject,
            'schedule' => $schedule,
            'students' => $students,
            'grades' => $grades,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('marks.create');

        $user = $request->user();
        $schoolId = $user->isSuperAdmin() ? $request->school_id : $user->school_id;

        if (!$schoolId) {
            return response()->json(['message' => 'School ID is required.'], 422);
        }

        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'exam_id' => 'required|exists:exams,id',
            'exam_schedule_id' => 'required|exists:exam_schedules,id',
            'subject_id' => 'required|exists:subjects,id',
            'marks' => 'required|array',
            'marks.*.student_id' => 'required|exists:students,id',
            'marks.*.marks_obtained' => 'nullable|numeric|min:0',
            'marks.*.is_absent' => 'nullable|boolean',
            'marks.*.grade_id' => 'nullable|exists:grade_scales,id',
            'marks.*.remarks' => 'nullable|string|max:255',
        ]);

        $academicYearId = $request->academic_year_id;
        $examId = $request->exam_id;
        $scheduleId = $request->exam_schedule_id;
        $subjectId = $request->subject_id;

        $schedule = ExamSchedule::findOrFail($scheduleId);
        $subject = Subject::findOrFail($subjectId);

        // Validate max marks
        foreach ($request->marks as $markData) {
            if ($subject->evaluation_type === 'marks' && isset($markData['marks_obtained'])) {
                if ($markData['marks_obtained'] > $schedule->max_marks) {
                    return response()->json([
                        'message' => "Marks obtained cannot exceed maximum marks ({$schedule->max_marks}) of the subject."
                    ], 422);
                }
            }
        }

        $savedMarks = DB::transaction(function () use ($schoolId, $academicYearId, $examId, $scheduleId, $subjectId, $user, $request, $schedule) {
            $results = [];
            foreach ($request->marks as $markData) {
                $isAbsent = isset($markData['is_absent']) ? (bool)$markData['is_absent'] : false;
                $gradeId = $markData['grade_id'] ?? null;
                
                // Let's implement Auto Grade calculation if evaluation_type = marks and a grade scale is linked!
                if (!$isAbsent && $request->subject_evaluation_type === 'marks' && isset($markData['marks_obtained'])) {
                    // Let's find grade based on percentage
                    $percentage = ($markData['marks_obtained'] / $schedule->max_marks) * 100;
                    $matchedGrade = GradeScale::where('school_id', $schoolId)
                        ->where('status', 'active')
                        ->where('min_percentage', '<=', $percentage)
                        ->orderBy('min_percentage', 'desc')
                        ->first();
                    if ($matchedGrade) {
                        $gradeId = $matchedGrade->id;
                    }
                }

                if ($isAbsent) {
                    $gradeId = null;
                }

                $mark = ExamMark::updateOrCreate(
                    [
                        'school_id' => $schoolId,
                        'academic_year_id' => $academicYearId,
                        'exam_id' => $examId,
                        'exam_schedule_id' => $scheduleId,
                        'student_id' => $markData['student_id'],
                        'subject_id' => $subjectId,
                    ],
                    [
                        'marks_obtained' => $isAbsent ? null : ($markData['marks_obtained'] ?? null),
                        'is_absent' => $isAbsent,
                        'grade_id' => $gradeId,
                        'remarks' => $markData['remarks'] ?? null,
                        'entered_by' => $user->id,
                    ]
                );
                $results[] = $mark;
            }
            return $results;
        });

        try {
            $fcmService = app(\App\Services\FcmService::class);
            $schedule->load('exam');
            foreach ($request->marks as $markData) {
                $fcmService->sendToStudent(
                    $markData['student_id'],
                    "Exam Results Published",
                    "Your marks for " . $subject->name . " (" . $schedule->exam->name . ") have been published.",
                    [
                        'type' => 'result',
                        'id' => $examId,
                    ]
                );
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("FCM Exam Marks Notification Error: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Exam marks saved successfully.',
            'marks' => $savedMarks
        ]);
    }
}
