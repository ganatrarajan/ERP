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
        // Add to academic_years
        if (Schema::hasTable('academic_years')) {
            Schema::table('academic_years', function (Blueprint $table) {
                if (!Schema::hasColumn('academic_years', 'is_delete')) {
                    $table->tinyInteger('is_delete')->default(0)->after('status');
                }
            });
        }

        // Add to classes
        if (Schema::hasTable('classes')) {
            Schema::table('classes', function (Blueprint $table) {
                if (!Schema::hasColumn('classes', 'is_delete')) {
                    $table->tinyInteger('is_delete')->default(0)->after('status');
                }
            });
        }

        // Add to sections
        if (Schema::hasTable('sections')) {
            Schema::table('sections', function (Blueprint $table) {
                if (!Schema::hasColumn('sections', 'is_delete')) {
                    $table->tinyInteger('is_delete')->default(0)->after('status');
                }
            });
        }

        // Add to students
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (!Schema::hasColumn('students', 'is_delete')) {
                    $table->tinyInteger('is_delete')->default(0)->after('status');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('academic_years')) {
            Schema::table('academic_years', function (Blueprint $table) {
                if (Schema::hasColumn('academic_years', 'is_delete')) {
                    $table->dropColumn('is_delete');
                }
            });
        }

        if (Schema::hasTable('classes')) {
            Schema::table('classes', function (Blueprint $table) {
                if (Schema::hasColumn('classes', 'is_delete')) {
                    $table->dropColumn('is_delete');
                }
            });
        }

        if (Schema::hasTable('sections')) {
            Schema::table('sections', function (Blueprint $table) {
                if (Schema::hasColumn('sections', 'is_delete')) {
                    $table->dropColumn('is_delete');
                }
            });
        }

        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (Schema::hasColumn('students', 'is_delete')) {
                    $table->dropColumn('is_delete');
                }
            });
        }
    }
};
