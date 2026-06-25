<?php

namespace App\Services;

use App\Models\Notice;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class NoticeService
{
    /**
     * Create a new Notice.
     */
    public function createNotice(array $data, int $schoolId, int $userId): Notice
    {
        $attachmentPath = null;
        if (isset($data['attachment']) && $data['attachment'] instanceof UploadedFile) {
            $attachmentPath = $data['attachment']->store('notices', 'public');
        }

        // Clean up class and section depending on target type
        $classId = null;
        $sectionId = null;
        if ($data['target_type'] === 'Class Wise' || $data['target_type'] === 'Section Wise') {
            $classId = $data['class_id'] ?? null;
        }
        if ($data['target_type'] === 'Section Wise') {
            $sectionId = $data['section_id'] ?? null;
        }

        return Notice::create([
            'school_id' => $schoolId,
            'title' => $data['title'],
            'description' => $data['description'],
            'notice_date' => $data['notice_date'],
            'target_type' => $data['target_type'],
            'class_id' => $classId,
            'section_id' => $sectionId,
            'attachment' => $attachmentPath,
            'created_by' => $userId,
            'status' => $data['status'] ?? 'active',
        ]);
    }

    /**
     * Update an existing Notice.
     */
    public function updateNotice(Notice $notice, array $data): Notice
    {
        $attachmentPath = $notice->attachment;

        if (isset($data['attachment'])) {
            if ($data['attachment'] instanceof UploadedFile) {
                // Delete old attachment if exists
                if ($notice->attachment) {
                    Storage::disk('public')->delete($notice->attachment);
                }
                $attachmentPath = $data['attachment']->store('notices', 'public');
            } elseif (is_string($data['attachment'])) {
                $attachmentPath = $data['attachment'];
            }
        } else {
            if (array_key_exists('attachment', $data)) {
                if ($notice->attachment) {
                    Storage::disk('public')->delete($notice->attachment);
                }
                $attachmentPath = null;
            }
        }

        // Clean up class and section depending on target type
        $classId = null;
        $sectionId = null;
        if ($data['target_type'] === 'Class Wise' || $data['target_type'] === 'Section Wise') {
            $classId = $data['class_id'] ?? null;
        }
        if ($data['target_type'] === 'Section Wise') {
            $sectionId = $data['section_id'] ?? null;
        }

        $notice->update([
            'title' => $data['title'],
            'description' => $data['description'],
            'notice_date' => $data['notice_date'],
            'target_type' => $data['target_type'],
            'class_id' => $classId,
            'section_id' => $sectionId,
            'attachment' => $attachmentPath,
            'status' => $data['status'] ?? $notice->status,
        ]);

        return $notice;
    }
}
