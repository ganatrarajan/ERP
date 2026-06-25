<?php

namespace App\Services;

use App\Models\Subject;

class SubjectService
{
    /**
     * Create a new Subject.
     */
    public function createSubject(array $data, int $schoolId): Subject
    {
        return Subject::create([
            'school_id' => $schoolId,
            'academic_year_id' => $data['academic_year_id'],
            'class_id' => $data['class_id'],
            'section_id' => $data['section_id'] ?? null,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'active',
            'is_optional' => $data['is_optional'] ?? false,
            'evaluation_type' => $data['evaluation_type'] ?? 'marks',
            'subject_category' => $data['subject_category'] ?? 'scholastic',
            'maximum_marks' => $data['maximum_marks'] ?? 100,
            'passing_marks' => $data['passing_marks'] ?? 35,
            'grade_scale_id' => $data['grade_scale_id'] ?? null,
        ]);
    }

    /**
     * Update a Subject.
     */
    public function updateSubject(Subject $subject, array $data): Subject
    {
        $subject->update([
            'academic_year_id' => $data['academic_year_id'] ?? $subject->academic_year_id,
            'class_id' => $data['class_id'] ?? $subject->class_id,
            'section_id' => array_key_exists('section_id', $data) ? $data['section_id'] : $subject->section_id,
            'name' => $data['name'],
            'code' => $data['code'] ?? $subject->code,
            'description' => $data['description'] ?? $subject->description,
            'status' => $data['status'] ?? $subject->status,
            'is_optional' => array_key_exists('is_optional', $data) ? $data['is_optional'] : $subject->is_optional,
            'evaluation_type' => $data['evaluation_type'] ?? $subject->evaluation_type,
            'subject_category' => $data['subject_category'] ?? $subject->subject_category,
            'maximum_marks' => $data['maximum_marks'] ?? $subject->maximum_marks,
            'passing_marks' => $data['passing_marks'] ?? $subject->passing_marks,
            'grade_scale_id' => array_key_exists('grade_scale_id', $data) ? $data['grade_scale_id'] : $subject->grade_scale_id,
        ]);

        return $subject;
    }
}
