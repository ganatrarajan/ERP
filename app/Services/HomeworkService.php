<?php

namespace App\Services;

use App\Models\Homework;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class HomeworkService
{
    /**
     * Create a new Homework.
     */
    public function createHomework(array $data, int $schoolId, int $userId): Homework
    {
        $attachmentPath = null;
        if (isset($data['attachment']) && $data['attachment'] instanceof UploadedFile) {
            $attachmentPath = $data['attachment']->store('homeworks', 'public');
        }

        return Homework::create([
            'school_id' => $schoolId,
            'academic_year_id' => $data['academic_year_id'],
            'class_id' => $data['class_id'],
            'section_id' => $data['section_id'],
            'subject_id' => $data['subject_id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'submission_date' => $data['submission_date'],
            'attachment' => $attachmentPath,
            'created_by' => $userId,
            'status' => $data['status'] ?? 'active',
        ]);
    }

    /**
     * Update an existing Homework.
     */
    public function updateHomework(Homework $homework, array $data): Homework
    {
        $attachmentPath = $homework->attachment;

        if (isset($data['attachment'])) {
            if ($data['attachment'] instanceof UploadedFile) {
                // Delete old attachment if exists
                if ($homework->attachment) {
                    Storage::disk('public')->delete($homework->attachment);
                }
                $attachmentPath = $data['attachment']->store('homeworks', 'public');
            } elseif (is_string($data['attachment'])) {
                // Keep the old one
                $attachmentPath = $data['attachment'];
            }
        } else {
            // If explicitly cleared
            if (array_key_exists('attachment', $data)) {
                if ($homework->attachment) {
                    Storage::disk('public')->delete($homework->attachment);
                }
                $attachmentPath = null;
            }
        }

        $homework->update([
            'academic_year_id' => $data['academic_year_id'],
            'class_id' => $data['class_id'],
            'section_id' => $data['section_id'],
            'subject_id' => $data['subject_id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'submission_date' => $data['submission_date'],
            'attachment' => $attachmentPath,
            'status' => $data['status'] ?? $homework->status,
        ]);

        return $homework;
    }
}
