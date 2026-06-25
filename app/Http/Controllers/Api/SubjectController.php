<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\AcademicYear;
use App\Http\Requests\Subject\StoreSubjectRequest;
use App\Http\Requests\Subject\UpdateSubjectRequest;
use App\Services\SubjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    protected SubjectService $subjectService;

    public function __construct(SubjectService $subjectService)
    {
        $this->subjectService = $subjectService;
    }

    /**
     * Display a listing of subjects.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('subject.view');

        $currentUser = $request->user();
        $query = Subject::query()->with(['academicYear', 'class', 'section']);

        // Enforce school isolation
        if (!$currentUser->isSuperAdmin()) {
            $query->where('school_id', $currentUser->school_id);
        } elseif ($request->has('school_id')) {
            $query->where('school_id', $request->input('school_id'));
        }

        // Exclude deleted subjects
        $query->where('is_delete', 0);

        // Filters
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
            $query->where('academic_year_id', $academicYearId);
        }
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->input('class_id'));
        }
        if ($request->filled('section_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('section_id', $request->input('section_id'))
                  ->orWhereNull('section_id');
            });
        }
        if ($request->filled('exam_id')) {
            $query->whereIn('id', function ($q) use ($request) {
                $q->select('subject_id')
                  ->from('exam_schedules')
                  ->where('exam_id', $request->input('exam_id'))
                  ->where('is_delete', 0);
            });
        }

        // Search support
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Filter status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Pagination or All (for dropdowns)
        if ($request->boolean('all')) {
            $subjects = $query->orderBy('name', 'asc')->get();
            return response()->json(['subjects' => $subjects]);
        }

        $perPage = $request->input('per_page', 10);
        $subjects = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($subjects);
    }

    /**
     * Store a newly created subject.
     */
    public function store(StoreSubjectRequest $request): JsonResponse
    {
        $this->authorize('subject.create');

        $currentUser = $request->user();
        $schoolId = $currentUser->isSuperAdmin()
            ? $request->input('school_id')
            : $currentUser->school_id;

        if (!$schoolId) {
            return response()->json(['message' => 'School ID is required.'], 422);
        }

        $subject = $this->subjectService->createSubject($request->validated(), $schoolId);

        return response()->json([
            'message' => 'Subject created successfully',
            'subject' => $subject
        ], 201);
    }

    /**
     * Display the specified subject.
     */
    public function show(Subject $subject, Request $request): JsonResponse
    {
        $this->authorize('subject.view');

        $currentUser = $request->user();
        if (!$currentUser->isSuperAdmin() && ($currentUser->school_id !== $subject->school_id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        return response()->json([
            'subject' => $subject
        ]);
    }

    /**
     * Update the specified subject.
     */
    public function update(UpdateSubjectRequest $request, Subject $subject): JsonResponse
    {
        $this->authorize('subject.edit');

        $currentUser = $request->user();
        if (!$currentUser->isSuperAdmin() && ($currentUser->school_id !== $subject->school_id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $updated = $this->subjectService->updateSubject($subject, $request->validated());

        return response()->json([
            'message' => 'Subject updated successfully',
            'subject' => $updated
        ]);
    }

    /**
     * Toggle subject status.
     */
    public function toggleStatus(Subject $subject, Request $request): JsonResponse
    {
        $this->authorize('subject.edit');

        $currentUser = $request->user();
        if (!$currentUser->isSuperAdmin() && ($currentUser->school_id !== $subject->school_id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $newStatus = $subject->status === 'active' ? 'inactive' : 'active';
        $subject->update(['status' => $newStatus]);

        return response()->json([
            'message' => 'Subject status updated successfully.',
            'subject' => $subject
        ]);
    }

    /**
     * Remove the specified subject.
     */
    public function destroy(Subject $subject, Request $request): JsonResponse
    {
        $this->authorize('subject.delete');

        $currentUser = $request->user();
        if (!$currentUser->isSuperAdmin() && ($currentUser->school_id !== $subject->school_id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $subject->update(['is_delete' => 1]);

        return response()->json([
            'message' => 'Subject deleted successfully'
        ]);
    }
}
