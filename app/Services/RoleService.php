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
                'status' => $data['status'] ?? 'active',
            ]);

            if (!empty($data['permissions'])) {
                $role->syncPermissions($data['permissions']);
            }

            // If a global role (school_id is null) is created, replicate it to all schools
            if (is_null($role->school_id)) {
                $schools = \App\Models\School::all();
                foreach ($schools as $school) {
                    Role::firstOrCreate([
                        'name' => $role->name,
                        'guard_name' => 'web',
                        'school_id' => $school->id,
                    ], [
                        'status' => $role->status,
                    ]);
                }
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

            $updateData = [];
            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }
            if (isset($data['status'])) {
                $updateData['status'] = $data['status'];
            }

            $oldName = $role->name;
            $role->update($updateData);

            if (isset($data['permissions'])) {
                $role->syncPermissions($data['permissions']);
            }

            // If it is a global role, update all copies across all schools
            if (is_null($role->school_id)) {
                $schools = \App\Models\School::all();
                foreach ($schools as $school) {
                    $schoolRole = Role::where('name', $oldName)
                        ->where('school_id', $school->id)
                        ->first();

                    if ($schoolRole) {
                        $schoolRole->update($updateData);
                    } else {
                        Role::create([
                            'name' => $role->name,
                            'guard_name' => 'web',
                            'school_id' => $school->id,
                            'status' => $role->status,
                        ]);
                    }
                }
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

        DB::transaction(function () use ($role) {
            // If it is a global role, delete all copies from all schools
            if (is_null($role->school_id)) {
                Role::where('name', $role->name)
                    ->whereNotNull('school_id')
                    ->delete();
            }
            $role->delete();
        });
    }
}
