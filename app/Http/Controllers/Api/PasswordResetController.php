<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasswordResetController extends Controller
{
    /**
     * Display a listing of the password reset requests (Super Admin only).
     */
    public function index(Request $request): JsonResponse
    {
        if (!$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $requests = PasswordResetRequest::orderBy('created_at', 'desc')->get();

        return response()->json([
            'requests' => $requests
        ]);
    }

    /**
     * Handle the approval or rejection of a password reset request (Super Admin only).
     */
    public function handleAction(Request $request, $id): JsonResponse
    {
        if (!$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'action' => 'required|string|in:approve,reject'
        ]);

        $resetRequest = PasswordResetRequest::findOrFail($id);

        if ($validated['action'] === 'approve') {
            $user = User::where('email', $resetRequest->email)->first();

            if (!$user) {
                return response()->json([
                    'message' => 'User with this email no longer exists in the system.'
                ], 404);
            }

            // Reset password to "12345678" (1 to 8)
            $user->password = Hash::make('12345678');
            $user->save();

            $resetRequest->status = 'approved';
        } else {
            $resetRequest->status = 'rejected';
        }

        $resetRequest->save();

        return response()->json([
            'message' => 'Request successfully ' . $resetRequest->status . '.',
            'request' => $resetRequest
        ]);
    }
}
