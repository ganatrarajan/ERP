<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create the module
        Module::updateOrCreate(
            ['slug' => 'online-payments'],
            [
                'name' => 'Online Payments',
                'icon' => 'credit-card',
                'description' => 'Configure payment gateway integrations and process online student fee collections.',
                'status' => 'active',
            ]
        );

        // 2. Create permissions
        $permissions = [
            'payment_gateway.manage',
            'payment_gateway.view'
        ];

        foreach ($permissions as $permName) {
            Permission::findOrCreate($permName, 'web');
        }

        // 3. Assign permissions to Global Super Admin roles (if any exist)
        $superAdminRoles = Role::where('name', 'Super Admin')->get();
        foreach ($superAdminRoles as $role) {
            $role->givePermissionTo($permissions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove permissions
        Permission::whereIn('name', [
            'payment_gateway.manage',
            'payment_gateway.view'
        ])->delete();

        // Remove module
        Module::where('slug', 'online-payments')->delete();
    }
};
