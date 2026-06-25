<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\StudentAcademicRecord;
use App\Models\Attendance;
use App\Models\StaffAttendance;
use App\Models\User;
use App\Models\AcademicYear;
use App\Models\WeekendSetting;
use Illuminate\Support\Facades\DB;

class HolidayService
{
    /**
     * Check if date is holiday for a student.
     */
    public static function isHolidayForStudent(int $schoolId, int $studentId, string $date, ?int $classId = null, ?int $sectionId = null, ?int $academicYearId = null): ?Holiday
    {
        if (!$classId || !$sectionId) {
            $record = StudentAcademicRecord::where('student_id', $studentId)
                ->where('status', 'active')
                ->first();
            if ($record) {
                $classId = $classId ?: $record->class_id;
                $sectionId = $sectionId ?: $record->section_id;
                $academicYearId = $academicYearId ?: $record->academic_year_id;
            }
        }

        if (!$academicYearId && $classId) {
            $classModel = \App\Models\ClassModel::find($classId);
            if ($classModel) {
                $academicYearId = $classModel->academic_year_id;
            }
        }

        if (!$academicYearId) {
            $yearByDate = AcademicYear::where('school_id', $schoolId)
                ->where('start_date', '<=', $date)
                ->where('end_date', '>=', $date)
                ->where('is_delete', 0)
                ->first();
            $academicYearId = $yearByDate ? $yearByDate->id : null;
        }

        // Sunday Check (always holiday)
        $dayOfWeek = date('N', strtotime($date));
        if ($dayOfWeek == 7) {
            $holiday = new Holiday();
            $holiday->title = 'Sunday';
            $holiday->holiday_date = $date;
            $holiday->target_type = 'all';
            $holiday->status = 'active';
            $holiday->is_delete = 0;
            return $holiday;
        }

        // Saturday Check (check WeekendSetting)
        if ($dayOfWeek == 6) {
            $weekendSetting = WeekendSetting::where('school_id', $schoolId)
                ->where('day_name', 'Saturday')
                ->where('is_holiday', true)
                ->where(function ($query) use ($classId, $sectionId) {
                    $query->where('target_type', 'all')
                        ->orWhere(function ($q) use ($classId, $sectionId) {
                            $q->where('target_type', 'class_section')
                                ->where('class_id', $classId)
                                ->where(function ($sq) use ($sectionId) {
                                    $sq->whereNull('section_id')
                                       ->orWhere('section_id', $sectionId);
                                });
                        });
                })
                ->first();

            if ($weekendSetting) {
                $holiday = new Holiday();
                $holiday->title = 'Saturday Holiday';
                $holiday->holiday_date = $date;
                $holiday->target_type = $weekendSetting->target_type === 'all' ? 'all' : 'students';
                $holiday->status = 'active';
                $holiday->is_delete = 0;
                return $holiday;
            }
        }

        $query = Holiday::where('school_id', $schoolId)
            ->where('holiday_date', $date)
            ->where('status', 'active')
            ->where('is_delete', 0);

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        return $query->where(function ($query) use ($classId, $sectionId, $studentId) {
                $query->where('target_type', 'all')
                    ->orWhere('target_type', 'students')
                    ->orWhere(function ($q) use ($classId) {
                        $q->where('target_type', 'class')->where('class_id', $classId);
                    })
                    ->orWhere(function ($q) use ($sectionId) {
                        $q->where('target_type', 'section')->where('section_id', $sectionId);
                    })
                    ->orWhere(function ($q) use ($studentId) {
                        $q->where('target_type', 'student')->where('student_id', $studentId);
                    });
            })
            ->first();
    }

    /**
     * Check if date is holiday for staff.
     */
    public static function isHolidayForStaff(int $schoolId, int $userId, string $date, ?int $academicYearId = null): ?Holiday
    {
        // Sunday Check (always holiday)
        $dayOfWeek = date('N', strtotime($date));
        if ($dayOfWeek == 7) {
            $holiday = new Holiday();
            $holiday->title = 'Sunday';
            $holiday->holiday_date = $date;
            $holiday->target_type = 'all';
            $holiday->status = 'active';
            $holiday->is_delete = 0;
            return $holiday;
        }

        // Saturday Check (check WeekendSetting for all/school-wide)
        if ($dayOfWeek == 6) {
            $weekendSetting = WeekendSetting::where('school_id', $schoolId)
                ->where('day_name', 'Saturday')
                ->where('is_holiday', true)
                ->where('target_type', 'all')
                ->first();

            if ($weekendSetting) {
                $holiday = new Holiday();
                $holiday->title = 'Saturday Holiday';
                $holiday->holiday_date = $date;
                $holiday->target_type = 'all';
                $holiday->status = 'active';
                $holiday->is_delete = 0;
                return $holiday;
            }
        }

        if (!$academicYearId) {
            $yearByDate = AcademicYear::where('school_id', $schoolId)
                ->where('start_date', '<=', $date)
                ->where('end_date', '>=', $date)
                ->where('is_delete', 0)
                ->first();
            $academicYearId = $yearByDate ? $yearByDate->id : null;
        }

        $query = Holiday::where('school_id', $schoolId)
            ->where('holiday_date', $date)
            ->whereIn('target_type', ['all', 'staff'])
            ->where('status', 'active')
            ->where('is_delete', 0);

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        return $query->first();
    }

