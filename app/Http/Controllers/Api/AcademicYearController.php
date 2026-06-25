<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Http\Requests\Academic\StoreAcademicYearRequest;
use App\Http\Requests\Academic\UpdateAcademicYearRequest;
use App\Services\AcademicYearService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    protected AcademicYearService $academicYearService;

    public function __construct(AcademicYearService $academicYearService)
    {
        $this->academicYearService = $academicYearService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('academic_year.view');

        $currentUser = $request->user();
        $query = AcademicYear::query();

        // Scope to school
        if (!$currentUser->isSuperAdmin()) {
            $query->where('school_id', $currentUser->school_id);
        } elseif ($request->has('school_id')) {
            $query->where('school_id', $request->input('school_id'));
        }

        if ($request->input('status') === 'active') {
            $query->where('status', 'active');
        }
        $query->where('is_delete', 0);

        return response()->json([
            'academic_years' => $query->orderBy('start_date', 'desc')->get()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAcademicYearRequest $request): JsonResponse
    {
        $this->authorize('academic_year.create');

        $currentUser = $request->user();
        $schoolId = $currentUser->isSuperAdmin()
            ? $request->input('school_id')
            : $currentUser->school_id;

        if (!$schoolId) {
            return response()->json(['message' => 'School ID is required.'], 422);
        }

        $academicYear = $this->academicYearService->createAcademicYear($request->validated(), $schoolId);

        return response()->json([
            'message' => 'Academic Year created successfully',
            'academic_year' => $academicYear
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(AcademicYear $academicYear, Request $request): JsonResponse
    {
        $this->authorize('academic_year.view');

        $currentUser = $request->user();

        // Enforce school boundaries
        if (!$currentUser->isSuperAdmin() && ($currentUser->school_id !== $academicYear->school_id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        return response()->json([
            'academic_year' => $academicYear
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAcademicYearRequest $request, AcademicYear $academicYear): JsonResponse
    {
        $this->authorize('academic_year.edit');

        $currentUser = $request->user();

        // Enforce school boundaries
        if (!$currentUser->isSuperAdmin() && ($currentUser->school_id !== $academicYear->school_id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $updated = $this->academicYearService->updateAcademicYear($academicYear, $request->validated());

        return response()->json([
            'message' => 'Academic Year updated successfully',
            'academic_year' => $updated
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AcademicYear $academicYear, Request $request): JsonResponse
    {
        $this->authorize('academic_year.delete');

        $currentUser = $request->user();

        // Enforce school boundaries
        if (!$currentUser->isSuperAdmin() && ($currentUser->school_id !== $academicYear->school_id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $academicYear->update(['is_delete' => 1]);

        return response()->json([
            'message' => 'Academic Year deleted successfully'
        ]);
    }
}
