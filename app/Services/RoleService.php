<?php

namespace App\Services;

use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class RoleService
{
    /**
     * Create a role and sync permissions.
     */
    public function createRole(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => 'web',
                'school_id' => $data['school_id'] ?? (auth()->check() ? auth()->user()->school_id : null),
            ]);

            if (!empty($data['permissions'])) {
                $role->syncPermissions($data['permissions']);
            }

            return $role;
        });
    }

    public function updateRole(Role $role, array $data): Role
    {
        // Prevent editing Super Admin role
        if ($role->name === 'Super Admin') {
            throw new \Exception("The Super Admin role cannot be modified.");
        }

        return DB::transaction(function () use ($role, $data) {
            // Prevent changing the name of system roles
            if (in_array($role->name, ['School Admin', 'Teacher'])) {
                $data['name'] = $role->name;
            }

            $role->update([
                'name' => $data['name']
            ]);

            if (isset($data['permissions'])) {
                $role->syncPermissions($data['permissions']);
            }

            return $role;
        });
    }

    /**
     * Delete role.
     */
    public function deleteRole(Role $role): void
    {
        if (in_array($role->name, ['Super Admin', 'School Admin', 'Teacher'])) {
            throw new \Exception("Default system roles cannot be deleted.");
        }
        $role->delete();
    }
}
