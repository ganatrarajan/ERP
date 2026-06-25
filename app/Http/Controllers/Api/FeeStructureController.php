<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeeStructure;
use App\Models\FeeStructureItem;
use App\Models\FeeInstallment;
use App\Models\AcademicYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeeStructureController extends Controller
{
    /**
     * Display a listing of fee structures.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('fee_structure.view');

        $user = $request->user();
        $query = FeeStructure::with(['class', 'academicYear', 'items.feeType', 'installments'])
            ->where('is_delete', false);

        if (!$user->isSuperAdmin()) {
            $query->where('school_id', $user->school_id);
        } elseif ($request->has('school_id')) {
            $query->where('school_id', $request->input('school_id'));
        }

        // Active Academic Year defaults if not provided
        $academicYearId = $request->input('academic_year_id');
        if (!$academicYearId && !$user->isSuperAdmin()) {
            $activeYear = AcademicYear::where('school_id', $user->school_id)
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

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->input('class_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->input('status') === 'active') {
            $query->where('status', 'active');
        }

        $feeStructures = $query->orderBy('name', 'asc')->get();

        // Add calculated total fields
        foreach ($feeStructures as $struct) {
            $struct->total_amount = $struct->items->sum('amount');
            $struct->installments_total = $struct->installments->where('is_delete', false)->sum('amount');
            $struct->is_validated = round($struct->total_amount, 2) === round($struct->installments_total, 2);
        }

        return response()->json([
            'fee_structures' => $feeStructures
        ]);
    }

    /**
     * Store a newly created fee structure.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('fee_structure.create');

        $user = $request->user();
        $schoolId = $user->isSuperAdmin() ? $request->input('school_id') : $user->school_id;

        if (!$schoolId) {
            return response()->json(['message' => 'School ID is required.'], 422);
        }

        $academicYearId = $request->input('academic_year_id');
        $academicYear = AcademicYear::find($academicYearId);
        if (!$academicYear || !$academicYear->is_current) {
            return response()->json([
                'message' => 'This academic session is locked. Modifications are only allowed in the active session.'
            ], 422);
        }

        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'class_id' => 'required|exists:classes,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'items' => 'required|array|min:1',
            'items.*.fee_type_id' => 'required|exists:fee_types,id',
            'items.*.amount' => 'required|numeric|min:0',
            'installments' => 'nullable|array',
            'installments.*.installment_name' => 'required|string|max:255',
            'installments.*.due_date' => 'required|date',
            'installments.*.amount' => 'required|numeric|min:0',
            'installments.*.sort_order' => 'nullable|integer',
        ]);

        $items = $request->input('items');
        $installments = $request->input('installments', []);

        $itemsSum = array_sum(array_column($items, 'amount'));
        $installmentsSum = array_sum(array_column($installments, 'amount'));

        if (!empty($installments) && round($itemsSum, 2) !== round($installmentsSum, 2)) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => [
                    'installments' => ["Total installment amount ({$installmentsSum}) must equal fee structure items total ({$itemsSum})."]
                ]
            ], 422);
        }

        $feeStructure = DB::transaction(function () use ($request, $schoolId, $items, $installments) {
            $structure = FeeStructure::create([
                'school_id' => $schoolId,
                'academic_year_id' => $request->input('academic_year_id'),
                'class_id' => $request->input('class_id'),
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'status' => $request->input('status'),
            ]);

            foreach ($items as $item) {
                FeeStructureItem::create([
                    'fee_structure_id' => $structure->id,
                    'fee_type_id' => $item['fee_type_id'],
                    'amount' => $item['amount'],
                ]);
            }

            foreach ($installments as $inst) {
                FeeInstallment::create([
                    'school_id' => $schoolId,
                    'fee_structure_id' => $structure->id,
                    'installment_name' => $inst['installment_name'],
                    'due_date' => $inst['due_date'],
                    'amount' => $inst['amount'],
                    'sort_order' => $inst['sort_order'] ?? 0,
                    'status' => 'active',
                ]);
            }

            return $structure;
        });

        return response()->json([
            'message' => 'Fee structure created successfully',
            'fee_structure' => $feeStructure->load(['class', 'academicYear', 'items.feeType', 'installments'])
        ], 201);
    }

    /**
     * Display the specified fee structure.
     */
    public function show($id, Request $request): JsonResponse
    {
        $this->authorize('fee_structure.view');

        $user = $request->user();
        $feeStructure = FeeStructure::with(['class', 'academicYear', 'items.feeType', 'installments' => function($q) {
            $q->where('is_delete', false)->orderBy('sort_order', 'asc');
        }])->findOrFail($id);

        if (!$user->isSuperAdmin() && $feeStructure->school_id !== $user->school_id) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $feeStructure->total_amount = $feeStructure->items->sum('amount');
        $feeStructure->installments_total = $feeStructure->installments->sum('amount');

        return response()->json(['fee_structure' => $feeStructure]);
    }

    /**
     * Update the specified fee structure.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $this->authorize('fee_structure.edit');

        $user = $request->user();
        $feeStructure = FeeStructure::findOrFail($id);

        if (!$user->isSuperAdmin() && $feeStructure->school_id !== $user->school_id) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $academicYear = AcademicYear::find($feeStructure->academic_year_id);
        if (!$academicYear || !$academicYear->is_current) {
            return response()->json([
                'message' => 'This academic session is locked. Modifications are only allowed in the active session.'
            ], 422);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'items' => 'required|array|min:1',
            'items.*.fee_type_id' => 'required|exists:fee_types,id',
            'items.*.amount' => 'required|numeric|min:0',
            'installments' => 'nullable|array',
            'installments.*.installment_name' => 'required|string|max:255',
            'installments.*.due_date' => 'required|date',
            'installments.*.amount' => 'required|numeric|min:0',
            'installments.*.sort_order' => 'nullable|integer',
        ]);

        $items = $request->input('items');
        $installments = $request->input('installments', []);

        $itemsSum = array_sum(array_column($items, 'amount'));
        $installmentsSum = array_sum(array_column($installments, 'amount'));

        if (!empty($installments) && round($itemsSum, 2) !== round($installmentsSum, 2)) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => [
                    'installments' => ["Total installment amount ({$installmentsSum}) must equal fee structure items total ({$itemsSum})."]
                ]
            ], 422);
        }

        DB::transaction(function () use ($feeStructure, $request, $items, $installments) {
            $feeStructure->update([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'status' => $request->input('status'),
            ]);

            // Sync items (Delete existing ones and insert new ones)
            FeeStructureItem::where('fee_structure_id', $feeStructure->id)->delete();
            foreach ($items as $item) {
                FeeStructureItem::create([
                    'fee_structure_id' => $feeStructure->id,
                    'fee_type_id' => $item['fee_type_id'],
                    'amount' => $item['amount'],
                ]);
            }

            // Sync installments (Soft delete existing and insert/update new ones)
            FeeInstallment::where('fee_structure_id', $feeStructure->id)->update(['is_delete' => true]);
            foreach ($installments as $inst) {
                FeeInstallment::create([
                    'school_id' => $feeStructure->school_id,
                    'fee_structure_id' => $feeStructure->id,
                    'installment_name' => $inst['installment_name'],
                    'due_date' => $inst['due_date'],
                    'amount' => $inst['amount'],
                    'sort_order' => $inst['sort_order'] ?? 0,
                    'status' => 'active',
                ]);
            }
        });

        return response()->json([
            'message' => 'Fee structure updated successfully',
            'fee_structure' => $feeStructure->load(['class', 'academicYear', 'items.feeType', 'installments' => function($q) {
                $q->where('is_delete', false)->orderBy('sort_order', 'asc');
            }])
        ]);
    }

    /**
     * Clone an existing fee structure.
     */
    public function clone(Request $request, $id): JsonResponse
    {
        $this->authorize('fee_structure.create');

        $user = $request->user();
        $original = FeeStructure::with(['items', 'installments' => function($q) {
            $q->where('is_delete', false);
        }])->findOrFail($id);

        if (!$user->isSuperAdmin() && $original->school_id !== $user->school_id) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'class_id' => 'required|exists:classes,id',
            'name' => 'required|string|max:255',
        ]);

        $academicYearId = $request->input('academic_year_id');
        $academicYear = AcademicYear::find($academicYearId);
        if (!$academicYear || !$academicYear->is_current) {
            return response()->json([
                'message' => 'This academic session is locked. Modifications are only allowed in the active session.'
            ], 422);
        }

        $cloned = DB::transaction(function () use ($original, $request) {
            $newStruct = FeeStructure::create([
                'school_id' => $original->school_id,
                'academic_year_id' => $request->input('academic_year_id'),
                'class_id' => $request->input('class_id'),
                'name' => $request->input('name'),
                'description' => $original->description,
                'status' => 'active',
            ]);

            foreach ($original->items as $item) {
                FeeStructureItem::create([
                    'fee_structure_id' => $newStruct->id,
                    'fee_type_id' => $item->fee_type_id,
                    'amount' => $item->amount,
                ]);
            }

            foreach ($original->installments as $inst) {
                FeeInstallment::create([
                    'school_id' => $original->school_id,
                    'fee_structure_id' => $newStruct->id,
                    'installment_name' => $inst->installment_name,
                    'due_date' => $inst->due_date,
                    'amount' => $inst->amount,
                    'sort_order' => $inst->sort_order,
                    'status' => 'active',
                ]);
            }

            return $newStruct;
        });

        return response()->json([
            'message' => 'Fee structure cloned successfully',
            'fee_structure' => $cloned->load(['class', 'academicYear', 'items.feeType', 'installments'])
        ], 201);
    }

    /**
     * Remove the specified fee structure.
     */
    public function destroy($id, Request $request): JsonResponse
    {
        $this->authorize('fee_structure.delete');

        $user = $request->user();
        $feeStructure = FeeStructure::findOrFail($id);

        if (!$user->isSuperAdmin() && $feeStructure->school_id !== $user->school_id) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $academicYear = AcademicYear::find($feeStructure->academic_year_id);
        if (!$academicYear || !$academicYear->is_current) {
            return response()->json([
                'message' => 'This academic session is locked. Modifications are only allowed in the active session.'
            ], 422);
        }

        // Check if assigned to any student
        $hasAssignments = \App\Models\StudentFeeAssignment::where('fee_structure_id', $feeStructure->id)->exists();
        if ($hasAssignments) {
            return response()->json([
                'message' => 'Cannot delete this fee structure. It is currently assigned to one or more students.'
            ], 422);
        }

        // Check if collections have been made against its installments
        $installmentIds = $feeStructure->installments()->pluck('id');
        $hasCollections = \App\Models\FeeCollection::whereIn('installment_id', $installmentIds)->exists();
        if ($hasCollections) {
            return response()->json([
                'message' => 'Cannot delete this fee structure. Payments have already been collected against its installments.'
            ], 422);
        }

        DB::transaction(function () use ($feeStructure) {
            $feeStructure->update(['is_delete' => true]);
            FeeInstallment::where('fee_structure_id', $feeStructure->id)->update(['is_delete' => true]);
        });

        return response()->json([
            'message' => 'Fee structure deleted successfully'
        ]);
    }
}
