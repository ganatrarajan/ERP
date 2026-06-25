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
        if (!Schema::hasTable('report_card_setups')) {
            Schema::create('report_card_setups', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
                $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
                $table->string('name');
                $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
                $table->foreignId('section_id')->nullable()->constrained('sections')->onDelete('cascade');
                $table->foreignId('exam_id_1')->constrained('exams')->onDelete('cascade');
                $table->foreignId('exam_id_2')->nullable()->constrained('exams')->onDelete('cascade');
                $table->enum('status', ['draft', 'published'])->default('draft');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_card_setups');
    }
};
