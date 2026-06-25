<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Role;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of users.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('user.view');

        $currentUser = $request->user();
        $query = User::with(['school', 'roles']);

        // Scope to own school if not Super Admin
        if (!$currentUser->isSuperAdmin()) {
            $query->where('school_id', $currentUser->school_id);
        }

        $users = $query->get();

        return response()->json([
            'users' => $users
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $currentUser = $request->user();
        
        // Prevent non-Super Admin from assigning Super Admin role or other schools' roles
        if (isset($request->validated()['role'])) {
            $role = Role::find($request->validated()['role']);
            if ($role) {
                if ($role->name === 'Super Admin' && !$currentUser->isSuperAdmin()) {
                    return response()->json(['message' => 'Unauthorized to assign Super Admin role.'], 403);
                }
                if (!$currentUser->isSuperAdmin() && $role->school_id !== $currentUser->school_id) {
                    return response()->json(['message' => 'Unauthorized to assign this role.'], 403);
                }
            }
        }

        // Determine the school_id for the user
        $schoolId = $currentUser->isSuperAdmin() 
            ? $request->input('school_id') 
            : $currentUser->school_id;

        $user = $this->userService->createUser($request->validated(), $schoolId);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user->load(['school', 'roles'])
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user, Request $request): JsonResponse
    {
        $this->authorize('user.view');

        $currentUser = $request->user();

        // Enforce school boundaries
        if (!$currentUser->isSuperAdmin() && ($currentUser->school_id !== $user->school_id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        return response()->json([
            'user' => $user->load(['school', 'roles'])
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $currentUser = $request->user();

        // Enforce school boundaries
        if (!$currentUser->isSuperAdmin() && ($currentUser->school_id !== $user->school_id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        // Prevent non-Super Admin from assigning Super Admin role or other schools' roles
        if (isset($request->validated()['role'])) {
            $role = Role::find($request->validated()['role']);
            if ($role) {
                if ($role->name === 'Super Admin' && !$currentUser->isSuperAdmin()) {
                    return response()->json(['message' => 'Unauthorized to assign Super Admin role.'], 403);
                }
                if (!$currentUser->isSuperAdmin() && $role->school_id !== $currentUser->school_id) {
                    return response()->json(['message' => 'Unauthorized to assign this role.'], 403);
                }
            }
        }

        $updatedUser = $this->userService->updateUser($user, $request->validated());

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $updatedUser->load(['school', 'roles'])
        ]);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user, Request $request): JsonResponse
    {
        $this->authorize('user.delete');

        $currentUser = $request->user();

        // Enforce school boundaries
        if (!$currentUser->isSuperAdmin() && ($currentUser->school_id !== $user->school_id)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        try {
            $this->userService->deleteUser($user);
            return response()->json([
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
