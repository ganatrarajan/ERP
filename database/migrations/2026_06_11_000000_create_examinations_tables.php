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
        // 1. Grade Scales
        if (!Schema::hasTable('grade_scales')) {
            Schema::create('grade_scales', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
                $table->string('grade');
                $table->string('description')->nullable();
                $table->decimal('grade_point', 4, 2)->default(0.00);
                $table->decimal('min_percentage', 5, 2)->default(0.00);
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
            });
        }

        // 2. Exam Types
        if (!Schema::hasTable('exam_types')) {
            Schema::create('exam_types', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
                $table->string('name');
                $table->text('description')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->tinyInteger('is_delete')->default(0);
                $table->timestamps();
            });
        }

        // 3. Exams
        if (!Schema::hasTable('exams')) {
            Schema::create('exams', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
                $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
                $table->foreignId('exam_type_id')->constrained('exam_types')->onDelete('cascade');
                $table->string('name');
                $table->date('start_date');
                $table->date('end_date');
                $table->text('description')->nullable();
                $table->enum('status', ['draft', 'published'])->default('draft');
                $table->tinyInteger('is_delete')->default(0);
                $table->timestamps();
            });
        }

        // 4. Exam Schedules
        if (!Schema::hasTable('exam_schedules')) {
            Schema::create('exam_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
                $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
                $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
                $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
                $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
                $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
                $table->date('exam_date');
                $table->time('start_time');
                $table->time('end_time');
                $table->integer('max_marks')->default(100);
                $table->tinyInteger('is_delete')->default(0);
                $table->timestamps();
            });
        }

        // 5. Exam Marks
        if (!Schema::hasTable('exam_marks')) {
            Schema::create('exam_marks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
                $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
                $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
                $table->foreignId('exam_schedule_id')->constrained('exam_schedules')->onDelete('cascade');
                $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
                $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
                $table->decimal('marks_obtained', 5, 2)->nullable();
                $table->foreignId('grade_id')->nullable()->constrained('grade_scales')->onDelete('set null');
                $table->string('remarks')->nullable();
                $table->foreignId('entered_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
            });
        }

        // 6. Report Card Templates
        if (!Schema::hasTable('report_card_templates')) {
            Schema::create('report_card_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('cascade');
                $table->string('name');
                $table->string('template_key');
                $table->text('description')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
            });
        }

        // 7. Subjects Table Enhancements
        Schema::table('subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('subjects', 'evaluation_type')) {
                $table->enum('evaluation_type', ['marks', 'grades'])->default('marks')->after('description');
            }
            if (!Schema::hasColumn('subjects', 'subject_category')) {
                $table->enum('subject_category', ['scholastic', 'co_scholastic'])->default('scholastic')->after('evaluation_type');
            }
            if (!Schema::hasColumn('subjects', 'maximum_marks')) {
                $table->integer('maximum_marks')->default(100)->after('subject_category');
            }
            if (!Schema::hasColumn('subjects', 'passing_marks')) {
                $table->integer('passing_marks')->default(35)->after('maximum_marks');
            }
            if (!Schema::hasColumn('subjects', 'report_card_visibility')) {
                $table->enum('report_card_visibility', ['included_in_result', 'display_only', 'hidden'])->default('included_in_result')->after('passing_marks');
            }
            if (!Schema::hasColumn('subjects', 'grade_scale_id')) {
                $table->foreignId('grade_scale_id')->nullable()->after('report_card_visibility')->constrained('grade_scales')->onDelete('set null');
            }
        });

        // 8. School Table Enhancements (Default Report Card Template)
        Schema::table('schools', function (Blueprint $table) {
            if (!Schema::hasColumn('schools', 'default_report_card_template')) {
                $table->string('default_report_card_template')->default('basic')->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Revert school table enhancement
        Schema::table('schools', function (Blueprint $table) {
            if (Schema::hasColumn('schools', 'default_report_card_template')) {
                $table->dropColumn('default_report_card_template');
            }
        });

        // 2. Revert subject table enhancements
        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasColumn('subjects', 'grade_scale_id')) {
                $table->dropForeign(['grade_scale_id']);
                $table->dropColumn('grade_scale_id');
            }
            $table->dropColumn([
                'evaluation_type',
                'subject_category',
                'maximum_marks',
                'passing_marks',
                'report_card_visibility'
            ]);
        });

        // 3. Drop tables in correct dependency order
        Schema::dropIfExists('report_card_templates');
        Schema::dropIfExists('exam_marks');
        Schema::dropIfExists('exam_schedules');
        Schema::dropIfExists('exams');
        Schema::dropIfExists('exam_types');
        Schema::dropIfExists('grade_scales');
    }
};
