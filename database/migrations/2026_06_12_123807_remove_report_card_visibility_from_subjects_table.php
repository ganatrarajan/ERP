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
            if (Schema::hasColumn('subjects', 'report_card_visibility')) {
                $table->dropColumn('report_card_visibility');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('subjects', 'report_card_visibility')) {
                $table->enum('report_card_visibility', ['included_in_result', 'display_only', 'hidden'])
                      ->default('included_in_result')
                      ->after('passing_marks');
            }
        });
    }
};
