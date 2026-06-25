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
        if (Schema::hasTable('fee_discounts')) {
            Schema::table('fee_discounts', function (Blueprint $table) {
                if (!Schema::hasColumn('fee_discounts', 'academic_year_id')) {
                    $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('cascade');
                }
            });
        }

        if (Schema::hasTable('fee_fine_rules')) {
            Schema::table('fee_fine_rules', function (Blueprint $table) {
                if (!Schema::hasColumn('fee_fine_rules', 'academic_year_id')) {
                    $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('cascade');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('fee_discounts')) {
            Schema::table('fee_discounts', function (Blueprint $table) {
                if (Schema::hasColumn('fee_discounts', 'academic_year_id')) {
                    $table->dropForeign(['academic_year_id']);
                    $table->dropColumn('academic_year_id');
                }
            });
        }

        if (Schema::hasTable('fee_fine_rules')) {
            Schema::table('fee_fine_rules', function (Blueprint $table) {
                if (Schema::hasColumn('fee_fine_rules', 'academic_year_id')) {
                    $table->dropForeign(['academic_year_id']);
                    $table->dropColumn('academic_year_id');
                }
            });
        }
    }
};
