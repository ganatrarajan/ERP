<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('exam.view');

        $user = $request->user();
        $query = Exam::query()->with(['academicYear', 'examType'])->where('is_delete', 0);

        if (!$user->isSuperAdmin()) {
            $query->where('school_id', $user->school_id);
        } elseif ($request->has('school_id')) {
            $query->where('school_id', $request->school_id);
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        } else {
            $schoolId = !$user->isSuperAdmin() ? $user->school_id : $request->input('school_id');
            if ($schoolId) {
                $activeYear = \App\Models\AcademicYear::where('school_id', $schoolId)
                    ->where('is_current', true)
                    ->where('is_delete', 0)
                    ->first();
                if ($activeYear) {
                    $query->where('academic_year_id', $activeYear->id);
                }
            }
        }

        if ($request->filled('exam_type_id')) {
            $query->where('exam_type_id', $request->exam_type_id);
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
            $exams = $query->orderBy('start_date', 'desc')->get();
            return response()->json(['exams' => $exams]);
        }

        $perPage = $request->input('per_page', 10);
        $exams = $query->orderBy('start_date', 'desc')->paginate($perPage);

        return response()->json($exams);
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
            'academic_year_id' => 'required|exists:academic_years,id',
            'exam_type_id' => 'required|exists:exam_types,id',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,published',
        ]);

        $data['school_id'] = $schoolId;
        $data['is_delete'] = 0;

        $exam = Exam::create($data);

        return response()->json([
            'message' => 'Exam created successfully.',
            'exam' => $exam->load(['academicYear', 'examType'])
        ], 201);
    }

    public function show(Exam $exam, Request $request): JsonResponse
    {
        $this->authorize('exam.view');

        $user = $request->user();
        if (!$user->isSuperAdmin() && $exam->school_id !== $user->school_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($exam->is_delete === 1) {
            return response()->json(['message' => 'Exam not found.'], 404);
        }

        return response()->json(['exam' => $exam->load(['academicYear', 'examType'])]);
    }

    public function update(Request $request, Exam $exam): JsonResponse
    {
        $this->authorize('exam.edit');

        $user = $request->user();
        if (!$user->isSuperAdmin() && $exam->school_id !== $user->school_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($exam->is_delete === 1) {
            return response()->json(['message' => 'Exam not found.'], 404);
        }

        $data = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'exam_type_id' => 'required|exists:exam_types,id',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,published',
        ]);

        $exam->update($data);

        return response()->json([
            'message' => 'Exam updated successfully.',
            'exam' => $exam->load(['academicYear', 'examType'])
        ]);
    }

    public function togglePublish(Exam $exam, Request $request): JsonResponse
    {
        $this->authorize('exam.edit');

        $user = $request->user();
        if (!$user->isSuperAdmin() && $exam->school_id !== $user->school_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($exam->is_delete === 1) {
            return response()->json(['message' => 'Exam not found.'], 404);
        }

        $exam->status = $exam->status === 'published' ? 'draft' : 'published';
        $exam->save();

        return response()->json([
            'message' => 'Exam publish status updated successfully.',
            'exam' => $exam->load(['academicYear', 'examType'])
        ]);
    }

    public function destroy(Exam $exam, Request $request): JsonResponse
    {
        $this->authorize('exam.delete');

        $user = $request->user();
        if (!$user->isSuperAdmin() && $exam->school_id !== $user->school_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $exam->update(['is_delete' => 1]);

        return response()->json([
            'message' => 'Exam deleted successfully.'
        ]);
    }
}
