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
        Schema::table('students', function (Blueprint $table) {
            $table->string('gr_no')->nullable()->after('admission_no');
            $table->string('house')->nullable()->after('status');
            $table->string('category')->nullable()->after('gender'); // General, OBC, SC, ST, EWS, SEBC, etc.
            $table->string('religion')->nullable()->after('category');
            $table->string('nationality')->nullable()->default('Indian')->after('religion');
            $table->string('aadhaar_no', 12)->nullable()->after('nationality');
            $table->string('pen_no', 20)->nullable()->after('aadhaar_no'); // Personal Education Number
            $table->string('udise_no', 20)->nullable()->after('pen_no');
            
            // Previous school details
            $table->string('previous_school_name')->nullable()->after('address');
            $table->string('previous_school_tc_no')->nullable()->after('previous_school_name');
            $table->date('previous_school_tc_date')->nullable()->after('previous_school_tc_no');

            // Emergency contact
            $table->string('emergency_contact_name')->nullable()->after('previous_school_tc_date');
            $table->string('emergency_contact_mobile')->nullable()->after('emergency_contact_name');
            $table->string('emergency_contact_email')->nullable()->after('emergency_contact_mobile');
        });

        Schema::create('student_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->string('document_name');
            $table->string('document_path');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_documents');
        
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'gr_no',
                'house',
                'category',
                'religion',
                'nationality',
                'aadhaar_no',
                'pen_no',
                'udise_no',
                'previous_school_name',
                'previous_school_tc_no',
                'previous_school_tc_date',
                'emergency_contact_name',
                'emergency_contact_mobile',
                'emergency_contact_email',
            ]);
        });
    }
};
