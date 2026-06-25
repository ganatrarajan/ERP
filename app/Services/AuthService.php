<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AuthService
{
    /**
     * Authenticate user.
     */
    public function login(array $credentials, bool $remember = false): User
    {
        if (!Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']], $remember)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $user = Auth::user();

        if ($user->status !== 'active') {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => ['Your account is inactive. Please contact system administrator.'],
            ]);
        }

        $user->update([
            'last_login_at' => Carbon::now()
        ]);

        return $user;
    }

    /**
     * Logout current user.
     */
    public function logout(): void
    {
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }

    /**
     * Get authenticated user details with roles and permissions.
     */
    public function getAuthenticatedUser(User $user): User
    {
        return $user->load(['school', 'roles.permissions', 'permissions']);
    }

    /**
     * Get active modules for the user's school.
     */
    public function getActiveModulesForUser(User $user): array
    {
        if ($user->isSuperAdmin()) {
            return \App\Models\Module::where('status', 'active')->pluck('slug')->toArray();
        }

        $schoolId = $user->school_id;
        if (!$schoolId) {
            return [];
        }

        return \Illuminate\Support\Facades\DB::table('school_modules')
            ->join('modules', 'modules.id', '=', 'school_modules.module_id')
            ->where('school_modules.school_id', $schoolId)
            ->where('school_modules.is_active', true)
            ->where('modules.status', 'active')
            ->pluck('modules.slug')
            ->toArray();
    }
}
