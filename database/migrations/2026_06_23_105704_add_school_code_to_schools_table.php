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
            $table->string('school_code', 5)->nullable()->unique()->after('id');
        });

        $schools = \Illuminate\Support\Facades\DB::table('schools')->get();
        foreach ($schools as $school) {
            do {
                $code = (string) random_int(10000, 99999);
                $exists = \Illuminate\Support\Facades\DB::table('schools')->where('school_code', $code)->exists();
            } while ($exists);
            \Illuminate\Support\Facades\DB::table('schools')->where('id', $school->id)->update(['school_code' => $code]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('school_code');
        });
    }
};
