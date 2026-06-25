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
        // 1. Fee Types
        Schema::create('fee_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->boolean('is_optional')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('is_delete')->default(false);
            $table->timestamps();
        });

        // 2. Fee Structures
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('is_delete')->default(false);
            $table->timestamps();
        });

        // 3. Fee Structure Items
        Schema::create('fee_structure_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_structure_id')->constrained('fee_structures')->onDelete('cascade');
            $table->foreignId('fee_type_id')->constrained('fee_types')->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });

        // 4. Fee Installments
        Schema::create('fee_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('fee_structure_id')->constrained('fee_structures')->onDelete('cascade');
            $table->string('installment_name');
            $table->date('due_date');
            $table->decimal('amount', 12, 2);
            $table->integer('sort_order')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('is_delete')->default(false);
            $table->timestamps();
        });

        // 5. Student Fee Assignments
        Schema::create('student_fee_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('fee_structure_id')->constrained('fee_structures')->onDelete('cascade');
            $table->date('assigned_date');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        // 6. Student Optional Fees
        Schema::create('student_optional_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('fee_type_id')->constrained('fee_types')->onDelete('cascade');
            $table->timestamps();
        });

        // 7. Fee Discounts
        Schema::create('fee_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->enum('discount_type', ['fixed', 'percentage']);
            $table->decimal('discount_value', 12, 2);
            $table->string('reason')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('is_delete')->default(false);
            $table->timestamps();
        });

        // 8. Fee Fine Rules
        Schema::create('fee_fine_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name');
            $table->enum('fine_type', ['fixed', 'per_day']);
            $table->decimal('fine_value', 12, 2);
            $table->integer('grace_days')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('is_delete')->default(false);
            $table->timestamps();
        });

        // 9. Fee Collections
        Schema::create('fee_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('installment_id')->constrained('fee_installments')->onDelete('cascade');
            $table->decimal('amount_due', 12, 2);
            $table->decimal('amount_paid', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('fine_amount', 12, 2)->default(0.00);
            $table->date('payment_date');
            $table->string('payment_method');
            $table->string('transaction_reference')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('collected_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // 10. Fee Receipts
        Schema::create('fee_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('receipt_number')->unique();
            $table->foreignId('collection_id')->constrained('fee_collections')->onDelete('cascade');
            $table->timestamp('generated_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_receipts');
        Schema::dropIfExists('fee_collections');
        Schema::dropIfExists('fee_fine_rules');
        Schema::dropIfExists('fee_discounts');
        Schema::dropIfExists('student_optional_fees');
        Schema::dropIfExists('student_fee_assignments');
        Schema::dropIfExists('fee_installments');
        Schema::dropIfExists('fee_structure_items');
        Schema::dropIfExists('fee_structures');
        Schema::dropIfExists('fee_types');
    }
};
