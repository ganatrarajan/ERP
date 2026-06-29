<?php

namespace App\Services;

use App\Models\Homework;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
class HomeworkService
{
    protected FcmService $fcmService;

    public function __construct(FcmService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Create a new Homework.
     */
    public function createHomework(array $data, int $schoolId, int $userId): Homework
    {
        $attachmentPath = null;
        if (isset($data['attachment']) && $data['attachment'] instanceof UploadedFile) {
            $attachmentPath = $data['attachment']->store('homeworks', 'public');
        }

        $homework = Homework::create([
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

        try {
            $homework->load('subject');
            $subjectName = $homework->subject ? $homework->subject->name : 'N/A';
            $title = "New Homework: " . $homework->title;
            $body = "New assignment for " . $subjectName . ". Submission Date: " . $homework->submission_date;
            
            $this->fcmService->sendToClass(
                $schoolId,
                $homework->class_id,
                $homework->section_id,
                $title,
                $body,
                [
                    'type' => 'homework',
                    'id' => $homework->id,
                ],
                $homework->academic_year_id
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("FCM Homework Notification Error: " . $e->getMessage());
        }

        return $homework;
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

        try {
            $homework->load('subject');
            $subjectName = $homework->subject ? $homework->subject->name : 'N/A';
            $title = "Homework Updated: " . $homework->title;
            $body = "Homework updated for " . $subjectName . ". Submission Date: " . $homework->submission_date;
            
            $this->fcmService->sendToClass(
                $homework->school_id,
                $homework->class_id,
                $homework->section_id,
                $title,
                $body,
                [
                    'type' => 'homework',
                    'id' => $homework->id,
                ],
                $homework->academic_year_id
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("FCM Homework Notification Error: " . $e->getMessage());
        }

        return $homework;
    }
}
