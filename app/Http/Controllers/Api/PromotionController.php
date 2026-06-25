<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Promotion\StorePromotionRequest;
use App\Services\PromotionService;
use App\Models\PromotionLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    protected PromotionService $promotionService;

    public function __construct(PromotionService $promotionService)
    {
        $this->promotionService = $promotionService;
    }

    /**
     * Display a listing of promotion logs.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('promotion.view');

        $currentUser = $request->user();
        $query = PromotionLog::with([
            'student',
            'fromAcademicYear',
            'fromClass',
            'fromSection',
            'toAcademicYear',
            'toClass',
            'toSection',
            'promoter'
        ]);

        // Scope to school
        if (!$currentUser->isSuperAdmin()) {
            $query->where('school_id', $currentUser->school_id);
        } elseif ($request->has('school_id')) {
            $query->where('school_id', $request->input('school_id'));
        }

        return response()->json([
            'logs' => $query->orderBy('created_at', 'desc')->get()
        ]);
    }

    /**
     * Execute student promotions.
     */
    public function store(StorePromotionRequest $request): JsonResponse
    {
        $this->authorize('promotion.create');

        $currentUser = $request->user();
        $schoolId = $currentUser->isSuperAdmin()
            ? $request->input('school_id')
            : $currentUser->school_id;

        if (!$schoolId) {
            return response()->json(['message' => 'School ID is required.'], 422);
        }

        $this->promotionService->promoteStudents(
            $request->validated(), 
            $schoolId, 
            $currentUser->id
        );

        return response()->json([
            'message' => 'Students promoted successfully'
        ], 201);
    }
}
