<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\AcademicYear;
use App\Http\Requests\Notice\StoreNoticeRequest;
use App\Http\Requests\Notice\UpdateNoticeRequest;
use App\Services\NoticeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NoticeController extends Controller
{
    protected NoticeService $noticeService;

    public function __construct(NoticeService $noticeService)
    {
        $this->noticeService = $noticeService;
    }

    /**
     * Display a listing of notices.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('notice.view');

        $currentUser = $request->user();
        $query = Notice::with(['class', 'section', 'creator']);

        // Enforce tenancy
        if (!$currentUser->isSuperAdmin()) {
            $query->where('school_id', $currentUser->school_id);
        } elseif ($request->has('school_id')) {
            $query->where('school_id', $request->input('school_id'));
        }

        // Active/deleted
        $query->where('is_delete', 0);

        // Filter by academic year of the class if class exists
        $academicYearId = $request->input('academic_year_id');
        if (!$academicYearId && !$request->filled('class_id') && !$request->filled('section_id') && !$currentUser->isSuperAdmin()) {
            $activeYear = AcademicYear::where('school_id', $currentUser->school_id)
                ->where('is_current', true)
                ->where('is_delete', 0)
                ->first();
            if ($activeYear) {
                $academicYearId = $activeYear->id;
            }
        }

        if ($academicYearId) {
            $query->where(function ($q) use ($academicYearId) {
                $q->whereNull('class_id')
                  ->orWhereHas('class', function ($q2) use ($academicYearId) {
                      $q2->where('academic_year_id', $academicYearId);
                  });
            });
        }

        // Filters
        if ($request->filled('target_type')) {
            $query->where('target_type', $request->input('target_type'));
        }
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->input('class_id'));
        }
        if ($request->filled('section_id')) {
            $query->where('section_id', $request->input('section_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('title', 'like', "%{$search}%");
        }

        $perPage = $request->input('per_page', 10);
        $notices = $query->orderBy('notice_date', 'desc')->paginate($perPage);

        return response()->json($notices);
    }

    /**
     * Store a newly created notice.
     */
    public function store(StoreNoticeRequest $request): JsonResponse
    {
        $this->authorize('notice.create');

        $currentUser = $request->user();
        $schoolId = $currentUser->isSuperAdmin()
            ? $request->input('school_id')
            : $currentUser->school_id;

        if (!$schoolId) {
            return response()->json(['message' => 'School ID is required.'], 422);
        }

        $notice = $this->noticeService->createNotice(
            $request->validated(),
            $schoolId,
            $currentUser->id
        );

        return response()->json([
            'message' => 'Notice published successfully.',
            'notice' => $notice->load(['class', 'section', 'creator'])
        ], 201);
    }

    /**
     * Display the specified notice.
     */
    public function show(Notice $notice, Request $request): JsonResponse
    {
        $this->authorize('notice.view');

        $currentUser = $request->user();
        if (!$currentUser->isSuperAdmin() && ($currentUser->school_id !== $notice->school_id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        return response()->json([
            'notice' => $notice->load(['class', 'section', 'creator'])
        ]);
    }

    /**
     * Update the specified notice.
     */
    public function update(UpdateNoticeRequest $request, Notice $notice): JsonResponse
    {
        $this->authorize('notice.edit');

        $currentUser = $request->user();
        if (!$currentUser->isSuperAdmin() && ($currentUser->school_id !== $notice->school_id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $updated = $this->noticeService->updateNotice($notice, $request->validated());

        return response()->json([
            'message' => 'Notice updated successfully.',
            'notice' => $updated->load(['class', 'section', 'creator'])
        ]);
    }

    /**
     * Remove the specified notice.
     */
    public function destroy(Notice $notice, Request $request): JsonResponse
    {
        $this->authorize('notice.delete');

        $currentUser = $request->user();
        if (!$currentUser->isSuperAdmin() && ($currentUser->school_id !== $notice->school_id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        // Delete attachment from storage
        if ($notice->attachment) {
            Storage::disk('public')->delete($notice->attachment);
        }

        $notice->update(['is_delete' => 1]);

        return response()->json([
            'message' => 'Notice deleted successfully.'
        ]);
    }
}
