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
        Schema::table('fee_discounts', function (Blueprint $table) {
            $table->integer('max_uses')->default(1)->after('status');
            $table->integer('used_count')->default(0)->after('max_uses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_discounts', function (Blueprint $table) {
            $table->dropColumn(['max_uses', 'used_count']);
        });
    }
};
