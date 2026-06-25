<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GradeScale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GradeScaleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('exam.view');

        $user = $request->user();
        $query = GradeScale::query();

        if (!$user->isSuperAdmin()) {
            $query->where('school_id', $user->school_id);
        } elseif ($request->has('school_id')) {
            $query->where('school_id', $request->school_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('grade', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->boolean('all')) {
            $gradeScales = $query->orderBy('min_percentage', 'desc')->get();
            return response()->json(['grade_scales' => $gradeScales]);
        }

        $perPage = $request->input('per_page', 10);
        $gradeScales = $query->orderBy('min_percentage', 'desc')->paginate($perPage);

        return response()->json($gradeScales);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('exam.create');

        $user = $request->user();
        $schoolId = $user->isSuperAdmin() ? $request->school_id : $user->school_id;

        if (!$schoolId) {
            return response()->json(['message' => 'School ID is required.'], 422);
        }

        $data = $request->validate([
            'grade' => 'required|string|max:10',
            'description' => 'nullable|string|max:255',
            'grade_point' => 'required|numeric|min:0|max:100',
            'min_percentage' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        $data['school_id'] = $schoolId;

        $gradeScale = GradeScale::create($data);

        return response()->json([
            'message' => 'Grade scale created successfully.',
            'grade_scale' => $gradeScale
        ], 201);
    }

    public function show(GradeScale $gradeScale, Request $request): JsonResponse
    {
        $this->authorize('exam.view');
        
        $user = $request->user();
        if (!$user->isSuperAdmin() && $gradeScale->school_id !== $user->school_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json(['grade_scale' => $gradeScale]);
    }

    public function update(Request $request, GradeScale $gradeScale): JsonResponse
    {
        $this->authorize('exam.edit');

        $user = $request->user();
        if (!$user->isSuperAdmin() && $gradeScale->school_id !== $user->school_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $data = $request->validate([
            'grade' => 'required|string|max:10',
            'description' => 'nullable|string|max:255',
            'grade_point' => 'required|numeric|min:0|max:100',
            'min_percentage' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        $gradeScale->update($data);

        return response()->json([
            'message' => 'Grade scale updated successfully.',
            'grade_scale' => $gradeScale
        ]);
    }

    public function toggleStatus(GradeScale $gradeScale, Request $request): JsonResponse
    {
        $this->authorize('exam.edit');

        $user = $request->user();
        if (!$user->isSuperAdmin() && $gradeScale->school_id !== $user->school_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $gradeScale->status = $gradeScale->status === 'active' ? 'inactive' : 'active';
        $gradeScale->save();

        return response()->json([
            'message' => 'Grade scale status toggled successfully.',
            'grade_scale' => $gradeScale
        ]);
    }

    public function destroy(GradeScale $gradeScale, Request $request): JsonResponse
    {
        $this->authorize('exam.delete');

        $user = $request->user();
        if (!$user->isSuperAdmin() && $gradeScale->school_id !== $user->school_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $gradeScale->delete();

        return response()->json([
            'message' => 'Grade scale deleted successfully.'
        ]);
    }
}
