<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    /**
     * Impersonate a School Admin for a given school.
     */
    public function impersonate(School $school, Request $request): JsonResponse
    {
        $currentUser = $request->user();

        // Only Super Admin can initiate impersonation
        if (!$currentUser->isSuperAdmin()) {
            return response()->json(['message' => 'Only Super Admin can impersonate.'], 403);
        }

        // Find the School Admin for this school
        $schoolAdmin = User::where('school_id', $school->id)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'School Admin');
            })
            ->first();

        if (!$schoolAdmin) {
            return response()->json(['message' => 'No School Admin user found for this school.'], 404);
        }

        if ($schoolAdmin->status !== 'active') {
            return response()->json(['message' => 'School Admin user is inactive.'], 400);
        }

        // Store original Super Admin ID in session
        $originalId = $currentUser->id;
        
        // Log in as the School Admin
        Auth::login($schoolAdmin);

        // Put the impersonator ID in the session
        session(['impersonator_user_id' => $originalId]);

        return response()->json([
            'message' => "Impersonating {$school->name} Admin",
            'user' => $schoolAdmin->load(['school', 'roles.permissions']),
            'impersonating' => true,
            'active_modules' => resolve(\App\Services\AuthService::class)->getActiveModulesForUser($schoolAdmin),
        ]);
    }

    /**
     * Exit current impersonation and return to Super Admin.
     */
    public function exitImpersonation(Request $request): JsonResponse
    {
        if (!session()->has('impersonator_user_id')) {
            return response()->json(['message' => 'No active impersonation session.'], 400);
        }

        $superAdminId = session('impersonator_user_id');
        $superAdmin = User::find($superAdminId);

        if (!$superAdmin) {
            return response()->json(['message' => 'Super Admin user not found.'], 404);
        }

        // Log in back as Super Admin
        Auth::login($superAdmin);

        // Clear session key
        session()->forget('impersonator_user_id');

        return response()->json([
            'message' => 'Exited impersonation. Logged back in as Super Admin.',
            'user' => $superAdmin->load(['school', 'roles.permissions']),
            'impersonating' => false,
            'active_modules' => resolve(\App\Services\AuthService::class)->getActiveModulesForUser($superAdmin),
        ]);
    }
}
