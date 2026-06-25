<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('exam.view');

        $user = $request->user();
        $query = ExamType::query()->where('is_delete', 0);

        if (!$user->isSuperAdmin()) {
            $query->where('school_id', $user->school_id);
        } elseif ($request->has('school_id')) {
            $query->where('school_id', $request->school_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->boolean('all')) {
            $examTypes = $query->orderBy('name', 'asc')->get();
            return response()->json(['exam_types' => $examTypes]);
        }

        $perPage = $request->input('per_page', 10);
        $examTypes = $query->orderBy('name', 'asc')->paginate($perPage);

        return response()->json($examTypes);
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $data['school_id'] = $schoolId;
        $data['is_delete'] = 0;

        $examType = ExamType::create($data);

        return response()->json([
            'message' => 'Exam Type created successfully.',
            'exam_type' => $examType
        ], 201);
    }

    public function show(ExamType $examType, Request $request): JsonResponse
    {
        $this->authorize('exam.view');

        $user = $request->user();
        if (!$user->isSuperAdmin() && $examType->school_id !== $user->school_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($examType->is_delete === 1) {
            return response()->json(['message' => 'Exam Type not found.'], 404);
        }

        return response()->json(['exam_type' => $examType]);
    }

    public function update(Request $request, ExamType $examType): JsonResponse
    {
        $this->authorize('exam.edit');

        $user = $request->user();
        if (!$user->isSuperAdmin() && $examType->school_id !== $user->school_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($examType->is_delete === 1) {
            return response()->json(['message' => 'Exam Type not found.'], 404);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $examType->update($data);

        return response()->json([
            'message' => 'Exam Type updated successfully.',
            'exam_type' => $examType
        ]);
    }

    public function toggleStatus(ExamType $examType, Request $request): JsonResponse
    {
        $this->authorize('exam.edit');

        $user = $request->user();
        if (!$user->isSuperAdmin() && $examType->school_id !== $user->school_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($examType->is_delete === 1) {
            return response()->json(['message' => 'Exam Type not found.'], 404);
        }

        $examType->status = $examType->status === 'active' ? 'inactive' : 'active';
        $examType->save();

        return response()->json([
            'message' => 'Exam Type status toggled successfully.',
            'exam_type' => $examType
        ]);
    }

    public function destroy(ExamType $examType, Request $request): JsonResponse
    {
        $this->authorize('exam.delete');

        $user = $request->user();
        if (!$user->isSuperAdmin() && $examType->school_id !== $user->school_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $examType->update(['is_delete' => 1]);

        return response()->json([
            'message' => 'Exam Type deleted successfully.'
        ]);
    }
}
