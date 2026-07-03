<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE notices MODIFY COLUMN target_type ENUM('Entire School', 'Class Wise', 'Section Wise', 'Teacher Only') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE notices MODIFY COLUMN target_type ENUM('Entire School', 'Class Wise', 'Section Wise') NOT NULL");
    }
};
