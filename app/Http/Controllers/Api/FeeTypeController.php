<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeeType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeeTypeController extends Controller
{
    /**
     * Display a listing of fee types.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('fee_type.view');

        $user = $request->user();
        $query = FeeType::where('is_delete', false);

        if (!$user->isSuperAdmin()) {
            $query->where('school_id', $user->school_id);
        } elseif ($request->has('school_id')) {
            $query->where('school_id', $request->input('school_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->input('status') === 'active') {
            $query->where('status', 'active');
        }

        if ($request->has('is_optional')) {
            $query->where('is_optional', $request->boolean('is_optional'));
        }

        $perPage = $request->input('per_page', 10);
        $feeTypes = $query->orderBy('name', 'asc')->paginate($perPage);

        return response()->json($feeTypes);
    }

    /**
     * Store a newly created fee type.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('fee_type.create');

        $user = $request->user();
        $schoolId = $user->isSuperAdmin() ? $request->input('school_id') : $user->school_id;

        if (!$schoolId) {
            return response()->json(['message' => 'School ID is required.'], 422);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'description' => 'nullable|string',
            'is_optional' => 'boolean',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['school_id'] = $schoolId;
        $feeType = FeeType::create($validated);

        return response()->json([
            'message' => 'Fee type created successfully',
            'fee_type' => $feeType
        ], 201);
    }

    /**
     * Display the specified fee type.
     */
    public function show($id, Request $request): JsonResponse
    {
        $this->authorize('fee_type.view');

        $user = $request->user();
        $feeType = FeeType::findOrFail($id);

        if (!$user->isSuperAdmin() && $feeType->school_id !== $user->school_id) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        return response()->json(['fee_type' => $feeType]);
    }

    /**
     * Update the specified fee type.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $this->authorize('fee_type.edit');

        $user = $request->user();
        $feeType = FeeType::findOrFail($id);

        if (!$user->isSuperAdmin() && $feeType->school_id !== $user->school_id) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'description' => 'nullable|string',
            'is_optional' => 'boolean',
            'status' => 'required|in:active,inactive',
        ]);

        $feeType->update($validated);

        return response()->json([
            'message' => 'Fee type updated successfully',
            'fee_type' => $feeType
        ]);
    }

    /**
     * Toggle the status of a fee type.
     */
    public function toggleStatus($id, Request $request): JsonResponse
    {
        $this->authorize('fee_type.edit');

        $user = $request->user();
        $feeType = FeeType::findOrFail($id);

        if (!$user->isSuperAdmin() && $feeType->school_id !== $user->school_id) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $feeType->status = $feeType->status === 'active' ? 'inactive' : 'active';
        $feeType->save();

        return response()->json([
            'message' => 'Fee type status updated successfully',
            'fee_type' => $feeType
        ]);
    }

    /**
     * Remove the specified fee type.
     */
    public function destroy($id, Request $request): JsonResponse
    {
        $this->authorize('fee_type.delete');

        $user = $request->user();
        $feeType = FeeType::findOrFail($id);

        if (!$user->isSuperAdmin() && $feeType->school_id !== $user->school_id) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        // Check if used in any active structures
        $isUsedInStructure = \App\Models\FeeStructureItem::where('fee_type_id', $feeType->id)
            ->whereHas('feeStructure', function($q) {
                $q->where('is_delete', false);
            })
            ->exists();

        if ($isUsedInStructure) {
            return response()->json([
                'message' => 'Cannot delete this fee type. It is currently used in one or more active fee structures.'
            ], 422);
        }

        // Check if assigned to any student as optional fee
        $isUsedInOptional = \App\Models\StudentOptionalFee::where('fee_type_id', $feeType->id)->exists();
        if ($isUsedInOptional) {
            return response()->json([
                'message' => 'Cannot delete this fee type. It is currently assigned as an optional fee to one or more students.'
            ], 422);
        }

        $feeType->update(['is_delete' => true]);

        return response()->json([
            'message' => 'Fee type deleted successfully'
        ]);
    }
}
