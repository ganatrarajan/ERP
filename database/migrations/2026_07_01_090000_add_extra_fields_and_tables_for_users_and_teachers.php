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
        Schema::table('users', function (Blueprint $table) {
            // Common User fields
            $table->string('employee_id')->nullable()->after('school_id');
            $table->string('gender')->nullable()->after('name');
            $table->date('dob')->nullable()->after('gender');
            $table->string('aadhaar_no', 12)->nullable()->after('dob');
            $table->string('pan_no', 10)->nullable()->after('aadhaar_no');
            $table->text('address')->nullable()->after('pan_no');
            $table->string('emergency_contact_name')->nullable()->after('address');
            $table->string('emergency_contact_mobile')->nullable()->after('emergency_contact_name');
            $table->string('profile_photo')->nullable()->after('emergency_contact_mobile');

            // Teacher fields
            $table->string('teacher_code')->nullable()->after('profile_photo');
            $table->string('qualification')->nullable()->after('teacher_code');
            $table->string('experience')->nullable()->after('qualification');
            $table->date('joining_date')->nullable()->after('experience');
            $table->string('department')->nullable()->after('joining_date');
            $table->string('designation')->nullable()->after('department');
            $table->string('employment_type')->nullable()->after('designation'); // Full-time, Part-time, Contract, etc.
        });

        // User Documents table
        Schema::create('user_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('document_name');
            $table->string('document_path');
            $table->timestamps();
        });

        // Teacher Assignments table
        Schema::create('teacher_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->onDelete('cascade');
            $table->boolean('is_class_teacher')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_assignments');
        Schema::dropIfExists('user_documents');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'employee_id',
                'gender',
                'dob',
                'aadhaar_no',
                'pan_no',
                'address',
                'emergency_contact_name',
                'emergency_contact_mobile',
                'profile_photo',
                'teacher_code',
                'qualification',
                'experience',
                'joining_date',
                'department',
                'designation',
                'employment_type',
            ]);
        });
    }
};
