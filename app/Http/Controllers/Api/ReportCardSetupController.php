<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReportCardSetup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportCardSetupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $schoolId = $user->isSuperAdmin() ? $request->input('school_id') : $user->school_id;
        
        $query = ReportCardSetup::with(['class', 'section', 'exam1', 'exam2']);
        
        if (!$user->isSuperAdmin()) {
            $query->where('school_id', $schoolId);
        } elseif ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        
        if ($request->has('class_id') && $request->class_id) {
            $query->where('class_id', $request->class_id);
        }
        if ($request->has('section_id') && $request->section_id) {
            $query->where('section_id', $request->section_id);
        }
        if ($request->has('academic_year_id') && $request->academic_year_id) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        $setups = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'report_card_setups' => $setups
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'academic_year_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'class_id' => 'required|integer',
            'section_id' => 'nullable|integer',
            'exam_id_1' => 'required|integer',
            'exam_id_2' => 'nullable|integer',
            'status' => 'nullable|string|in:draft,published',
            'template' => 'nullable|string|in:basic,detailed,cbse',
            'include_graded' => 'nullable|string|in:yes,no',
        ]);

        $user = $request->user();
        $schoolId = $user->school_id;
        if ($user->isSuperAdmin()) {
            $schoolId = \App\Models\ClassModel::where('id', $request->class_id)->value('school_id');
        }

        $setup = ReportCardSetup::create([
            'school_id' => $schoolId,
            'academic_year_id' => $request->academic_year_id,
            'name' => $request->name,
            'class_id' => $request->class_id,
            'section_id' => $request->section_id,
            'exam_id_1' => $request->exam_id_1,
            'exam_id_2' => $request->exam_id_2,
            'status' => $request->input('status', 'draft'),
            'template' => $request->input('template', 'basic'),
            'include_graded' => $request->input('include_graded', 'no'),
        ]);

        return response()->json([
            'message' => 'Report card setup created successfully.',
            'report_card_setup' => $setup->load(['class', 'section', 'exam1', 'exam2'])
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $setup = ReportCardSetup::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'exam_id_1' => 'required|integer',
            'exam_id_2' => 'nullable|integer',
            'status' => 'nullable|string|in:draft,published',
            'class_id' => 'required|integer',
            'section_id' => 'nullable|integer',
            'template' => 'nullable|string|in:basic,detailed,cbse',
            'include_graded' => 'nullable|string|in:yes,no',
        ]);

        $setup->update([
            'name' => $request->name,
            'class_id' => $request->class_id,
            'section_id' => $request->section_id,
            'exam_id_1' => $request->exam_id_1,
            'exam_id_2' => $request->exam_id_2,
            'status' => $request->input('status', $setup->status),
            'template' => $request->input('template', $setup->template),
            'include_graded' => $request->input('include_graded', $setup->include_graded),
        ]);

        return response()->json([
            'message' => 'Report card setup updated successfully.',
            'report_card_setup' => $setup->load(['class', 'section', 'exam1', 'exam2'])
        ]);
    }

    public function togglePublish($id): JsonResponse
    {
        $setup = ReportCardSetup::findOrFail($id);
        $newStatus = $setup->status === 'published' ? 'draft' : 'published';
        
        $setup->update(['status' => $newStatus]);

        return response()->json([
            'message' => 'Report card status updated to ' . $newStatus . '.',
            'status' => $newStatus,
            'report_card_setup' => $setup->load(['class', 'section', 'exam1', 'exam2'])
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $setup = ReportCardSetup::findOrFail($id);
        $setup->delete();

        return response()->json([
            'message' => 'Report card setup deleted successfully.'
        ]);
    }
}
