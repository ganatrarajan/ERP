<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop foreign key if it exists
        try {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropForeign(['grade_scale_id']);
            });
        } catch (\Exception $e) {
            // Ignore if foreign key doesn't exist
        }

        // Drop index if it exists
        try {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropIndex(['grade_scale_id']);
            });
        } catch (\Exception $e) {
            // Ignore if index doesn't exist
        }

        // Change column type
        Schema::table('subjects', function (Blueprint $table) {
            $table->text('grade_scale_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->unsignedBigInteger('grade_scale_id')->nullable()->change();
            $table->foreign('grade_scale_id')
                ->references('id')
                ->on('grade_scales')
                ->nullOnDelete();
        });
    }
};