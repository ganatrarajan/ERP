<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Seed initial default landing page and contact settings
        DB::table('system_settings')->insert([
            ['key' => 'whatsapp_number', 'value' => '919999999999', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'contact_phone', 'value' => '+91 99999 99999', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'contact_email', 'value' => 'support@eduvorax.com', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'founding_offer_text', 'value' => 'Get EduvoraX school management software free for your first year as part of our founding-school program.', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
