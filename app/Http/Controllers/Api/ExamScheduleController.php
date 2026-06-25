<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamSchedule;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamScheduleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('exam_schedule.view');

        $user = $request->user();
        $query = ExamSchedule::query()
            ->with(['exam', 'class', 'section', 'subject'])
            ->where('is_delete', 0);

        if (!$user->isSuperAdmin()) {
            $query->where('school_id', $user->school_id);
        } elseif ($request->has('school_id')) {
            $query->where('school_id', $request->school_id);
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->exam_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        // Return all schedules if filtering, otherwise paginate
        if ($request->filled('exam_id') && $request->filled('class_id') && $request->filled('section_id')) {
            $schedules = $query->orderBy('exam_date', 'asc')->get();
            return response()->json(['schedules' => $schedules]);
        }

        $perPage = $request->input('per_page', 10);
        $schedules = $query->orderBy('exam_date', 'desc')->paginate($perPage);

        return response()->json($schedules);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('exam_schedule.create');

        $user = $request->user();
        $schoolId = $user->isSuperAdmin() ? $request->school_id : $user->school_id;

        if (!$schoolId) {
            return response()->json(['message' => 'School ID is required.'], 422);
        }

        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'exam_id' => 'required|exists:exams,id',
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'schedules' => 'required|array',
            'schedules.*.subject_id' => 'required|exists:subjects,id',
            'schedules.*.exam_date' => 'required|date',
            'schedules.*.start_time' => 'required',
            'schedules.*.end_time' => 'required',
            'schedules.*.max_marks' => 'required|integer|min:1',
            'schedules.*.report_card_visibility' => 'nullable|string|in:included_in_result,display_only,hidden',
        ]);

        $exam = \App\Models\Exam::findOrFail($request->exam_id);
        $startDate = \Carbon\Carbon::parse($exam->start_date)->startOfDay();
        $endDate = \Carbon\Carbon::parse($exam->end_date)->endOfDay();

        foreach ($request->schedules as $index => $sched) {
            $examDate = \Carbon\Carbon::parse($sched['exam_date'])->startOfDay();
            if ($examDate->lt($startDate) || $examDate->gt($endDate)) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => [
                        'schedules' => [
                            "The exam date for subject must be between {$exam->start_date->format('Y-m-d')} and {$exam->end_date->format('Y-m-d')}."
                        ]
                    ]
                ], 422);
            }

            // Check if exam date falls on a holiday or weekend
            $holiday = \App\Services\HolidayService::isHolidayForStudent($schoolId, 0, $sched['exam_date'], $request->class_id, $request->section_id, $request->academic_year_id);
            if ($holiday) {
                $subject = Subject::find($sched['subject_id']);
                $subjectName = $subject ? $subject->name : 'subject';
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => [
                        'schedules' => [
                            "The exam date {$sched['exam_date']} for {$subjectName} falls on a holiday or weekend ({$holiday->title})."
                        ]
                    ]
                ], 422);
            }
        }

        $academicYearId = $request->academic_year_id;
        $examId = $request->exam_id;
        $classId = $request->class_id;
        $sectionId = $request->section_id;

        $savedSchedules = DB::transaction(function () use ($schoolId, $academicYearId, $examId, $classId, $sectionId, $request) {
            $submittedSubjectIds = collect($request->schedules)->pluck('subject_id')->toArray();

            // Soft-delete any existing schedules for this exam/class/section that are NOT in the submitted list
            ExamSchedule::where('school_id', $schoolId)
                ->where('academic_year_id', $academicYearId)
                ->where('exam_id', $examId)
                ->where('class_id', $classId)
                ->where('section_id', $sectionId)
                ->whereNotIn('subject_id', $submittedSubjectIds)
                ->update(['is_delete' => 1]);

            $results = [];
            foreach ($request->schedules as $sched) {
                $schedule = ExamSchedule::updateOrCreate(
                    [
                        'school_id' => $schoolId,
                        'academic_year_id' => $academicYearId,
                        'exam_id' => $examId,
                        'class_id' => $classId,
                        'section_id' => $sectionId,
                        'subject_id' => $sched['subject_id'],
                    ],
                    [
                        'exam_date' => $sched['exam_date'],
                        'start_time' => $sched['start_time'],
                        'end_time' => $sched['end_time'],
                        'max_marks' => $sched['max_marks'],
                        'report_card_visibility' => $sched['report_card_visibility'] ?? null,
                        'is_delete' => 0,
                    ]
                );
                $results[] = $schedule;
            }
            return $results;
        });

        return response()->json([
            'message' => 'Exam schedules saved successfully.',
            'schedules' => $savedSchedules
        ]);
    }

    public function destroy(ExamSchedule $examSchedule, Request $request): JsonResponse
    {
        $this->authorize('exam_schedule.delete');

        $user = $request->user();
        if (!$user->isSuperAdmin() && $examSchedule->school_id !== $user->school_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $examSchedule->update(['is_delete' => 1]);

        return response()->json([
            'message' => 'Exam schedule deleted successfully.'
        ]);
    }
}
