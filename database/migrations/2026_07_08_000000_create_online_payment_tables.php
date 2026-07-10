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
        // 1. Payment Gateway Configs
        Schema::create('payment_gateway_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->unique()->constrained('schools')->onDelete('cascade');
            $table->string('gateway_name')->default('Razorpay');
            $table->string('key_id')->nullable();
            $table->text('key_secret')->nullable(); // stored encrypted
            $table->text('webhook_secret')->nullable(); // stored encrypted
            $table->enum('mode', ['test', 'live'])->default('test');
            $table->string('currency', 10)->default('INR');
            $table->boolean('active')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 2. Online Payment Transactions (Audit Trail)
        Schema::create('online_payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('installment_id')->constrained('fee_installments')->onDelete('cascade');
            $table->string('receipt_no')->nullable()->unique();
            $table->string('gateway_name');
            $table->string('order_id')->nullable()->index();
            $table->string('payment_id')->nullable()->index();
            $table->text('signature')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('INR');
            $table->string('payment_method')->nullable();
            $table->enum('status', ['pending', 'successful', 'failed'])->default('pending');
            $table->timestamp('transaction_date')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('online_payment_transactions');
        Schema::dropIfExists('payment_gateway_configs');
    }
};
