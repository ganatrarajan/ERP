<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\PasswordReset;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle user login.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember', false);

        $user = $this->authService->login($credentials, $remember);
        $userWithRoles = $this->authService->getAuthenticatedUser($user);

        return response()->json([
            'message' => 'Logged in successfully',
            'user' => $userWithRoles,
            'impersonating' => session()->has('impersonator_user_id'),
            'active_modules' => $this->authService->getActiveModulesForUser($user),
        ]);
    }

    /**
     * Handle user logout.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $userWithRoles = $this->authService->getAuthenticatedUser($user);

        return response()->json([
            'user' => $userWithRoles,
            'impersonating' => session()->has('impersonator_user_id'),
            'active_modules' => $this->authService->getActiveModulesForUser($user),
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'message' => 'nullable|string|max:1000'
        ]);

        \App\Models\PasswordResetRequest::create([
            'email' => $request->input('email'),
            'message' => $request->input('message'),
            'status' => 'pending'
        ]);

        return response()->json([
            'message' => 'Your password reset request has been submitted successfully to the Super Admin.'
        ]);
    }

    /**
     * Reset user password.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Password has been reset successfully.'])
            : response()->json(['message' => 'Invalid or expired reset token.'], 400);
    }

    /**
     * Update authenticated user's profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'mobile' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->mobile = $validated['mobile'] ?? null;

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        $userWithRoles = $this->authService->getAuthenticatedUser($user);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $userWithRoles,
            'active_modules' => $this->authService->getActiveModulesForUser($user),
        ]);
    }

    /**
     * Update the user's selected academic year context.
     */
    public function updateSelectedAcademicYear(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'academic_year_id' => 'nullable|integer|exists:academic_years,id',
        ]);

        $academicYearId = $validated['academic_year_id'] ?? null;

        if ($academicYearId) {
            // Verify school boundary
            $academicYear = \App\Models\AcademicYear::find($academicYearId);
            if (!$user->isSuperAdmin() && $academicYear && $academicYear->school_id !== $user->school_id) {
                return response()->json(['message' => 'Unauthorized academic year choice.'], 403);
            }

            // Save in database
            \Illuminate\Support\Facades\DB::table('user_academic_selections')->updateOrInsert(
                ['user_id' => $user->id],
                ['academic_year_id' => $academicYearId, 'updated_at' => now(), 'created_at' => now()]
            );

            // Save in session
            if ($request->hasSession()) {
                $request->session()->put('selected_academic_year_id', $academicYearId);
            }

            \App\Support\AcademicYearContext::setWebAcademicYearId($academicYearId);
        } else {
            // Delete selection
            \Illuminate\Support\Facades\DB::table('user_academic_selections')->where('user_id', $user->id)->delete();

            // Clear from session
            if ($request->hasSession()) {
                $request->session()->forget('selected_academic_year_id');
            }

            \App\Support\AcademicYearContext::setWebAcademicYearId(null);
        }

        return response()->json([
            'message' => 'Academic year context updated successfully.',
            'selected_academic_year_id' => $academicYearId
        ]);
    }
}
