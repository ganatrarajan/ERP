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
        Schema::table('roles', function (Blueprint $table) {
            $indexes = Schema::getIndexes('roles');
            $hasOldUnique = collect($indexes)->contains('name', 'roles_name_guard_name_unique');
            $hasNewUnique = collect($indexes)->contains('name', 'roles_school_id_name_guard_name_unique');

            if ($hasOldUnique) {
                $table->dropUnique('roles_name_guard_name_unique');
            }
            if (!$hasNewUnique) {
                $table->unique(['school_id', 'name', 'guard_name'], 'roles_school_id_name_guard_name_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $indexes = Schema::getIndexes('roles');
            $hasOldUnique = collect($indexes)->contains('name', 'roles_name_guard_name_unique');
            $hasNewUnique = collect($indexes)->contains('name', 'roles_school_id_name_guard_name_unique');

            if ($hasNewUnique) {
                $table->dropUnique('roles_school_id_name_guard_name_unique');
            }
            if (!$hasOldUnique) {
                $table->unique(['name', 'guard_name'], 'roles_name_guard_name_unique');
            }
        });
    }
};