    /**
     * Run nightly job: auto mark holiday attendance.
     */
    public static function autoMarkHolidayAttendance(string $date): void
    {
        // Get all active holidays for this date
        $holidays = Holiday::where('holiday_date', $date)
            ->where('status', 'active')
            ->where('is_delete', 0)
            ->get();

        foreach ($holidays as $holiday) {
            $schoolId = $holiday->school_id;
            $academicYearId = $holiday->academic_year_id;

            // 1. Process Students
            if ($holiday->target_type === 'all' || $holiday->target_type === 'students') {
                // All active students in the current academic year
                $studentRecords = StudentAcademicRecord::where('school_id', $schoolId)
                    ->where('academic_year_id', $academicYearId)
                    ->where('status', 'active')
                    ->get();
            } elseif ($holiday->target_type === 'class') {
                $studentRecords = StudentAcademicRecord::where('school_id', $schoolId)
                    ->where('academic_year_id', $academicYearId)
                    ->where('class_id', $holiday->class_id)
                    ->where('status', 'active')
                    ->get();
            } elseif ($holiday->target_type === 'section') {
                $studentRecords = StudentAcademicRecord::where('school_id', $schoolId)
                    ->where('academic_year_id', $academicYearId)
                    ->where('class_id', $holiday->class_id)
                    ->where('section_id', $holiday->section_id)
                    ->where('status', 'active')
                    ->get();
            } elseif ($holiday->target_type === 'student') {
                $studentRecords = StudentAcademicRecord::where('school_id', $schoolId)
                    ->where('academic_year_id', $academicYearId)
                    ->where('student_id', $holiday->student_id)
                    ->where('status', 'active')
                    ->get();
            } else {
                $studentRecords = collect();
            }

            foreach ($studentRecords as $rec) {
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

            // 2. Process Staff (all users who are not Super Admin)
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
    }

    /**
     * Apply all active holidays of the current academic year to a newly added student.
     */
    public static function applyHolidaysToNewStudent(int $schoolId, int $academicYearId, int $studentId, int $classId, int $sectionId): void
    {
        $holidays = Holiday::where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'active')
            ->where('is_delete', 0)
            ->where(function ($query) use ($classId, $sectionId, $studentId) {
                $query->whereIn('target_type', ['all', 'students'])
                    ->orWhere(function ($q) use ($classId) {
                        $q->where('target_type', 'class')->where('class_id', $classId);
                    })
                    ->orWhere(function ($q) use ($sectionId) {
                        $q->where('target_type', 'section')->where('section_id', $sectionId);
                    })
                    ->orWhere(function ($q) use ($studentId) {
                        $q->where('target_type', 'student')->where('student_id', $studentId);
                    });
            })
            ->get();

        foreach ($holidays as $holiday) {
            Attendance::updateOrCreate(
                [
                    'school_id' => $schoolId,
                    'student_id' => $studentId,
                    'attendance_date' => $holiday->holiday_date,
                ],
                [
                    'academic_year_id' => $academicYearId,
                    'class_id' => $classId,
                    'section_id' => $sectionId,
                    'status' => 'Holiday',
                    'remarks' => 'Holiday: ' . $holiday->title,
                    'is_delete' => 0
                ]
            );
        }
    }

    /**
     * Apply all active holidays to a newly added staff user.
     */
    public static function applyHolidaysToNewStaff(int $schoolId, int $userId): void
    {
        $currentYear = AcademicYear::where('school_id', $schoolId)
            ->where('status', 'active')
            ->where('is_current', true)
            ->where('is_delete', 0)
            ->first() ?? AcademicYear::where('school_id', $schoolId)
            ->where('status', 'active')
            ->where('is_delete', 0)
            ->first();

        if (!$currentYear) {
            return;
        }

        $holidays = Holiday::where('school_id', $schoolId)
            ->where('academic_year_id', $currentYear->id)
            ->whereIn('target_type', ['all', 'staff'])
            ->where('status', 'active')
            ->where('is_delete', 0)
            ->get();

        foreach ($holidays as $holiday) {
            StaffAttendance::updateOrCreate(
                [
                    'school_id' => $schoolId,
                    'user_id' => $userId,
                    'attendance_date' => $holiday->holiday_date,
                ],
                [
                    'academic_year_id' => $holiday->academic_year_id,
                    'status' => 'Holiday',
                    'remarks' => 'Holiday: ' . $holiday->title,
                    'is_delete' => 0
                ]
            );
        }
    }
}
