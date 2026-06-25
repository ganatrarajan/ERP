<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeeFineRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeeFineRuleController extends Controller
{
    /**
     * Display a listing of fine rules.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('fee_collection.view');

        $user = $request->user();
        $query = FeeFineRule::where('is_delete', false);

        if (!$user->isSuperAdmin()) {
            $query->where('school_id', $user->school_id);
        } elseif ($request->filled('school_id')) {
            $query->where('school_id', $request->input('school_id'));
        }

        $academicYearId = $request->input('academic_year_id');
        if (!$academicYearId) {
            $schoolId = !$user->isSuperAdmin() ? $user->school_id : $request->input('school_id');
            if ($schoolId) {
                $activeYear = \App\Models\AcademicYear::where('school_id', $schoolId)
                    ->where('is_current', true)
                    ->where('is_delete', 0)
                    ->first();
                if ($activeYear) {
                    $academicYearId = $activeYear->id;
                }
            }
        }

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $fineRules = $query->orderBy('name', 'asc')->get();

        return response()->json([
            'fine_rules' => $fineRules
        ]);
    }

    /**
     * Store a newly created fine rule.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('fee_collection.create');

        $user = $request->user();
        $schoolId = $user->isSuperAdmin() ? $request->input('school_id') : $user->school_id;

        if (!$schoolId) {
            return response()->json(['message' => 'School ID is required.'], 422);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'fine_type' => 'required|in:fixed,per_day',
            'fine_value' => 'required|numeric|min:0',
            'grace_days' => 'required|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['school_id'] = $schoolId;

        $activeYear = \App\Models\AcademicYear::where('school_id', $schoolId)
            ->where('is_current', true)
            ->where('is_delete', 0)
            ->first();
        if ($activeYear) {
            $validated['academic_year_id'] = $activeYear->id;
        }

        $fineRule = FeeFineRule::create($validated);

        return response()->json([
            'message' => 'Fine rule created successfully',
            'fine_rule' => $fineRule
        ], 201);
    }

    /**
     * Display the specified fine rule.
     */
    public function show($id, Request $request): JsonResponse
    {
        $this->authorize('fee_collection.view');

        $user = $request->user();
        $fineRule = FeeFineRule::findOrFail($id);

        if (!$user->isSuperAdmin() && $fineRule->school_id !== $user->school_id) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        return response()->json(['fine_rule' => $fineRule]);
    }

    /**
     * Update the specified fine rule.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $this->authorize('fee_collection.edit');

        $user = $request->user();
        $fineRule = FeeFineRule::findOrFail($id);

        if (!$user->isSuperAdmin() && $fineRule->school_id !== $user->school_id) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'fine_type' => 'required|in:fixed,per_day',
            'fine_value' => 'required|numeric|min:0',
            'grace_days' => 'required|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $fineRule->update($validated);

        return response()->json([
            'message' => 'Fine rule updated successfully',
            'fine_rule' => $fineRule
        ]);
    }

    /**
     * Remove the specified fine rule.
     */
    public function destroy($id, Request $request): JsonResponse
    {
        $this->authorize('fee_collection.delete');

        $user = $request->user();
        $fineRule = FeeFineRule::findOrFail($id);

        if (!$user->isSuperAdmin() && $fineRule->school_id !== $user->school_id) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $fineRule->update(['is_delete' => true]);

        return response()->json([
            'message' => 'Fine rule deleted successfully'
        ]);
    }
}
