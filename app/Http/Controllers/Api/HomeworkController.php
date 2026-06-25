<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Homework;
use App\Models\AcademicYear;
use App\Http\Requests\Homework\StoreHomeworkRequest;
use App\Http\Requests\Homework\UpdateHomeworkRequest;
use App\Services\HomeworkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeworkController extends Controller
{
    protected HomeworkService $homeworkService;

    public function __construct(HomeworkService $homeworkService)
    {
        $this->homeworkService = $homeworkService;
    }

    /**
     * Display a listing of homework.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('homework.view');

        $currentUser = $request->user();
        $query = Homework::query()->with(['academicYear', 'class', 'section', 'subject', 'creator']);

        // Enforce school isolation
        if (!$currentUser->isSuperAdmin()) {
            $query->where('school_id', $currentUser->school_id);
        } elseif ($request->has('school_id')) {
            $query->where('school_id', $request->input('school_id'));
        }

        // Exclude deleted homeworks
        $query->where('is_delete', 0);

        // Filters
        $academicYearId = $request->input('academic_year_id');
        if (!$academicYearId && !$request->filled('class_id') && !$request->filled('section_id') && !$request->filled('subject_id') && !$currentUser->isSuperAdmin()) {
            $activeYear = AcademicYear::where('school_id', $currentUser->school_id)
                ->where('is_current', true)
                ->where('is_delete', 0)
                ->first();
            if ($activeYear) {
                $academicYearId = $activeYear->id;
            }
        }

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->input('class_id'));
        }
        if ($request->filled('section_id')) {
            $query->where('section_id', $request->input('section_id'));
        }
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->input('subject_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Search support
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 10);
        $homeworks = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($homeworks);
    }

    /**
     * Store a newly created homework.
     */
    public function store(StoreHomeworkRequest $request): JsonResponse
    {
        $this->authorize('homework.create');

        $currentUser = $request->user();
        $schoolId = $currentUser->isSuperAdmin()
            ? $request->input('school_id')
            : $currentUser->school_id;

        if (!$schoolId) {
            return response()->json(['message' => 'School ID is required.'], 422);
        }

        $homework = $this->homeworkService->createHomework(
            $request->validated(), 
            $schoolId, 
            $currentUser->id
        );

        // Load relationships
        $homework->load(['academicYear', 'class', 'section', 'subject', 'creator']);

        return response()->json([
            'message' => 'Homework created successfully',
            'homework' => $homework
        ], 201);
    }

    /**
     * Display the specified homework.
     */
    public function show(Homework $homework, Request $request): JsonResponse
    {
        $this->authorize('homework.view');

        $currentUser = $request->user();
        if (!$currentUser->isSuperAdmin() && ($currentUser->school_id !== $homework->school_id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        if ($homework->is_delete) {
            return response()->json(['message' => 'Homework not found.'], 404);
        }

        $homework->load(['academicYear', 'class', 'section', 'subject', 'creator']);

        return response()->json([
            'homework' => $homework
        ]);
    }

    /**
     * Update the specified homework.
     */
    public function update(UpdateHomeworkRequest $request, Homework $homework): JsonResponse
    {
        $this->authorize('homework.edit');

        $currentUser = $request->user();
        if (!$currentUser->isSuperAdmin() && ($currentUser->school_id !== $homework->school_id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        if ($homework->is_delete) {
            return response()->json(['message' => 'Homework not found.'], 404);
        }

        $updated = $this->homeworkService->updateHomework($homework, $request->validated());
        $updated->load(['academicYear', 'class', 'section', 'subject', 'creator']);

        return response()->json([
            'message' => 'Homework updated successfully',
            'homework' => $updated
        ]);
    }

    /**
     * Remove the specified homework.
     */
    public function destroy(Homework $homework, Request $request): JsonResponse
    {
        $this->authorize('homework.delete');

        $currentUser = $request->user();
        if (!$currentUser->isSuperAdmin() && ($currentUser->school_id !== $homework->school_id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        if ($homework->is_delete) {
            return response()->json(['message' => 'Homework already deleted.'], 404);
        }

        if ($homework->attachment) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($homework->attachment);
        }

        $homework->update([
            'is_delete' => 1,
            'attachment' => null
        ]);

        return response()->json([
            'message' => 'Homework deleted successfully'
        ]);
    }
}
