<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Attendance;
use App\Models\StaffAttendance;
use App\Models\StudentAcademicRecord;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    /**
     * Display a listing of holidays.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('attendance.view');

        $request->validate([
            'academic_year_id' => 'required|integer',
        ]);

        $currentUser = $request->user();
        $schoolId = $currentUser->school_id;
        $academicYearId = $request->input('academic_year_id');

        $query = Holiday::with(['class', 'section', 'student'])
            ->where('academic_year_id', $academicYearId)
            ->where('is_delete', 0);

        if (!$currentUser->isSuperAdmin()) {
            $query->where('school_id', $schoolId);
        }

        $holidays = $query->orderBy('holiday_date', 'asc')->get();

        return response()->json([
            'holidays' => $holidays
        ]);
    }

    /**
     * Store a newly created holiday.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('attendance.create');

        $request->validate([
            'academic_year_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'holiday_date' => 'required|date_format:Y-m-d',
            'target_type' => 'required|in:all,staff,class,section,student,students',
            'class_id' => 'required_if:target_type,class,section|nullable|integer',
            'section_id' => 'required_if:target_type,section|nullable|integer',
            'student_id' => 'required_if:target_type,student|nullable|integer',
        ]);

        $currentUser = $request->user();
        $schoolId = $currentUser->school_id;

        $holiday = Holiday::create([
            'school_id' => $schoolId,
            'academic_year_id' => $request->input('academic_year_id'),
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'holiday_date' => $request->input('holiday_date'),
            'target_type' => $request->input('target_type'),
            'class_id' => $request->input('class_id'),
            'section_id' => $request->input('section_id'),
            'student_id' => $request->input('student_id'),
            'status' => 'active',
            'is_delete' => 0
        ]);

        // Automatically populate 'H' attendance for the target students & staff on this holiday
        $this->applyHolidayAttendance($holiday);

        return response()->json([
            'message' => 'Holiday created successfully.',
            'holiday' => $holiday
        ]);
    }

    /**
     * Update the specified holiday.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $this->authorize('attendance.create');

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $currentUser = $request->user();
        $schoolId = $currentUser->school_id;

        $holiday = Holiday::findOrFail($id);
        if (!$currentUser->isSuperAdmin() && $holiday->school_id !== $schoolId) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $holiday->update([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'status' => $request->input('status'),
        ]);

        // If inactive, delete the associated 'H' attendances
        if ($holiday->status === 'inactive') {
            $this->removeHolidayAttendance($holiday);
        } else {
            $this->applyHolidayAttendance($holiday);
        }

        return response()->json([
            'message' => 'Holiday updated successfully.',
            'holiday' => $holiday
        ]);
    }

    /**
     * Soft delete the specified holiday.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $this->authorize('attendance.delete');

        $currentUser = $request->user();
        $schoolId = $currentUser->school_id;

        $holiday = Holiday::findOrFail($id);
        if (!$currentUser->isSuperAdmin() && $holiday->school_id !== $schoolId) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $holiday->update(['is_delete' => 1]);

        // Remove the associated 'H' attendances
        $this->removeHolidayAttendance($holiday);

        return response()->json([
            'message' => 'Holiday deleted successfully.'
        ]);
    }

    /**
     * Helper to apply Holiday H attendance records.
     */
    private function applyHolidayAttendance(Holiday $holiday): void
    {
        $date = $holiday->holiday_date;
        $schoolId = $holiday->school_id;
        $academicYearId = $holiday->academic_year_id;

        // 1. Mark Student Attendance
        if ($holiday->target_type === 'all' || $holiday->target_type === 'students') {
            $records = StudentAcademicRecord::where('school_id', $schoolId)
                ->where('academic_year_id', $academicYearId)
                ->where('status', 'active')
                ->get();
        } elseif ($holiday->target_type === 'class') {
            $records = StudentAcademicRecord::where('school_id', $schoolId)
                ->where('academic_year_id', $academicYearId)
                ->where('class_id', $holiday->class_id)
                ->where('status', 'active')
                ->get();
        } elseif ($holiday->target_type === 'section') {
            $records = StudentAcademicRecord::where('school_id', $schoolId)
                ->where('academic_year_id', $academicYearId)
                ->where('class_id', $holiday->class_id)
                ->where('section_id', $holiday->section_id)
                ->where('status', 'active')
                ->get();
        } elseif ($holiday->target_type === 'student') {
            $records = StudentAcademicRecord::where('school_id', $schoolId)
                ->where('academic_year_id', $academicYearId)
                ->where('student_id', $holiday->student_id)
                ->where('status', 'active')
                ->get();
        } else {
            $records = collect();
        }

        foreach ($records as $rec) {
            Attendance::updateOrCreate(
                [
                    'school_id' => $schoolId,
                    'student_id' => $rec->student_id,
                    'attendance_date' => $date,
                ],
                [
                    'academic_year_id' => $academicYearId,
                    'class_id' => $rec->class_id,
                    'section_id' => $rec->section_id,
                    'status' => 'Holiday',
                    'remarks' => 'Holiday: ' . $holiday->title,
                    'is_delete' => 0
                ]
            );
        }

        // 2. Mark Staff Attendance (only for target_type = all or staff)
        if ($holiday->target_type === 'all' || $holiday->target_type === 'staff') {
            $staffUsers = User::where('school_id', $schoolId)
                ->where('status', 'active')
                ->get();

            foreach ($staffUsers as $user) {
                if ($user->isSuperAdmin()) continue;

                StaffAttendance::updateOrCreate(
                    [
                        'school_id' => $schoolId,
                        'user_id' => $user->id,
                        'attendance_date' => $date,
                    ],
                    [
                        'academic_year_id' => $academicYearId,
                        'status' => 'Holiday',
                        'remarks' => 'Holiday: ' . $holiday->title,
                        'is_delete' => 0
                    ]
                );
            }
        }
    }

    /**
     * Helper to remove Holiday H attendance records.
     */
    private function removeHolidayAttendance(Holiday $holiday): void
    {
        $date = $holiday->holiday_date;
        $schoolId = $holiday->school_id;

        // Delete H attendance for students
        Attendance::where('school_id', $schoolId)
            ->where('attendance_date', $date)
            ->where('status', 'Holiday')
            ->delete();

        // Delete H attendance for staff
        StaffAttendance::where('school_id', $schoolId)
            ->where('attendance_date', $date)
            ->where('status', 'Holiday')
            ->delete();
    }
}
