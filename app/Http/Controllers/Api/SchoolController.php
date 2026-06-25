<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Http\Requests\Schools\StoreSchoolRequest;
use App\Http\Requests\Schools\UpdateSchoolRequest;
use App\Services\SchoolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    protected SchoolService $schoolService;

    public function __construct(SchoolService $schoolService)
    {
        $this->schoolService = $schoolService;
    }

    /**
     * Display a listing of schools.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('school.view');

        $query = School::query();
        $query->where('status', 'active');
        if (\Illuminate\Support\Facades\Schema::hasColumn('schools', 'is_delete')) {
            $query->where('is_delete', 0);
        }

        $schools = $query->withCount('users')->get();

        return response()->json([
            'schools' => $schools
        ]);
    }

    /**
     * Store a newly created school.
     */
    public function store(StoreSchoolRequest $request): JsonResponse
    {
        $school = $this->schoolService->createSchool($request->validated());

        return response()->json([
            'message' => 'School and School Admin created successfully',
            'school' => $school
        ], 210); // Using 201 Created is fine but let's use 201 standard status code
    }

    /**
     * Display the specified school.
     */
    public function show(School $school, Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Authorize: school.view OR own school
        if (!$user->can('school.view') && ($user->school_id !== $school->id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        return response()->json([
            'school' => $school->loadCount('users')
        ]);
    }

    /**
     * Update the specified school.
     */
    public function update(UpdateSchoolRequest $request, School $school): JsonResponse
    {
        // Enforce School Admin restriction on updating status
        $data = $request->validated();
        if ($request->user()->hasRole('School Admin')) {
            // School admin cannot deactivate their own school
            unset($data['status']);
        }

        $updatedSchool = $this->schoolService->updateSchool($school, $data);

        return response()->json([
            'message' => 'School updated successfully',
            'school' => $updatedSchool
        ]);
    }

    /**
     * Remove the specified school.
     */
    public function destroy(School $school, Request $request): JsonResponse
    {
        if (!$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $this->schoolService->deleteSchool($school);

        return response()->json([
            'message' => 'School deleted successfully'
        ]);
    }

    /**
     * Upload school logo and save it locally.
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'school_id' => 'nullable|integer',
        ]);

        $currentUser = $request->user();
        $schoolId = !$currentUser->isSuperAdmin()
            ? $currentUser->school_id
            : ($request->input('school_id') ?: 1);

        $file = $request->file('logo');
        
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $cleanName = preg_replace('/[^A-Za-z0-9\-]/', '_', $originalName);
        $filename = time() . '_' . $cleanName . '.' . $file->getClientOriginalExtension();
        
        $destinationPath = public_path("uploads/schools/{$schoolId}");
        
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }
        
        $file->move($destinationPath, $filename);
        
        $url = url("uploads/schools/{$schoolId}/{$filename}");
        
        return response()->json([
            'url' => $url,
            'path' => "uploads/schools/{$schoolId}/{$filename}"
        ], 200);
    }
}
