<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\StudentAcademicRecord;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Http\Requests\Attendance\StoreAttendanceRequest;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Load student list with their attendance status for a specific date.
     */
    public function loadStudents(Request $request): JsonResponse
    {
        $this->authorize('attendance.view');

        $request->validate([
            'academic_year_id' => 'required|integer',
            'class_id' => 'required|integer',
            'section_id' => 'required|integer',
            'attendance_date' => 'required|date_format:Y-m-d',
        ]);

        $currentUser = $request->user();
        $schoolId = $currentUser->school_id;

        $academicYearId = $request->input('academic_year_id');
        $classId = $request->input('class_id');
        $sectionId = $request->input('section_id');
        $attendanceDate = $request->input('attendance_date');

        // Check class/section tenancy
        if (!$currentUser->isSuperAdmin()) {
            $class = ClassModel::where('id', $classId)->where('school_id', $schoolId)->first();
            if (!$class) {
                return response()->json(['message' => 'Class not found.'], 404);
            }
        }

        // Fetch students associated with the class/section for the given academic year
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

        // Fetch existing attendance for this date
        $attendancesQuery = Attendance::where('academic_year_id', $academicYearId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('attendance_date', $attendanceDate)
            ->where('is_delete', 0);

        if (!$currentUser->isSuperAdmin()) {
            $attendancesQuery->where('school_id', $schoolId);
        }

        $attendances = $attendancesQuery->get()->keyBy('student_id');

        $students = $records->map(function ($record) use ($attendances, $schoolId, $attendanceDate, $classId, $sectionId, $academicYearId) {
            $student = $record->student;
            $holiday = \App\Services\HolidayService::isHolidayForStudent($schoolId, $student->id, $attendanceDate, $classId, $sectionId, $academicYearId);
            $attendance = $attendances->get($student->id);
            return [
                'student_id' => $student->id,
                'roll_no' => $record->roll_no,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'admission_no' => $student->admission_no,
                'gender' => $student->gender,
                'attendance_status' => $holiday ? 'Holiday' : ($attendance ? $attendance->status : 'Present'),
                'remarks' => $holiday ? 'Holiday: ' . $holiday->title : ($attendance ? $attendance->remarks : ''),
                'attendance_id' => $attendance ? $attendance->id : null,
                'is_holiday' => $holiday ? true : false,
            ];
        })->sortBy('roll_no')->values();

        // Check if there is a general holiday for this class/section on this date
        $generalHoliday = \App\Models\Holiday::where('school_id', $schoolId)
            ->where('holiday_date', $attendanceDate)
            ->where('status', 'active')
            ->where('is_delete', 0)
            ->where(function ($q) use ($classId, $sectionId) {
                $q->where('target_type', 'all')
                    ->orWhere(function($q2) use ($classId) {
                        $q2->where('target_type', 'class')->where('class_id', $classId);
                    })
                    ->orWhere(function($q2) use ($sectionId) {
                        $q2->where('target_type', 'section')->where('section_id', $sectionId);
                    });
            })
            ->first();

        return response()->json([
            'students' => $students,
            'is_holiday' => $generalHoliday ? true : false,
            'holiday_title' => $generalHoliday ? $generalHoliday->title : null,
        ]);
    }

    /**
     * Store/Mark attendance.
     */
    public function store(StoreAttendanceRequest $request): JsonResponse
    {
        $this->authorize('attendance.create');

        $currentUser = $request->user();
        $schoolId = $currentUser->isSuperAdmin()
            ? StudentAcademicRecord::where('class_id', $request->input('class_id'))->value('school_id')
            : $currentUser->school_id;

        if (!$schoolId) {
            return response()->json(['message' => 'School ID could not be determined.'], 422);
        }

        $this->attendanceService->markAttendance(
            $request->validated(),
            $schoolId,
            $currentUser->id
        );

        return response()->json([
            'message' => 'Attendance saved successfully.'
        ]);
    }

    /**
     * Get monthly attendance grid/view.
     */
    public function monthlyView(Request $request): JsonResponse
    {
        $this->authorize('attendance.view');

        $request->validate([
            'academic_year_id' => 'required|integer',
            'class_id' => 'required|integer',
            'section_id' => 'required|integer',
            'month' => 'required|date_format:Y-m', // e.g. "2026-06"
        ]);

        $currentUser = $request->user();
        $schoolId = $currentUser->school_id;

        $academicYearId = $request->input('academic_year_id');
        $classId = $request->input('class_id');
        $sectionId = $request->input('section_id');
        $monthStr = $request->input('month');

        $carbonMonth = Carbon::parse($monthStr . '-01');
        $daysInMonth = $carbonMonth->daysInMonth;
        $startDate = $carbonMonth->copy()->startOfMonth()->format('Y-m-d');
        $endDate = $carbonMonth->copy()->endOfMonth()->format('Y-m-d');

        // Fetch students
        $recordsQuery = StudentAcademicRecord::with(['student'])
            ->where('academic_year_id', $academicYearId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('status', 'active');

        if (!$currentUser->isSuperAdmin()) {
            $recordsQuery->where('school_id', $schoolId);
        }

        $records = $recordsQuery->whereHas('student', function ($q) {
            $q->where('is_delete', 0);
        })->get();

        // Fetch attendance records for this month
        $attendancesQuery = Attendance::where('academic_year_id', $academicYearId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->where('is_delete', 0);

        if (!$currentUser->isSuperAdmin()) {
            $attendancesQuery->where('school_id', $schoolId);
        }

        $attendances = $attendancesQuery->get()->groupBy('student_id');

        $matrix = $records->map(function ($record) use ($attendances, $daysInMonth, $monthStr, $schoolId, $classId, $sectionId, $academicYearId) {
            $student = $record->student;
            $studentAttendances = $attendances->get($student->id) ?? collect();

            $daysData = [];
            $present = 0;
            $absent = 0;
            $late = 0;
            $halfDay = 0;
            $leave = 0;
            $holidayCount = 0;

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $dateStr = sprintf('%s-%02d', $monthStr, $day);
                $att = $studentAttendances->firstWhere('attendance_date', $dateStr);

                $status = $att ? $att->status : '-';
                if ($status === '-') {
                    $holiday = \App\Services\HolidayService::isHolidayForStudent($schoolId, $student->id, $dateStr, $classId, $sectionId, $academicYearId);
                    if ($holiday) {
                        $status = 'Holiday';
                    }
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
                'student_id' => $student->id,
                'roll_no' => $record->roll_no,
                'name' => $student->first_name . ' ' . $student->last_name,
                'days' => $daysData,
                'stats' => [
                    'Present' => $present,
                    'Absent' => $absent,
                    'Late' => $late,
                    'Half Day' => $halfDay,
                    'Leave' => $leave,
                    'Holiday' => $holidayCount,
                    'total_days' => $present + $absent + $late + $halfDay + $leave + $holidayCount,
                ]
            ];
        })->sortBy('roll_no')->values();

        return response()->json([
            'days_in_month' => $daysInMonth,
            'matrix' => $matrix
        ]);
    }

    /**
     * Get Student Attendance Report.
     */
    public function studentReport(Request $request): JsonResponse
    {
        $this->authorize('attendance.view');

        $request->validate([
            'student_id' => 'required|integer',
            'academic_year_id' => 'required|integer',
        ]);

        $currentUser = $request->user();
        $studentId = $request->input('student_id');
        $academicYearId = $request->input('academic_year_id');

        $student = Student::findOrFail($studentId);

        if (!$currentUser->isSuperAdmin() && $student->school_id !== $currentUser->school_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $attendances = Attendance::with(['class', 'section'])
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('is_delete', 0)
            ->orderBy('attendance_date', 'desc')
            ->get();

        $stats = [
            'Present' => $attendances->where('status', 'Present')->count(),
            'Absent' => $attendances->where('status', 'Absent')->count(),
            'Late' => $attendances->where('status', 'Late')->count(),
            'Half Day' => $attendances->where('status', 'Half Day')->count(),
            'Leave' => $attendances->where('status', 'Leave')->count(),
            'total' => $attendances->count(),
        ];

        return response()->json([
            'student' => $student,
            'attendances' => $attendances,
            'stats' => $stats
        ]);
    }

    /**
     * Get Class Attendance Report.
     */
    public function classReport(Request $request): JsonResponse
    {
        $this->authorize('attendance.view');

        $request->validate([
            'academic_year_id' => 'required|integer',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $currentUser = $request->user();
        $schoolId = $currentUser->school_id;
        $academicYearId = $request->input('academic_year_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Query active classes
        $classesQuery = ClassModel::with(['sections'])
            ->where('academic_year_id', $academicYearId)
            ->where('is_delete', 0);

        if (!$currentUser->isSuperAdmin()) {
            $classesQuery->where('school_id', $schoolId);
        }

        $classes = $classesQuery->get();

        $report = [];

        foreach ($classes as $class) {
            foreach ($class->sections as $section) {
                // Fetch attendance for this class/section in the date range
                $attQuery = Attendance::where('class_id', $class->id)
                    ->where('section_id', $section->id)
                    ->whereBetween('attendance_date', [$startDate, $endDate])
                    ->where('is_delete', 0);

                if (!$currentUser->isSuperAdmin()) {
                    $attQuery->where('school_id', $schoolId);
                }

                $atts = $attQuery->get();

                $total = $atts->count();
                $present = $atts->whereIn('status', ['Present', 'Late', 'Half Day'])->count();
                $absent = $atts->where('status', 'Absent')->count();
                $leave = $atts->where('status', 'Leave')->count();

                $percentage = $total > 0 ? round(($present / $total) * 100, 2) : 100;

                $report[] = [
                    'class_id' => $class->id,
                    'class_name' => $class->name,
                    'section_id' => $section->id,
                    'section_name' => $section->name,
                    'total_records' => $total,
                    'presents' => $present,
                    'absents' => $absent,
                    'leaves' => $leave,
                    'attendance_rate' => $percentage
                ];
            }
        }

        return response()->json([
            'report' => $report
        ]);
    }

    /**
     * Download monthly attendance report as PDF.
     */
    public function downloadPdfReport(Request $request)
    {
        $this->authorize('attendance.view');

        $request->validate([
            'academic_year_id' => 'required|integer',
            'class_id' => 'required|integer',
            'section_id' => 'required|integer',
            'month' => 'required|date_format:Y-m', // e.g. "2026-06"
        ]);

        $currentUser = $request->user();
        $schoolId = $currentUser->school_id;

        $academicYearId = $request->input('academic_year_id');
        $classId = $request->input('class_id');
        $sectionId = $request->input('section_id');
        $monthStr = $request->input('month');

        $carbonMonth = Carbon::parse($monthStr . '-01');
        $daysInMonth = $carbonMonth->daysInMonth;
        $startDate = $carbonMonth->copy()->startOfMonth()->format('Y-m-d');
        $endDate = $carbonMonth->copy()->endOfMonth()->format('Y-m-d');

        // Fetch students
        $recordsQuery = StudentAcademicRecord::with(['student'])
            ->where('academic_year_id', $academicYearId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('status', 'active');

        if (!$currentUser->isSuperAdmin()) {
            $recordsQuery->where('school_id', $schoolId);
        }

        $records = $recordsQuery->whereHas('student', function ($q) {
            $q->where('is_delete', 0);
        })->get();

        // Fetch attendance records for this month
        $attendancesQuery = Attendance::where('academic_year_id', $academicYearId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->where('is_delete', 0);

        if (!$currentUser->isSuperAdmin()) {
            $attendancesQuery->where('school_id', $schoolId);
        }

        $attendances = $attendancesQuery->get()->groupBy('student_id');

        $matrix = $records->map(function ($record) use ($attendances, $daysInMonth, $monthStr, $schoolId, $classId, $sectionId, $academicYearId) {
            $student = $record->student;
            $studentAttendances = $attendances->get($student->id) ?? collect();

            $daysData = [];
            $present = 0;
            $absent = 0;
            $late = 0;
            $halfDay = 0;
            $leave = 0;
            $holidayCount = 0;

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $dateStr = sprintf('%s-%02d', $monthStr, $day);
                $att = $studentAttendances->firstWhere('attendance_date', $dateStr);

                $status = $att ? $att->status : '-';
                if ($status === '-') {
                    $holiday = \App\Services\HolidayService::isHolidayForStudent($schoolId, $student->id, $dateStr, $classId, $sectionId, $academicYearId);
                    if ($holiday) {
                        $status = 'Holiday';
                    }
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
                'roll_no' => $record->roll_no,
                'name' => $student->first_name . ' ' . $student->last_name,
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
        })->sortBy('roll_no')->values();

        $schoolName = $currentUser->school ? $currentUser->school->name : 'EduvoraX ERP';
        $classModel = ClassModel::find($classId);
        $sectionModel = Section::find($sectionId);
        $academicYearModel = \App\Models\AcademicYear::find($academicYearId);

        $pdfData = [
            'school_name' => $schoolName,
            'academic_year' => $academicYearModel ? $academicYearModel->title : '',
            'class_name' => $classModel ? $classModel->name : '',
            'section_name' => $sectionModel ? $sectionModel->name : '',
            'month' => $carbonMonth->format('F Y'),
            'days_in_month' => $daysInMonth,
            'matrix' => $matrix
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('attendance.report_pdf', $pdfData)
            ->setPaper('a4', 'landscape');

        return $pdf->stream('Attendance_Report_' . $monthStr . '.pdf');
    }

    /**
     * Download Student Individual Attendance Report as PDF.
     */
    public function studentReportPdf(Request $request)
    {
        $this->authorize('attendance.view');

        $request->validate([
            'student_id' => 'required|integer',
            'academic_year_id' => 'required|integer',
        ]);

        $currentUser = $request->user();
        $studentId = $request->input('student_id');
        $academicYearId = $request->input('academic_year_id');

        $student = Student::findOrFail($studentId);

        if (!$currentUser->isSuperAdmin() && $student->school_id !== $currentUser->school_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $attendances = Attendance::with(['class', 'section'])
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
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

        $schoolName = $currentUser->school ? $currentUser->school->name : 'EduvoraX ERP';
        $academicYearModel = \App\Models\AcademicYear::find($academicYearId);

        $pdfData = [
            'school_name' => $schoolName,
            'academic_year' => $academicYearModel ? $academicYearModel->title : '',
            'student' => $student,
            'attendances' => $attendances,
            'stats' => $stats
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('attendance.student_individual_report_pdf', $pdfData);

        return $pdf->stream('Student_Attendance_Report_' . str_replace(' ', '_', $student->first_name . '_' . $student->last_name) . '.pdf');
    }
}
