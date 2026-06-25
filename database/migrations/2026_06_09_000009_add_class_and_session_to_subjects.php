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
        Schema::table('subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('subjects', 'academic_year_id')) {
                $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('cascade');
            }
            if (!Schema::hasColumn('subjects', 'class_id')) {
                $table->foreignId('class_id')->nullable()->constrained('classes')->onDelete('cascade');
            }
            if (!Schema::hasColumn('subjects', 'section_id')) {
                $table->foreignId('section_id')->nullable()->constrained('sections')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasColumn('subjects', 'academic_year_id')) {
                $table->dropForeign(['academic_year_id']);
                $table->dropColumn('academic_year_id');
            }
            if (Schema::hasColumn('subjects', 'class_id')) {
                $table->dropForeign(['class_id']);
                $table->dropColumn('class_id');
            }
            if (Schema::hasColumn('subjects', 'section_id')) {
                $table->dropForeign(['section_id']);
                $table->dropColumn('section_id');
            }
        });
    }
};
