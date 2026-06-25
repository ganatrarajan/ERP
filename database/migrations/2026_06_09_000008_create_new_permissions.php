<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            'subject.view',
            'subject.create',
            'subject.edit',
            'subject.delete',
            'attendance.view',
            'attendance.create',
            'attendance.edit',
            'attendance.delete',
            'homework.view',
            'homework.create',
            'homework.edit',
            'homework.delete',
            'notice.view',
            'notice.create',
            'notice.edit',
            'notice.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissions = [
            'subject.view',
            'subject.create',
            'subject.edit',
            'subject.delete',
            'attendance.view',
            'attendance.create',
            'attendance.edit',
            'attendance.delete',
            'homework.view',
            'homework.create',
            'homework.edit',
            'homework.delete',
            'notice.view',
            'notice.create',
            'notice.edit',
            'notice.delete',
        ];

        Permission::whereIn('name', $permissions)->delete();
    }
};
