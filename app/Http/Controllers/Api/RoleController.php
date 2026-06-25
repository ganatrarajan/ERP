<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Http\Requests\Roles\StoreRoleRequest;
use App\Http\Requests\Roles\UpdateRoleRequest;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    protected RoleService $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * Display a listing of roles.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('role.view');

        $query = Role::with('permissions');
        
        if (!$request->user()->isSuperAdmin()) {
            $query->where('name', '!=', 'Super Admin')
                  ->where('school_id', $request->user()->school_id);
        } else {
            if ($request->has('school_id')) {
                $query->where('school_id', $request->input('school_id'));
            } else {
                $query->whereNull('school_id');
            }
        }

        $roles = $query->get();

        return response()->json([
            'roles' => $roles
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $this->authorize('role.edit');

        $role = $this->roleService->createRole($request->validated());

        return response()->json([
            'message' => 'Role created successfully',
            'role' => $role->load('permissions')
        ], 201);
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role, Request $request): JsonResponse
    {
        $this->authorize('role.view');

        // Non-Super Admin cannot view roles of other schools
        if (!$request->user()->isSuperAdmin() && $role->school_id !== $request->user()->school_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Non-Super Admin cannot view Super Admin role details
        if ($role->name === 'Super Admin' && !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json([
            'role' => $role->load('permissions')
        ]);
    }

    /**
     * Update the specified role.
     */
    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $this->authorize('role.edit');

        // Non-Super Admin cannot update roles of other schools
        if (!$request->user()->isSuperAdmin() && $role->school_id !== $request->user()->school_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Non-Super Admin cannot update Super Admin role
        if ($role->name === 'Super Admin' && !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        try {
            $updatedRole = $this->roleService->updateRole($role, $request->validated());
            return response()->json([
                'message' => 'Role updated successfully',
                'role' => $updatedRole->load('permissions')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role, Request $request): JsonResponse
    {
        $this->authorize('role.edit');

        // Non-Super Admin cannot delete roles of other schools
        if (!$request->user()->isSuperAdmin() && $role->school_id !== $request->user()->school_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        try {
            $this->roleService->deleteRole($role);
            return response()->json([
                'message' => 'Role deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Display a listing of all permissions.
     */
    public function permissions(Request $request): JsonResponse
    {
        $this->authorize('permission.view');

        $user = $request->user();

        if ($user->isSuperAdmin()) {
            $permissions = Permission::all();
            return response()->json([
                'permissions' => $permissions
            ]);
        }

        $schoolId = $user->school_id;
        if (!$schoolId) {
            return response()->json([
                'permissions' => []
            ]);
        }

        // Get active module slugs for this school
        $activeModules = \Illuminate\Support\Facades\DB::table('school_modules')
            ->join('modules', 'modules.id', '=', 'school_modules.module_id')
            ->where('school_modules.school_id', $schoolId)
            ->where('school_modules.is_active', true)
            ->where('modules.status', 'active')
            ->pluck('modules.slug')
            ->toArray();

        // Map modules to permission prefixes
        $modulePermissionMap = [
            'students' => ['student', 'promotion'],
            'academics' => ['academic_year', 'class', 'section'],
            'teachers' => ['teacher'],
            'attendance' => ['attendance'],
            'homework' => ['homework'],
            'fees' => ['fee_type', 'fee_structure', 'fee_collection', 'receipt', 'ledger', 'report'],
            'exams' => ['exams'],
            'library' => ['library'],
            'transport' => ['transport'],
            'hostel' => ['hostel'],
            'reports' => ['reports'],
            'settings' => ['settings'],
            'subjects' => ['subject'],
            'notices' => ['notice'],
            'examinations' => ['exam', 'exam_schedule', 'marks', 'result', 'report_card'],
        ];

        // Core prefixes always allowed for school users
        $allowedPrefixes = ['user', 'role', 'permission', 'dashboard'];

        foreach ($activeModules as $moduleSlug) {
            if (isset($modulePermissionMap[$moduleSlug])) {
                $allowedPrefixes = array_merge($allowedPrefixes, $modulePermissionMap[$moduleSlug]);
            }
        }

        $permissions = Permission::all()->filter(function ($permission) use ($allowedPrefixes) {
            $prefix = explode('.', $permission->name)[0];
            return in_array($prefix, $allowedPrefixes);
        })->values();

        return response()->json([
            'permissions' => $permissions
        ]);
    }
}
