<?php

namespace App\Services;

use App\Models\Notice;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
class NoticeService
{
    protected FcmService $fcmService;

    public function __construct(FcmService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

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

        $notice = Notice::create([
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

        try {
            $title = "New Notice: " . $notice->title;
            $body = strip_tags($notice->description);
            if (strlen($body) > 120) {
                $body = substr($body, 0, 117) . "...";
            }

            $dataPayload = [
                'type' => 'notice',
                'id' => $notice->id,
            ];

            if ($notice->target_type === 'Class Wise' || $notice->target_type === 'Section Wise') {
                $this->fcmService->sendToClass($schoolId, $notice->class_id, $notice->section_id, $title, $body, $dataPayload);
            } else {
                $this->fcmService->sendToSchool($schoolId, $title, $body, $dataPayload);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("FCM Notice Notification Error: " . $e->getMessage());
        }

        return $notice;
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

        try {
            $title = "Notice Updated: " . $notice->title;
            $body = strip_tags($notice->description);
            if (strlen($body) > 120) {
                $body = substr($body, 0, 117) . "...";
            }

            $dataPayload = [
                'type' => 'notice',
                'id' => $notice->id,
            ];

            if ($notice->target_type === 'Class Wise' || $notice->target_type === 'Section Wise') {
                $this->fcmService->sendToClass($notice->school_id, $notice->class_id, $notice->section_id, $title, $body, $dataPayload);
            } else {
                $this->fcmService->sendToSchool($notice->school_id, $title, $body, $dataPayload);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("FCM Notice Notification Error: " . $e->getMessage());
        }

        return $notice;
    }
}
