<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\AcademicYear;
use App\Http\Requests\Academic\StoreClassRequest;
use App\Http\Requests\Academic\UpdateClassRequest;
use App\Services\ClassService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    protected ClassService $classService;

    public function __construct(ClassService $classService)
    {
        $this->classService = $classService;
    }

    /**
     * Display a listing of classes.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('class.view');

        $currentUser = $request->user();
        $query = ClassModel::with(['academicYear']);

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
            if (!$academicYearId && !$currentUser->isSuperAdmin()) {
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
        }

        if ($request->input('status') === 'active') {
            $query->where('status', 'active');
        }
        $query->where('is_delete', 0);

        return response()->json([
            'classes' => $query->orderBy('name', 'asc')->get()
        ]);
    }

    /**
     * Store a newly created class.
     */
    public function store(StoreClassRequest $request): JsonResponse
    {
        $this->authorize('class.create');

        $currentUser = $request->user();
        $schoolId = $currentUser->isSuperAdmin()
            ? $request->input('school_id')
            : $currentUser->school_id;

        if (!$schoolId) {
            return response()->json(['message' => 'School ID is required.'], 422);
        }

        $class = $this->classService->createClass($request->validated(), $schoolId);

        return response()->json([
            'message' => 'Class created successfully',
            'class' => $class->load('academicYear')
        ], 201);
    }

    /**
     * Display the specified class.
     */
    public function show(ClassModel $class, Request $request): JsonResponse
    {
        $this->authorize('class.view');

        $currentUser = $request->user();

        // Enforce school boundaries
        if (!$currentUser->isSuperAdmin() && ($currentUser->school_id !== $class->school_id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        return response()->json([
            'class' => $class->load('academicYear')
        ]);
    }

    /**
     * Update the specified class.
     */
    public function update(UpdateClassRequest $request, ClassModel $class): JsonResponse
    {
        $this->authorize('class.edit');

        $currentUser = $request->user();

        // Enforce school boundaries
        if (!$currentUser->isSuperAdmin() && ($currentUser->school_id !== $class->school_id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $updated = $this->classService->updateClass($class, $request->validated());

        return response()->json([
            'message' => 'Class updated successfully',
            'class' => $updated->load('academicYear')
        ]);
    }

    /**
     * Remove the specified class.
     */
    public function destroy(ClassModel $class, Request $request): JsonResponse
    {
        $this->authorize('class.delete');

        $currentUser = $request->user();

        // Enforce school boundaries
        if (!$currentUser->isSuperAdmin() && ($currentUser->school_id !== $class->school_id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $class->update(['is_delete' => 1]);

        return response()->json([
            'message' => 'Class deleted successfully'
        ]);
    }
}
