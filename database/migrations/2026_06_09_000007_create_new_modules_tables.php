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
        // 1. Subjects Table
        if (!Schema::hasTable('subjects')) {
            Schema::create('subjects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
                $table->string('name');
                $table->string('code')->nullable();
                $table->text('description')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->tinyInteger('is_delete')->default(0);
                $table->timestamps();
            });
        }

        // 2. Attendances Table
        if (!Schema::hasTable('attendances')) {
            Schema::create('attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
                $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
                $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
                $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
                $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
                $table->date('attendance_date');
                $table->string('status'); // Store 'Present', 'Absent', 'Late', 'Half Day', 'Leave', 'H'
                $table->text('remarks')->nullable();
                $table->foreignId('marked_by')->nullable()->constrained('users')->onDelete('set null');
                $table->tinyInteger('is_delete')->default(0);
                $table->timestamps();
            });
        }

        // 3. Homeworks Table
        if (!Schema::hasTable('homeworks')) {
            Schema::create('homeworks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
                $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
                $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
                $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
                $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
                $table->string('title');
                $table->text('description');
                $table->string('attachment')->nullable();
                $table->date('submission_date');
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->tinyInteger('is_delete')->default(0);
                $table->timestamps();
            });
        }

        // 4. Notices Table
        if (!Schema::hasTable('notices')) {
            Schema::create('notices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
                $table->string('title');
                $table->text('description');
                $table->date('notice_date');
                $table->enum('target_type', ['Entire School', 'Class Wise', 'Section Wise']);
                $table->foreignId('class_id')->nullable()->constrained('classes')->onDelete('set null');
                $table->foreignId('section_id')->nullable()->constrained('sections')->onDelete('set null');
                $table->string('attachment')->nullable();
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->tinyInteger('is_delete')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notices');
        Schema::dropIfExists('homeworks');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('subjects');
    }
};
