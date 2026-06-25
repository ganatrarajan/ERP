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
        Schema::table('subjects', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['grade_scale_id']);
            // Change column to text to hold multiple serialized grade IDs
            $table->text('grade_scale_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            // Restore back to foreign key if needed
            $table->unsignedBigInteger('grade_scale_id')->nullable()->change();
            $table->foreign('grade_scale_id')->references('id')->on('grade_scales')->onDelete('set null');
        });
    }
};
