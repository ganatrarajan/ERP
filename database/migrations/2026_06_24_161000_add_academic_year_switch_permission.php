<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permission
        Permission::findOrCreate('academic_year.switch', 'web');

        // Assign to all Super Admin, School Admin, and Teacher roles
        $roles = Role::whereIn('name', ['Super Admin', 'School Admin', 'Teacher'])->get();
        foreach ($roles as $role) {
            $role->givePermissionTo('academic_year.switch');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Detach and delete permission
        $permission = Permission::where('name', 'academic_year.switch')->first();
        if ($permission) {
            $permission->delete();
        }
    }
};
