<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Define examination permissions
        $examPermissions = [
            'exam.view',
            'exam.create',
            'exam.edit',
            'exam.delete',
            'exam_schedule.view',
            'exam_schedule.create',
            'exam_schedule.edit',
            'exam_schedule.delete',
            'marks.view',
            'marks.create',
            'marks.edit',
            'result.view',
            'report_card.view',
        ];

        // Ensure permissions exist
        foreach ($examPermissions as $permissionName) {
            \Spatie\Permission\Models\Permission::findOrCreate($permissionName, 'web');
        }

        // Get all School Admin roles
        $schoolAdminRoles = \App\Models\Role::where('name', 'School Admin')->get();
        foreach ($schoolAdminRoles as $role) {
            $role->givePermissionTo($examPermissions);
        }

        // Get all Teacher roles
        $teacherPermissions = [
            'exam.view',
            'exam_schedule.view',
            'marks.view',
            'marks.create',
            'marks.edit',
            'report_card.view',
        ];
        $teacherRoles = \App\Models\Role::where('name', 'Teacher')->get();
        foreach ($teacherRoles as $role) {
            $role->givePermissionTo($teacherPermissions);
        }

        // Forget cached permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optional rollback logic: revoke permissions
        $examPermissions = [
            'exam.view',
            'exam.create',
            'exam.edit',
            'exam.delete',
            'exam_schedule.view',
            'exam_schedule.create',
            'exam_schedule.edit',
            'exam_schedule.delete',
            'marks.view',
            'marks.create',
            'marks.edit',
            'result.view',
            'report_card.view',
        ];

        $schoolAdminRoles = \App\Models\Role::where('name', 'School Admin')->get();
        foreach ($schoolAdminRoles as $role) {
            foreach ($examPermissions as $permission) {
                $role->revokePermissionTo($permission);
            }
        }

        $teacherPermissions = [
            'exam.view',
            'exam_schedule.view',
            'marks.view',
            'marks.create',
            'marks.edit',
            'report_card.view',
        ];
        $teacherRoles = \App\Models\Role::where('name', 'Teacher')->get();
        foreach ($teacherRoles as $role) {
            foreach ($teacherPermissions as $permission) {
                $role->revokePermissionTo($permission);
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
