<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeeDiscount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeeDiscountController extends Controller
{
    /**
     * Display a listing of discounts.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('fee_collection.view');

        $user = $request->user();
        $query = FeeDiscount::with(['student'])
            ->where('is_delete', false);

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

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $discounts = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'discounts' => $discounts
        ]);
    }

    /**
     * Store a newly created discount.
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
            'student_id' => 'required|exists:students,id',
            'discount_type' => 'required|in:fixed,percentage',
            'discount_value' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:active,inactive',
            'max_uses' => 'required|integer|min:1',
        ]);

        $validated['school_id'] = $schoolId;
        
        $activeYear = \App\Models\AcademicYear::where('school_id', $schoolId)
            ->where('is_current', true)
            ->where('is_delete', 0)
            ->first();
        if ($activeYear) {
            $validated['academic_year_id'] = $activeYear->id;
        }

        $discount = FeeDiscount::create($validated);

        return response()->json([
            'message' => 'Fee discount created successfully',
            'discount' => $discount->load('student')
        ], 201);
    }

    /**
     * Display the specified discount.
     */
    public function show($id, Request $request): JsonResponse
    {
        $this->authorize('fee_collection.view');

        $user = $request->user();
        $discount = FeeDiscount::with('student')->findOrFail($id);

        if (!$user->isSuperAdmin() && $discount->school_id !== $user->school_id) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        return response()->json(['discount' => $discount]);
    }

    /**
     * Update the specified discount.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $this->authorize('fee_collection.edit');

        $user = $request->user();
        $discount = FeeDiscount::findOrFail($id);

        if (!$user->isSuperAdmin() && $discount->school_id !== $user->school_id) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $validated = $request->validate([
            'discount_type' => 'required|in:fixed,percentage',
            'discount_value' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:active,inactive',
            'max_uses' => 'required|integer|min:1',
        ]);

        $discount->update($validated);

        return response()->json([
            'message' => 'Fee discount updated successfully',
            'discount' => $discount->load('student')
        ]);
    }

    /**
     * Remove the specified discount.
     */
    public function destroy($id, Request $request): JsonResponse
    {
        $this->authorize('fee_collection.delete');

        $user = $request->user();
        $discount = FeeDiscount::findOrFail($id);

        if (!$user->isSuperAdmin() && $discount->school_id !== $user->school_id) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $discount->update(['is_delete' => true]);

        return response()->json([
            'message' => 'Fee discount deleted successfully'
        ]);
    }
}
