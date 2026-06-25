<?php

namespace App\Services;

use App\Models\Attendance;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    /**
     * Mark or update student attendance.
     */
    public function markAttendance(array $data, int $schoolId, int $markedByUserId): array
    {
        $records = [];

        DB::transaction(function () use ($data, $schoolId, $markedByUserId, &$records) {
            foreach ($data['students'] as $studentData) {
                // Check if the date is a holiday for this student
                $holiday = \App\Services\HolidayService::isHolidayForStudent(
                    $schoolId,
                    $studentData['student_id'],
                    $data['attendance_date'],
                    $data['class_id'],
                    $data['section_id'],
                    $data['academic_year_id']
                );

                if ($holiday) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'attendance_date' => ["Cannot save attendance on a holiday: {$holiday->title}"]
                    ]);
                }

                $record = Attendance::updateOrCreate(
                    [
                        'school_id' => $schoolId,
                        'student_id' => $studentData['student_id'],
                        'attendance_date' => $data['attendance_date'],
                    ],
                    [
                        'academic_year_id' => $data['academic_year_id'],
                        'class_id' => $data['class_id'],
                        'section_id' => $data['section_id'],
                        'status' => $studentData['status'],
                        'remarks' => $studentData['remarks'] ?? null,
                        'marked_by' => $markedByUserId,
                        'is_delete' => 0 // In case it was soft deleted previously
                    ]
                );

                $records[] = $record;
            }
        });

        return $records;
    }
}
