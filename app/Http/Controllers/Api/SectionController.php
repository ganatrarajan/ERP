<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\AcademicYear;
use App\Http\Requests\Academic\StoreSectionRequest;
use App\Http\Requests\Academic\UpdateSectionRequest;
use App\Services\SectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    protected SectionService $sectionService;

    public function __construct(SectionService $sectionService)
    {
        $this->sectionService = $sectionService;
    }

    /**
     * Display a listing of sections.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('section.view');

        $currentUser = $request->user();
        $query = Section::with(['class.academicYear']);

        // Scope to school
        if (!$currentUser->isSuperAdmin()) {
            $query->where('school_id', $currentUser->school_id);
        } elseif ($request->has('school_id')) {
            $query->where('school_id', $request->input('school_id'));
        }

        // Filter by Academic Year
        $academicYearId = $request->input('academic_year_id');
        $ignoreAcademicYear = $request->boolean('ignore_academic_year');

        if (!$ignoreAcademicYear) {
            if (!$academicYearId && !$request->has('class_id') && !$currentUser->isSuperAdmin()) {
                $activeYear = AcademicYear::where('school_id', $currentUser->school_id)
                    ->where('is_current', true)
                    ->where('is_delete', 0)
                    ->first();
                if ($activeYear) {
                    $academicYearId = $activeYear->id;
                }
            }

            if ($academicYearId) {
                $query->whereHas('class', function ($q) use ($academicYearId) {
                    $q->where('academic_year_id', $academicYearId);
                });
            }
        }

        // Filter by class_id
        if ($request->has('class_id')) {
            $query->where('class_id', $request->input('class_id'));
        }

        if ($request->input('status') === 'active') {
            $query->where('status', 'active');
        }
        $query->where('is_delete', 0);

        // Parent class must be active and not deleted
        $query->whereHas('class', function ($q) {
            $q->where('status', 'active')
              ->where('is_delete', 0);
        });

        // Associated academic year must be active and not deleted
        $query->whereHas('class.academicYear', function ($q) {
            $q->where('status', 'active')
              ->where('is_delete', 0);
        });

        return response()->json([
            'sections' => $query->orderBy('name', 'asc')->get()
        ]);
    }

    /**
     * Store a newly created section.
     */
    public function store(StoreSectionRequest $request): JsonResponse
    {
        $this->authorize('section.create');

        $currentUser = $request->user();
        $schoolId = $currentUser->isSuperAdmin()
            ? $request->input('school_id')
            : $currentUser->school_id;

        if (!$schoolId) {
            return response()->json(['message' => 'School ID is required.'], 422);
        }

        $section = $this->sectionService->createSection($request->validated(), $schoolId);

        return response()->json([
            'message' => 'Section created successfully',
            'section' => $section->load('class.academicYear')
        ], 201);
    }

    /**
     * Display the specified section.
     */
    public function show(Section $section, Request $request): JsonResponse
    {
        $this->authorize('section.view');

        $currentUser = $request->user();

        // Enforce school boundaries
        if (!$currentUser->isSuperAdmin() && ($currentUser->school_id !== $section->school_id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        return response()->json([
            'section' => $section->load('class.academicYear')
        ]);
    }

    /**
     * Update the specified section.
     */
    public function update(UpdateSectionRequest $request, Section $section): JsonResponse
    {
        $this->authorize('section.edit');

        $currentUser = $request->user();

        // Enforce school boundaries
        if (!$currentUser->isSuperAdmin() && ($currentUser->school_id !== $section->school_id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $updated = $this->sectionService->updateSection($section, $request->validated());

        return response()->json([
            'message' => 'Section updated successfully',
            'section' => $updated->load('class.academicYear')
        ]);
    }

    /**
     * Remove the specified section.
     */
    public function destroy(Section $section, Request $request): JsonResponse
    {
        $this->authorize('section.delete');

        $currentUser = $request->user();

        // Enforce school boundaries
        if (!$currentUser->isSuperAdmin() && ($currentUser->school_id !== $section->school_id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $section->update(['is_delete' => 1]);

        return response()->json([
            'message' => 'Section deleted successfully'
        ]);
    }
}
