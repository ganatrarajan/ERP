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
        // 1. Add is_optional to subjects
        Schema::table('subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('subjects', 'is_optional')) {
                $table->boolean('is_optional')->default(false)->after('status');
            }
        });

        // 2. Create student_optional_subjects table
        if (!Schema::hasTable('student_optional_subjects')) {
            Schema::create('student_optional_subjects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
                $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
                $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
                $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
                $table->timestamps();

                $table->unique(['academic_year_id', 'student_id', 'subject_id'], 'student_optional_sub_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_optional_subjects');

        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasColumn('subjects', 'is_optional')) {
                $table->dropColumn('is_optional');
            }
        });
    }
};
