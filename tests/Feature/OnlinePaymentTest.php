<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Models\AcademicYear;
use App\Models\FeeStructure;
use App\Models\FeeInstallment;
use App\Models\StudentFeeAssignment;
use App\Models\PaymentGatewayConfig;
use App\Models\OnlinePaymentTransaction;
use App\Models\FeeCollection;
use App\Models\FeeReceipt;
use App\Models\ClassModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OnlinePaymentTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $superAdmin;
    protected User $schoolAdmin;
    protected Student $student;
    protected AcademicYear $academicYear;
    protected FeeInstallment $installment;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup seed data
        $this->school = School::create([
            'name' => 'Test High School',
            'email' => 'test@school.com',
            'phone' => '1234567890',
            'address' => '123 Main St'
        ]);

        $this->academicYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'title' => '2026-2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'is_current' => true,
        ]);

        // Roles
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $schoolAdminRole = Role::firstOrCreate(['name' => 'School Admin', 'guard_name' => 'web', 'school_id' => $this->school->id]);

        // Users
        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@admin.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->superAdmin->assignRole($superAdminRole);

        $this->schoolAdmin = User::create([
            'school_id' => $this->school->id,
            'name' => 'School Admin',
            'email' => 'admin@school.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->schoolAdmin->assignRole($schoolAdminRole);

        // Student
        $this->student = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'admission_no' => 'ADM-001',
            'roll_no' => 1,
            'gender' => 'Male',
            'date_of_birth' => '2015-05-15',
            'admission_date' => '2026-06-01',
            'email' => 'john@doe.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        // Class Model creation
        $class = ClassModel::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Class 1',
            'status' => 'active',
        ]);

        // Fees structure and installment setup
        $structure = FeeStructure::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $class->id,
            'name' => 'General Fee Structure',
            'status' => 'active',
        ]);

        $this->installment = FeeInstallment::create([
            'school_id' => $this->school->id,
            'fee_structure_id' => $structure->id,
            'installment_name' => 'Term 1',
            'due_date' => '2026-09-01',
            'amount' => 500.00,
            'status' => 'active',
        ]);

        // Assign fee to student
        StudentFeeAssignment::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'student_id' => $this->student->id,
            'fee_structure_id' => $structure->id,
            'assigned_date' => '2026-06-01',
        ]);

        // Seed the module Online Payments and its permissions
        $module = \App\Models\Module::updateOrCreate(
            ['slug' => 'online-payments'],
            [
                'name' => 'Online Payments',
                'icon' => 'credit-card',
                'description' => 'Gateway configuration',
                'status' => 'active',
            ]
        );

        DB::table('school_modules')->insert([
            'school_id' => $this->school->id,
            'module_id' => $module->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->schoolAdmin->givePermissionTo(['payment_gateway.manage', 'payment_gateway.view']);

        $this->token = $this->student->createToken('test-token', ['school:' . $this->school->id])->plainTextToken;
    }

    /**
     * Test config transparent encryption casts.
     */
    public function test_gateway_credentials_are_encrypted(): void
    {
        $config = PaymentGatewayConfig::create([
            'school_id' => $this->school->id,
            'gateway_name' => 'Razorpay',
            'key_id' => 'rzp_test_123',
            'key_secret' => 'super_secret_value',
            'webhook_secret' => 'webhook_secret_value',
            'active' => true
        ]);

        // Check DB row raw content
        $rawRow = DB::table('payment_gateway_configs')->where('id', $config->id)->first();
        
        $this->assertNotEquals('super_secret_value', $rawRow->key_secret);
        $this->assertNotEquals('webhook_secret_value', $rawRow->webhook_secret);

        // Check model automatically decrypts
        $loaded = PaymentGatewayConfig::find($config->id);
        $this->assertEquals('super_secret_value', $loaded->key_secret);
        $this->assertEquals('webhook_secret_value', $loaded->webhook_secret);
    }

    /**
     * Test parent order creation via mobile API.
     */
    public function test_parent_can_create_order(): void
    {
        PaymentGatewayConfig::create([
            'school_id' => $this->school->id,
            'gateway_name' => 'Razorpay',
            'key_id' => 'rzp_test_123',
            'key_secret' => 'secret',
            'webhook_secret' => 'secret',
            'active' => true
        ]);

        // Mock Razorpay order creation Http request
        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_ABC123XYZ',
                'amount' => 50000,
                'currency' => 'INR',
                'status' => 'created'
            ], 200)
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/mobile/fees/create-order', [
                'installment_id' => $this->installment->id,
                'amount' => 500.00
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'order_id',
                'amount',
                'key_id',
                'gateway'
            ])
            ->assertJson([
                'success' => true,
                'order_id' => 'order_ABC123XYZ',
                'amount' => 500.00,
                'key_id' => 'rzp_test_123',
                'gateway' => 'Razorpay'
            ]);

        $this->assertDatabaseHas('online_payment_transactions', [
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'installment_id' => $this->installment->id,
            'amount' => 500.00,
            'order_id' => 'order_ABC123XYZ',
            'status' => 'pending'
        ]);
    }

    /**
     * Test signature verification and capture logic (Idempotence, receipts generation).
     */
    public function test_parent_can_verify_and_capture_payment(): void
    {
        PaymentGatewayConfig::create([
            'school_id' => $this->school->id,
            'gateway_name' => 'Razorpay',
            'key_id' => 'rzp_test_123',
            'key_secret' => 'testsecret',
            'webhook_secret' => 'webhooksecret',
            'active' => true
        ]);

        $orderId = 'order_ABC123';
        $paymentId = 'pay_XYZ789';
        
        // Generate valid signature using HMAC SHA256
        $signature = hash_hmac('sha256', $orderId . '|' . $paymentId, 'testsecret');

        $transaction = OnlinePaymentTransaction::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'installment_id' => $this->installment->id,
            'amount' => 500.00,
            'gateway_name' => 'Razorpay',
            'order_id' => $orderId,
            'status' => 'pending'
        ]);

        // Mock Razorpay payment method fetch call
        Http::fake([
            "https://api.razorpay.com/v1/payments/{$paymentId}" => Http::response([
                'id' => $paymentId,
                'method' => 'upi',
                'status' => 'captured'
            ], 200)
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/mobile/fees/verify-payment', [
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        // Verify database updates
        $this->assertDatabaseHas('online_payment_transactions', [
            'id' => $transaction->id,
            'status' => 'successful',
            'payment_id' => $paymentId,
            'payment_method' => 'Online - Upi'
        ]);

        $this->assertDatabaseHas('fee_collections', [
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'installment_id' => $this->installment->id,
            'amount_paid' => 500.00,
            'payment_method' => 'Online - Upi',
            'transaction_reference' => $paymentId
        ]);

        $this->assertDatabaseHas('fee_receipts', [
            'school_id' => $this->school->id,
        ]);

        // Verify IDEMPOTENCE (Running verify again should not generate second collection!)
        $responseSecond = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/mobile/fees/verify-payment', [
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature,
            ]);

        $responseSecond->assertStatus(200);

        // Assert fee collection count is still exactly 1
        $this->assertEquals(1, FeeCollection::where('student_id', $this->student->id)->count());
        $this->assertEquals(1, FeeReceipt::where('school_id', $this->school->id)->count());
    }
}
