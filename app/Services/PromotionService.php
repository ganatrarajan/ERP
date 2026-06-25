<?php

namespace App\Services;

use App\Models\StudentAcademicRecord;
use App\Models\PromotionLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PromotionService
{
    /**
     * Promote a batch of students to the next academic year class/section.
     */
    public function promoteStudents(array $data, int $schoolId, int $promotedByUserId): void
    {
        DB::transaction(function () use ($data, $schoolId, $promotedByUserId) {
            $studentIds = $data['student_ids']; // array of student IDs
            $fromYearId = $data['from_academic_year_id'];
            $fromClassId = $data['from_class_id'];
            $fromSectionId = $data['from_section_id'];

            $toYearId = $data['to_academic_year_id'];
            $toClassId = $data['to_class_id'];
            $toSectionId = $data['to_section_id'];

            $promotedAt = Carbon::now();

            foreach ($studentIds as $studentId) {
                // 1. Create/update the academic record for the destination academic year
                StudentAcademicRecord::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'academic_year_id' => $toYearId
                    ],
                    [
                        'school_id' => $schoolId,
                        'class_id' => $toClassId,
                        'section_id' => $toSectionId,
                        'roll_no' => null, // Clear/unset roll number for the new year to assign later
                        'status' => 'active',
                    ]
                );

                // Apply active holidays in the promoted class/section/year
                \App\Services\HolidayService::applyHolidaysToNewStudent(
                    $schoolId,
                    $toYearId,
                    $studentId,
                    $toClassId,
                    $toSectionId
                );

                // 2. Log the promotion
                PromotionLog::create([
                    'school_id' => $schoolId,
                    'student_id' => $studentId,
                    'from_academic_year_id' => $fromYearId,
                    'from_class_id' => $fromClassId,
                    'from_section_id' => $fromSectionId,
                    'to_academic_year_id' => $toYearId,
                    'to_class_id' => $toClassId,
                    'to_section_id' => $toSectionId,
                    'promoted_by' => $promotedByUserId,
                    'promoted_at' => $promotedAt,
                ]);
            }
        });
    }
}
