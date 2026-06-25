<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendance;
use App\Models\User;
use App\Services\HolidayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StaffAttendanceController extends Controller
{
    /**
     * Load staff members with attendance status for a date.
     */
    public function loadStaff(Request $request): JsonResponse
    {
        $this->authorize('attendance.view');

        $request->validate([
            'attendance_date' => 'required|date_format:Y-m-d',
        ]);

        $currentUser = $request->user();
        $schoolId = $currentUser->school_id;
        $attendanceDate = $request->input('attendance_date');

        // Fetch all active school users who are not Super Admin
        $staffQuery = User::where('school_id', $schoolId)
            ->where('status', 'active');

        $staffList = $staffQuery->get();

        // Fetch existing staff attendance for this date
        $attendances = StaffAttendance::where('school_id', $schoolId)
            ->where('attendance_date', $attendanceDate)
            ->where('is_delete', 0)
            ->get()
            ->keyBy('user_id');

        $staffData = $staffList->map(function ($user) use ($attendances, $schoolId, $attendanceDate) {
            $holiday = HolidayService::isHolidayForStaff($schoolId, $user->id, $attendanceDate);
            $attendance = $attendances->get($user->id);

            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'attendance_status' => $holiday ? 'Holiday' : ($attendance ? $attendance->status : 'Present'),
                'remarks' => $holiday ? 'Holiday: ' . $holiday->title : ($attendance ? $attendance->remarks : ''),
                'attendance_id' => $attendance ? $attendance->id : null,
                'is_holiday' => $holiday ? true : false,
            ];
        })->sortBy('name')->values();

        // Check if there is a general holiday for staff
        $generalHoliday = \App\Models\Holiday::where('school_id', $schoolId)
            ->where('holiday_date', $attendanceDate)
            ->where('target_type', 'all')
            ->where('status', 'active')
            ->where('is_delete', 0)
            ->first();

        return response()->json([
            'staff' => $staffData,
            'is_holiday' => $generalHoliday ? true : false,
            'holiday_title' => $generalHoliday ? $generalHoliday->title : null,
        ]);
    }

    /**
     * Mark/Store staff attendance.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('attendance.create');

        $request->validate([
            'attendance_date' => 'required|date_format:Y-m-d',
            'staff' => 'required|array',
            'staff.*.user_id' => 'required|integer',
            'staff.*.status' => 'required|string|in:Present,Absent,Late,Half Day,Leave,Holiday',
            'staff.*.remarks' => 'nullable|string',
        ]);

        $currentUser = $request->user();
        $schoolId = $currentUser->school_id;
        $attendanceDate = $request->input('attendance_date');

        $currentYear = \App\Models\AcademicYear::where('school_id', $schoolId)
            ->where('is_current', true)
            ->first();
        $academicYearId = $currentYear ? $currentYear->id : null;
        if (!$academicYearId) {
            $anyYear = \App\Models\AcademicYear::where('school_id', $schoolId)->first();
            $academicYearId = $anyYear ? $anyYear->id : null;
        }

        // Check if this date is a holiday
        foreach ($request->input('staff') as $sData) {
            $holiday = HolidayService::isHolidayForStaff($schoolId, $sData['user_id'], $attendanceDate);
            if ($holiday) {
                throw ValidationException::withMessages([
                    'attendance_date' => ["Cannot save attendance on a holiday: {$holiday->title}"]
                ]);
            }
        }

        foreach ($request->input('staff') as $sData) {
            StaffAttendance::updateOrCreate(
                [
                    'school_id' => $schoolId,
                    'user_id' => $sData['user_id'],
                    'attendance_date' => $attendanceDate,
                ],
                [
                    'academic_year_id' => $academicYearId,
                    'status' => $sData['status'],
                    'remarks' => $sData['remarks'] ?? null,
                    'marked_by' => $currentUser->id,
                    'is_delete' => 0
                ]
            );
        }

        return response()->json([
            'message' => 'Staff attendance marking saved successfully.'
        ]);
    }

    /**
     * Get Staff Individual Attendance Report.
     */
    public function report(Request $request): JsonResponse
    {
        $this->authorize('attendance.view');

        $request->validate([
            'user_id' => 'required|integer',
        ]);

        $currentUser = $request->user();
        $userId = $request->input('user_id');

        $staff = User::findOrFail($userId);

        if (!$currentUser->isSuperAdmin() && $staff->school_id !== $currentUser->school_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $attendances = StaffAttendance::where('user_id', $userId)
            ->where('is_delete', 0)
            ->orderBy('attendance_date', 'desc')
            ->get();

        $stats = [
            'Present' => $attendances->where('status', 'Present')->count(),
            'Absent' => $attendances->where('status', 'Absent')->count(),
            'Late' => $attendances->where('status', 'Late')->count(),
            'Half Day' => $attendances->where('status', 'Half Day')->count(),
            'Leave' => $attendances->where('status', 'Leave')->count(),
            'Holiday' => $attendances->where('status', 'Holiday')->count(),
            'total' => $attendances->count(),
        ];

        return response()->json([
            'staff' => $staff,
            'attendances' => $attendances,
            'stats' => $stats
        ]);
    }

    /**
     * Download Staff Individual Attendance Report as PDF.
     */
    public function reportPdf(Request $request)
    {
        $this->authorize('attendance.view');

        $request->validate([
            'user_id' => 'required|integer',
        ]);

        $currentUser = $request->user();
        $userId = $request->input('user_id');

        $staff = User::findOrFail($userId);

        if (!$currentUser->isSuperAdmin() && $staff->school_id !== $currentUser->school_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $attendances = StaffAttendance::where('user_id', $userId)
            ->where('is_delete', 0)
            ->orderBy('attendance_date', 'desc')
            ->get();

        $stats = [
            'Present' => $attendances->where('status', 'Present')->count(),
            'Absent' => $attendances->where('status', 'Absent')->count(),
            'Late' => $attendances->where('status', 'Late')->count(),
            'Half Day' => $attendances->where('status', 'Half Day')->count(),
            'Leave' => $attendances->where('status', 'Leave')->count(),
            'Holiday' => $attendances->where('status', 'Holiday')->count(),
            'total' => $attendances->count(),
        ];

        $schoolName = $currentUser->school ? $currentUser->school->name : 'School ERP';
        
        $currentYear = \App\Models\AcademicYear::where('school_id', $staff->school_id)
            ->where('is_current', true)
            ->first();
        if (!$currentYear) {
            $currentYear = \App\Models\AcademicYear::where('school_id', $staff->school_id)->first();
        }

        $pdfData = [
            'school_name' => $schoolName,
            'academic_year' => $currentYear ? $currentYear->title : 'All Sessions',
            'staff' => $staff,
            'attendances' => $attendances,
            'stats' => $stats
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('attendance.staff_report_pdf', $pdfData);

        return $pdf->download('Staff_Attendance_Report_' . str_replace(' ', '_', $staff->name) . '.pdf');
    }

    /**
     * Get Staff Monthly Grid Report.
     */
    public function monthlyGrid(Request $request): JsonResponse
    {
        $this->authorize('attendance.view');

        $request->validate([
            'month' => 'required|date_format:Y-m', // e.g. "2026-06"
        ]);

        $currentUser = $request->user();
        $schoolId = $currentUser->school_id;
        $monthStr = $request->input('month');

        $carbonMonth = \Carbon\Carbon::parse($monthStr . '-01');
        $daysInMonth = $carbonMonth->daysInMonth;
        $startDate = $carbonMonth->copy()->startOfMonth()->format('Y-m-d');
        $endDate = $carbonMonth->copy()->endOfMonth()->format('Y-m-d');

        $staffUsers = User::where('school_id', $schoolId)
            ->where('status', 'active')
            ->get();

        $attendances = StaffAttendance::whereBetween('attendance_date', [$startDate, $endDate])
            ->where('is_delete', 0);

        if (!$currentUser->isSuperAdmin()) {
            $attendances->where('school_id', $schoolId);
        }

        $attendancesGrouped = $attendances->get()->groupBy('user_id');

        $matrix = $staffUsers->map(function ($user) use ($attendancesGrouped, $daysInMonth, $monthStr, $schoolId) {
            $staffAttendances = $attendancesGrouped->get($user->id) ?? collect();

            $daysData = [];
            $present = 0;
            $absent = 0;
            $late = 0;
            $halfDay = 0;
            $leave = 0;
            $holidayCount = 0;

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $dateStr = sprintf('%s-%02d', $monthStr, $day);
                $att = $staffAttendances->firstWhere('attendance_date', $dateStr);

                if ($att) {
                    $status = $att->status;
                } else {
                    $holiday = HolidayService::isHolidayForStaff($schoolId, $user->id, $dateStr);
                    $status = $holiday ? 'Holiday' : '-';
                }

                $daysData[$day] = $status;

                if ($status === 'Present') $present++;
                elseif ($status === 'Absent') $absent++;
                elseif ($status === 'Late') $late++;
                elseif ($status === 'Half Day') $halfDay++;
                elseif ($status === 'Leave') $leave++;
                elseif ($status === 'Holiday') $holidayCount++;
            }

            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'days' => $daysData,
                'stats' => [
                    'Present' => $present,
                    'Absent' => $absent,
                    'Late' => $late,
                    'Half Day' => $halfDay,
                    'Leave' => $leave,
                    'Holiday' => $holidayCount,
                ]
            ];
        })->sortBy('name')->values();

        return response()->json([
            'days_in_month' => $daysInMonth,
            'matrix' => $matrix
        ]);
    }

    /**
     * Download Staff Monthly Grid as PDF.
     */
    public function monthlyGridPdf(Request $request)
    {
        $this->authorize('attendance.view');

        $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        $currentUser = $request->user();
        $schoolId = $currentUser->school_id;
        $monthStr = $request->input('month');

        $carbonMonth = \Carbon\Carbon::parse($monthStr . '-01');
        $daysInMonth = $carbonMonth->daysInMonth;
        $startDate = $carbonMonth->copy()->startOfMonth()->format('Y-m-d');
        $endDate = $carbonMonth->copy()->endOfMonth()->format('Y-m-d');

        $staffUsers = User::where('school_id', $schoolId)
            ->where('status', 'active')
            ->get();

        $attendances = StaffAttendance::whereBetween('attendance_date', [$startDate, $endDate])
            ->where('is_delete', 0);

        if (!$currentUser->isSuperAdmin()) {
            $attendances->where('school_id', $schoolId);
        }

        $attendancesGrouped = $attendances->get()->groupBy('user_id');

        $matrix = $staffUsers->map(function ($user) use ($attendancesGrouped, $daysInMonth, $monthStr, $schoolId) {
            $staffAttendances = $attendancesGrouped->get($user->id) ?? collect();

            $daysData = [];
            $present = 0;
            $absent = 0;
            $late = 0;
            $halfDay = 0;
            $leave = 0;
            $holidayCount = 0;

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $dateStr = sprintf('%s-%02d', $monthStr, $day);
                $att = $staffAttendances->firstWhere('attendance_date', $dateStr);

                if ($att) {
                    $status = $att->status;
                } else {
                    $holiday = HolidayService::isHolidayForStaff($schoolId, $user->id, $dateStr);
                    $status = $holiday ? 'Holiday' : '-';
                }

                $daysData[$day] = $status;

                if ($status === 'Present') $present++;
                elseif ($status === 'Absent') $absent++;
                elseif ($status === 'Late') $late++;
                elseif ($status === 'Half Day') $halfDay++;
                elseif ($status === 'Leave') $leave++;
                elseif ($status === 'Holiday') $holidayCount++;
            }

            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'days' => $daysData,
                'stats' => [
                    'Present' => $present,
                    'Absent' => $absent,
                    'Late' => $late,
                    'Half Day' => $halfDay,
                    'Leave' => $leave,
                    'Holiday' => $holidayCount,
                ]
            ];
        })->sortBy('name')->values();

        $schoolName = $currentUser->school ? $currentUser->school->name : 'School ERP';
        
        $currentYear = \App\Models\AcademicYear::where('school_id', $schoolId)
            ->where('is_current', true)
            ->first();
        if (!$currentYear) {
            $currentYear = \App\Models\AcademicYear::where('school_id', $schoolId)->first();
        }

        $pdfData = [
            'school_name' => $schoolName,
            'academic_year' => $currentYear ? $currentYear->title : 'All Sessions',
            'month' => $carbonMonth->format('F Y'),
            'days_in_month' => $daysInMonth,
            'matrix' => $matrix
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('attendance.staff_monthly_grid_pdf', $pdfData)
            ->setPaper('a4', 'landscape');

        return $pdf->download('Staff_Attendance_Report_' . $monthStr . '.pdf');
    }
}
