<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('password')->nullable()->after('status');
            $table->tinyInteger('password_changed')->default(0)->after('password');
        });

        // Set default passwords for existing students (using chunkById to avoid memory limit issues)
        DB::table('students')->orderBy('id')->chunkById(100, function ($students) {
            foreach ($students as $student) {
                DB::table('students')
                    ->where('id', $student->id)
                    ->update([
                        'password' => Hash::make($student->admission_no),
                        'password_changed' => 0
                    ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['password', 'password_changed']);
        });
    }
};
