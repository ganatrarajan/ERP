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
        Schema::table('schools', function (Blueprint $table) {
            $table->foreignId('mobile_academic_year_id')->nullable()->after('school_code')->constrained('academic_years')->onDelete('set null');
        });

        // Set mobile_academic_year_id for existing schools to the current academic year
        $schools = \Illuminate\Support\Facades\DB::table('schools')->get();
        foreach ($schools as $school) {
            $currentYearId = \Illuminate\Support\Facades\DB::table('academic_years')
                ->where('school_id', $school->id)
                ->where('is_current', true)
                ->where('is_delete', 0)
                ->value('id');
            if ($currentYearId) {
                \Illuminate\Support\Facades\DB::table('schools')
                    ->where('id', $school->id)
                    ->update(['mobile_academic_year_id' => $currentYearId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            if (Schema::hasColumn('schools', 'mobile_academic_year_id')) {
                $table->dropForeign(['mobile_academic_year_id']);
                $table->dropColumn('mobile_academic_year_id');
            }
        });
    }
};
