<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentAcademicRecord;
use App\Models\FeeType;
use App\Models\FeeStructure;
use App\Models\FeeStructureItem;
use App\Models\FeeInstallment;
use App\Models\StudentFeeAssignment;
use App\Models\StudentOptionalFee;
use App\Models\FeeCollection;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeesManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $schoolAdmin;
    protected School $school;
    protected AcademicYear $academicYear;
    protected ClassModel $classModel;
    protected Section $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\ModuleSeeder::class);

        // Retrieve School Admin and School
        $this->schoolAdmin = User::whereHas('roles', function ($query) {
            $query->where('name', 'School Admin');
        })->first();
        $this->school = School::find($this->schoolAdmin->school_id);

        // Create Academic Year
        $this->academicYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'title' => '2025-2026',
            'start_date' => '2025-06-01',
            'end_date' => '2026-05-31',
            'is_current' => true,
            'status' => 'active'
        ]);

        // Create Class
        $this->classModel = ClassModel::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Grade 10',
            'status' => 'active'
        ]);

        // Create Section
        $this->section = Section::create([
            'school_id' => $this->school->id,
            'class_id' => $this->classModel->id,
            'name' => 'A',
            'status' => 'active'
        ]);
    }

    public function test_fee_type_deletion_blocked_when_used_in_active_structure()
    {
        $feeType = FeeType::create([
            'school_id' => $this->school->id,
            'name' => 'Tuition Fee',
            'code' => 'TUIT',
            'is_optional' => false,
            'status' => 'active'
        ]);

        $feeStructure = FeeStructure::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->classModel->id,
            'name' => 'Grade 10 Standard Structure',
            'status' => 'active'
        ]);

        FeeStructureItem::create([
            'fee_structure_id' => $feeStructure->id,
            'fee_type_id' => $feeType->id,
            'amount' => 5000.00
        ]);

        // Attempt to delete fee type
        $response = $this->actingAs($this->schoolAdmin)
            ->deleteJson("/api/fee-types/{$feeType->id}");

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cannot delete this fee type. It is currently used in one or more active fee structures.'
            ]);

        $this->assertDatabaseHas('fee_types', [
            'id' => $feeType->id,
            'is_delete' => false
        ]);
    }

    public function test_fee_type_deletion_blocked_when_used_in_optional_assignment()
    {
        $feeType = FeeType::create([
            'school_id' => $this->school->id,
            'name' => 'Bus Fee',
            'code' => 'BUS',
            'is_optional' => true,
            'status' => 'active'
        ]);

        $student = Student::create([
            'school_id' => $this->school->id,
            'admission_no' => 'ADM-001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'Male',
            'date_of_birth' => '2010-05-15',
            'admission_date' => '2025-06-01',
            'status' => 'active'
        ]);

        StudentAcademicRecord::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->classModel->id,
            'section_id' => $this->section->id,
            'roll_no' => '01'
        ]);

        StudentOptionalFee::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'student_id' => $student->id,
            'fee_type_id' => $feeType->id
        ]);

        // Attempt to delete fee type
        $response = $this->actingAs($this->schoolAdmin)
            ->deleteJson("/api/fee-types/{$feeType->id}");

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cannot delete this fee type. It is currently assigned as an optional fee to one or more students.'
            ]);

        $this->assertDatabaseHas('fee_types', [
            'id' => $feeType->id,
            'is_delete' => false
        ]);
    }

    public function test_fee_structure_deletion_blocked_when_assigned_to_student()
    {
        $feeType = FeeType::create([
            'school_id' => $this->school->id,
            'name' => 'Tuition Fee',
            'code' => 'TUIT',
            'is_optional' => false,
            'status' => 'active'
        ]);

        $feeStructure = FeeStructure::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->classModel->id,
            'name' => 'Grade 10 Standard Structure',
            'status' => 'active'
        ]);

        FeeStructureItem::create([
            'fee_structure_id' => $feeStructure->id,
            'fee_type_id' => $feeType->id,
            'amount' => 5000.00
        ]);

        $student = Student::create([
            'school_id' => $this->school->id,
            'admission_no' => 'ADM-002',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'gender' => 'Female',
            'date_of_birth' => '2010-08-20',
            'admission_date' => '2025-06-01',
            'status' => 'active'
        ]);

        StudentAcademicRecord::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->classModel->id,
            'section_id' => $this->section->id,
            'roll_no' => '02'
        ]);

        StudentFeeAssignment::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'student_id' => $student->id,
            'fee_structure_id' => $feeStructure->id,
            'assigned_date' => '2025-06-01'
        ]);

        // Attempt to delete fee structure
        $response = $this->actingAs($this->schoolAdmin)
            ->deleteJson("/api/fee-structures/{$feeStructure->id}");

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cannot delete this fee structure. It is currently assigned to one or more students.'
            ]);

        $this->assertDatabaseHas('fee_structures', [
            'id' => $feeStructure->id,
            'is_delete' => false
        ]);
    }

    public function test_fee_structure_deletion_blocked_when_has_collections()
    {
        $feeType = FeeType::create([
            'school_id' => $this->school->id,
            'name' => 'Tuition Fee',
            'code' => 'TUIT',
            'is_optional' => false,
            'status' => 'active'
        ]);

        $feeStructure = FeeStructure::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->classModel->id,
            'name' => 'Grade 10 Standard Structure',
            'status' => 'active'
        ]);

        FeeStructureItem::create([
            'fee_structure_id' => $feeStructure->id,
            'fee_type_id' => $feeType->id,
            'amount' => 5000.00
        ]);

        $installment = FeeInstallment::create([
            'school_id' => $this->school->id,
            'fee_structure_id' => $feeStructure->id,
            'installment_name' => 'Term 1',
            'due_date' => '2025-07-01',
            'amount' => 5000.00,
            'sort_order' => 1,
            'status' => 'active'
        ]);

        $student = Student::create([
            'school_id' => $this->school->id,
            'admission_no' => 'ADM-003',
            'first_name' => 'Alice',
            'last_name' => 'Brown',
            'gender' => 'Female',
            'date_of_birth' => '2010-12-05',
            'admission_date' => '2025-06-01',
            'status' => 'active'
        ]);

        StudentAcademicRecord::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->classModel->id,
            'section_id' => $this->section->id,
            'roll_no' => '03'
        ]);

        StudentFeeAssignment::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'student_id' => $student->id,
            'fee_structure_id' => $feeStructure->id,
            'assigned_date' => '2025-06-01'
        ]);

        FeeCollection::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'student_id' => $student->id,
            'installment_id' => $installment->id,
            'amount_due' => 5000.00,
            'amount_paid' => 5000.00,
            'discount_amount' => 0.00,
            'fine_amount' => 0.00,
            'payment_date' => '2025-06-15',
            'payment_method' => 'Cash',
            'collected_by' => $this->schoolAdmin->id
        ]);

        // Delete the assignment so the first check passes and we hit the collections check
        StudentFeeAssignment::where('fee_structure_id', $feeStructure->id)->delete();

        // Attempt to delete fee structure
        $response = $this->actingAs($this->schoolAdmin)
            ->deleteJson("/api/fee-structures/{$feeStructure->id}");

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cannot delete this fee structure. Payments have already been collected against its installments.'
            ]);

        $this->assertDatabaseHas('fee_structures', [
            'id' => $feeStructure->id,
            'is_delete' => false
        ]);
    }

    public function test_student_fee_assignment_blocked_when_fee_structure_belongs_to_different_class()
    {
        $differentClass = ClassModel::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Grade 11',
            'status' => 'active'
        ]);

        $feeStructureOfDiffClass = FeeStructure::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $differentClass->id,
            'name' => 'Grade 11 Standard Structure',
            'status' => 'active'
        ]);

        $student = Student::create([
            'school_id' => $this->school->id,
            'admission_no' => 'ADM-X01',
            'first_name' => 'Bob',
            'last_name' => 'Doe',
            'gender' => 'Male',
            'date_of_birth' => '2010-05-15',
            'admission_date' => '2025-06-01',
            'status' => 'active'
        ]);

        StudentAcademicRecord::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->classModel->id, // Grade 10
            'section_id' => $this->section->id,
            'roll_no' => '10'
        ]);

        // Attempt individual assign
        $response = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/fee-assignments', [
                'academic_year_id' => $this->academicYear->id,
                'student_id' => $student->id,
                'fee_structure_id' => $feeStructureOfDiffClass->id,
                'assigned_date' => '2025-06-01',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => "The selected fee structure does not belong to the student's class."
            ]);

        // Attempt bulk assign to Class 10 with Class 11's fee structure
        $responseBulk = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/fee-assignments/bulk', [
                'academic_year_id' => $this->academicYear->id,
                'class_id' => $this->classModel->id,
                'fee_structure_id' => $feeStructureOfDiffClass->id,
                'assigned_date' => '2025-06-01',
            ]);

        $responseBulk->assertStatus(422)
            ->assertJson([
                'message' => "The selected fee structure does not belong to the selected class."
            ]);
    }

    public function test_fee_collection_with_partial_payment_and_discounts()
    {
        $feeType = FeeType::create([
            'school_id' => $this->school->id,
            'name' => 'Tuition Fee',
            'code' => 'TUIT',
            'is_optional' => false,
            'status' => 'active'
        ]);

        $feeStructure = FeeStructure::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->classModel->id,
            'name' => 'Grade 10 Standard Structure',
            'status' => 'active'
        ]);

        FeeStructureItem::create([
            'fee_structure_id' => $feeStructure->id,
            'fee_type_id' => $feeType->id,
            'amount' => 5000.00
        ]);

        $installment = FeeInstallment::create([
            'school_id' => $this->school->id,
            'fee_structure_id' => $feeStructure->id,
            'installment_name' => 'Term 1',
            'due_date' => '2025-07-01',
            'amount' => 5000.00,
            'sort_order' => 1,
            'status' => 'active'
        ]);

        $student = Student::create([
            'school_id' => $this->school->id,
            'admission_no' => 'ADM-004',
            'first_name' => 'Emma',
            'last_name' => 'Watson',
            'gender' => 'Female',
            'date_of_birth' => '2010-04-15',
            'admission_date' => '2025-06-01',
            'status' => 'active'
        ]);

        StudentAcademicRecord::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->classModel->id,
            'section_id' => $this->section->id,
            'roll_no' => '04'
        ]);

        StudentFeeAssignment::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'student_id' => $student->id,
            'fee_structure_id' => $feeStructure->id,
            'assigned_date' => '2025-06-01'
        ]);

        // Create a discount limit of 1 use
        $discount = \App\Models\FeeDiscount::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'student_id' => $student->id,
            'discount_type' => 'fixed',
            'discount_value' => 500.00,
            'reason' => 'Scholarship',
            'status' => 'active',
            'max_uses' => 1,
            'used_count' => 0
        ]);

        // Verify active discounts query returns the discount
        $calcService = app(\App\Services\FeeCalculationService::class);
        $activeDiscounts = $calcService->getStudentActiveDiscounts($student->id);
        $this->assertCount(1, $activeDiscounts);
        $this->assertEquals(500.00, $activeDiscounts[0]['discount_value']);

        // Collect partial payment: pay 2000, discount 500, out of 5000 due
        $response = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/fee-collections/collect', [
                'academic_year_id' => $this->academicYear->id,
                'student_id' => $student->id,
                'installment_id' => $installment->id,
                'amount_paid' => 2000.00,
                'discount_amount' => 500.00,
                'discount_id' => $discount->id,
                'fine_amount' => 0.00,
                'payment_date' => '2025-06-15',
                'payment_method' => 'Cash'
            ]);

        $response->assertStatus(201);

        // Assert database updates
        $this->assertDatabaseHas('fee_collections', [
            'student_id' => $student->id,
            'installment_id' => $installment->id,
            'amount_paid' => 2000.00,
            'discount_amount' => 500.00,
        ]);

        // Check that the discount's used_count has incremented to 1
        $discount->refresh();
        $this->assertEquals(1, $discount->used_count);

        // Verify that because max_uses is 1, the discount is no longer active / returned
        $activeDiscountsAfter = $calcService->getStudentActiveDiscounts($student->id);
        $this->assertCount(0, $activeDiscountsAfter);

        // Verify that the remaining dues show 2500 outstanding (5000 - 2000 paid - 500 discount)
        $dues = $calcService->getStudentFeeDues($student->id, $this->academicYear->id);
        $this->assertEquals(2500.00, $dues['outstanding_balance']);
        $this->assertEquals('Partial', $dues['installments'][0]['status']);
    }

    public function test_fee_reports_daily_and_monthly_grouping()
    {
        $feeType = FeeType::create([
            'school_id' => $this->school->id,
            'name' => 'Tuition Fee',
            'code' => 'TUIT',
            'is_optional' => false,
            'status' => 'active'
        ]);

        $feeStructure = FeeStructure::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->classModel->id,
            'name' => 'Grade 10 Standard Structure',
            'status' => 'active'
        ]);

        $installment = FeeInstallment::create([
            'school_id' => $this->school->id,
            'fee_structure_id' => $feeStructure->id,
            'installment_name' => 'Term 1',
            'due_date' => '2025-07-01',
            'amount' => 5000.00,
            'sort_order' => 1,
            'status' => 'active'
        ]);

        $student = Student::create([
            'school_id' => $this->school->id,
            'admission_no' => 'ADM-005',
            'first_name' => 'John',
            'last_name' => 'Watson',
            'gender' => 'Male',
            'date_of_birth' => '2010-04-15',
            'admission_date' => '2025-06-01',
            'status' => 'active'
        ]);

        StudentAcademicRecord::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->classModel->id,
            'section_id' => $this->section->id,
            'roll_no' => '05'
        ]);

        FeeCollection::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'student_id' => $student->id,
            'installment_id' => $installment->id,
            'amount_due' => 5000.00,
            'amount_paid' => 2000.00,
            'discount_amount' => 0.00,
            'fine_amount' => 0.00,
            'payment_date' => '2025-06-15',
            'payment_method' => 'Cash',
            'collected_by' => $this->schoolAdmin->id
        ]);

        // Daily Report
        $dailyResponse = $this->actingAs($this->schoolAdmin)
            ->getJson("/api/fee-reports?academic_year_id={$this->academicYear->id}&report_type=daily");
        $dailyResponse->assertStatus(200)
            ->assertJsonPath('report_type', 'daily')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.date', '2025-06-15')
            ->assertJsonPath('data.0.total_collected', 2000);

        // Monthly Report
        $monthlyResponse = $this->actingAs($this->schoolAdmin)
            ->getJson("/api/fee-reports?academic_year_id={$this->academicYear->id}&report_type=monthly");
        $monthlyResponse->assertStatus(200)
            ->assertJsonPath('report_type', 'monthly')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.month', '2025-06')
            ->assertJsonPath('data.0.total_collected', 2000);
    }

    public function test_academic_year_locking_enforced_in_mutations()
    {
        // 1. Create a locked academic year (is_current = false)
        $lockedYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'title' => '2024-2025',
            'start_date' => '2024-06-01',
            'end_date' => '2025-05-31',
            'is_current' => false,
            'status' => 'active'
        ]);

        $feeType = FeeType::create([
            'school_id' => $this->school->id,
            'name' => 'Tuition Fee',
            'code' => 'TUIT',
            'is_optional' => false,
            'status' => 'active'
        ]);

        // Attempting to create a fee structure under locked year should fail
        $response = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/fee-structures', [
                'school_id' => $this->school->id,
                'academic_year_id' => $lockedYear->id,
                'class_id' => $this->classModel->id,
                'name' => 'Locked Session Fee Structure',
                'description' => 'Test',
                'status' => 'active',
                'items' => [
                    ['fee_type_id' => $feeType->id, 'amount' => 5000.00]
                ],
                'installments' => [
                    ['installment_name' => 'Term 1', 'due_date' => '2024-07-01', 'amount' => 5000.00, 'sort_order' => 1]
                ]
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'This academic session is locked. Modifications are only allowed in the active session.'
            ]);

        // Create a fee structure in the current academic year to test edit/delete locking when it's under locked year
        $feeStructureInLockedYear = FeeStructure::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $lockedYear->id,
            'class_id' => $this->classModel->id,
            'name' => 'Legacy Fee Structure',
            'status' => 'active'
        ]);

        FeeStructureItem::create([
            'fee_structure_id' => $feeStructureInLockedYear->id,
            'fee_type_id' => $feeType->id,
            'amount' => 5000.00
        ]);

        $installment = FeeInstallment::create([
            'school_id' => $this->school->id,
            'fee_structure_id' => $feeStructureInLockedYear->id,
            'installment_name' => 'Term 1',
            'due_date' => '2024-07-01',
            'amount' => 5000.00,
            'sort_order' => 1,
            'status' => 'active'
        ]);

        // Attempting to edit a fee structure under locked year should fail
        $response = $this->actingAs($this->schoolAdmin)
            ->putJson("/api/fee-structures/{$feeStructureInLockedYear->id}", [
                'name' => 'Updated Name',
                'description' => 'Updated desc',
                'status' => 'active',
                'items' => [
                    ['fee_type_id' => $feeType->id, 'amount' => 5000.00]
                ],
                'installments' => [
                    ['installment_name' => 'Term 1', 'due_date' => '2024-07-01', 'amount' => 5000.00, 'sort_order' => 1]
                ]
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'This academic session is locked. Modifications are only allowed in the active session.'
            ]);

        // Attempting to delete a fee structure under locked year should fail
        $response = $this->actingAs($this->schoolAdmin)
            ->deleteJson("/api/fee-structures/{$feeStructureInLockedYear->id}");

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'This academic session is locked. Modifications are only allowed in the active session.'
            ]);

        // Attempting to clone a structure TO a locked year should fail
        $activeStructure = FeeStructure::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->classModel->id,
            'name' => 'Active Structure',
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->schoolAdmin)
            ->postJson("/api/fee-structures/{$activeStructure->id}/clone", [
                'academic_year_id' => $lockedYear->id,
                'class_id' => $this->classModel->id,
                'name' => 'Cloned to Legacy'
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'This academic session is locked. Modifications are only allowed in the active session.'
            ]);

        // 2. Student Assignment locking
        $student = Student::create([
            'school_id' => $this->school->id,
            'admission_no' => 'ADM-L01',
            'first_name' => 'Legacy',
            'last_name' => 'Student',
            'gender' => 'Male',
            'date_of_birth' => '2010-04-15',
            'admission_date' => '2024-06-01',
            'status' => 'active'
        ]);

        StudentAcademicRecord::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $lockedYear->id,
            'class_id' => $this->classModel->id,
            'section_id' => $this->section->id,
            'roll_no' => '99'
        ]);

        // Attempting individual assign under locked year should fail
        $response = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/fee-assignments', [
                'academic_year_id' => $lockedYear->id,
                'student_id' => $student->id,
                'fee_structure_id' => $feeStructureInLockedYear->id,
                'assigned_date' => '2024-06-01'
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'This academic session is locked. Modifications are only allowed in the active session.'
            ]);

        // Attempting bulk assign under locked year should fail
        $response = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/fee-assignments/bulk', [
                'academic_year_id' => $lockedYear->id,
                'class_id' => $this->classModel->id,
                'fee_structure_id' => $feeStructureInLockedYear->id,
                'assigned_date' => '2024-06-01'
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'This academic session is locked. Modifications are only allowed in the active session.'
            ]);

        // 3. Payment collection locking
        // Attempting to collect payment under locked year should fail
        $response = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/fee-collections/collect', [
                'academic_year_id' => $lockedYear->id,
                'student_id' => $student->id,
                'installment_id' => $installment->id,
                'amount_paid' => 1000.00,
                'payment_date' => '2024-06-15',
                'payment_method' => 'Cash'
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'This academic session is locked. Modifications are only allowed in the active session.'
            ]);
    }

    public function test_fee_discounts_and_fine_rules_scoped_by_academic_year()
    {
        // 1. Create a second academic year
        $anotherYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'title' => '2026-2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'is_current' => false,
            'status' => 'active'
        ]);

        $student = Student::create([
            'school_id' => $this->school->id,
            'admission_no' => 'ADM-T99',
            'first_name' => 'Emma',
            'last_name' => 'Watson',
            'gender' => 'Female',
            'date_of_birth' => '2010-04-15',
            'admission_date' => '2025-06-01',
            'status' => 'active'
        ]);

        // 2. Create discounts in different years
        $discountCurrent = \App\Models\FeeDiscount::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'student_id' => $student->id,
            'discount_type' => 'fixed',
            'discount_value' => 100.00,
            'status' => 'active'
        ]);

        $discountAnother = \App\Models\FeeDiscount::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $anotherYear->id,
            'student_id' => $student->id,
            'discount_type' => 'fixed',
            'discount_value' => 200.00,
            'status' => 'active'
        ]);

        // 3. Create fine rules in different years
        $fineRuleCurrent = \App\Models\FeeFineRule::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Current Year Fine',
            'fine_type' => 'fixed',
            'fine_value' => 10.00,
            'grace_days' => 5,
            'status' => 'active'
        ]);

        $fineRuleAnother = \App\Models\FeeFineRule::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $anotherYear->id,
            'name' => 'Another Year Fine',
            'fine_type' => 'fixed',
            'fine_value' => 20.00,
            'grace_days' => 5,
            'status' => 'active'
        ]);

        // 4. Test listing discounts - should default to current year
        $response = $this->actingAs($this->schoolAdmin)
            ->getJson('/api/fee-discounts');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'discounts')
            ->assertJsonPath('discounts.0.id', $discountCurrent->id);

        // Test listing discounts with explicit academic_year_id parameter
        $response = $this->actingAs($this->schoolAdmin)
            ->getJson("/api/fee-discounts?academic_year_id={$anotherYear->id}");
        $response->assertStatus(200)
            ->assertJsonCount(1, 'discounts')
            ->assertJsonPath('discounts.0.id', $discountAnother->id);

        // 5. Test listing fine rules - should default to current year
        $response = $this->actingAs($this->schoolAdmin)
            ->getJson('/api/fee-fine-rules');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'fine_rules')
            ->assertJsonPath('fine_rules.0.id', $fineRuleCurrent->id);

        // Test listing fine rules with explicit academic_year_id parameter
        $response = $this->actingAs($this->schoolAdmin)
            ->getJson("/api/fee-fine-rules?academic_year_id={$anotherYear->id}");
        $response->assertStatus(200)
            ->assertJsonCount(1, 'fine_rules')
            ->assertJsonPath('fine_rules.0.id', $fineRuleAnother->id);
    }

    public function test_fee_assignment_blocked_when_student_has_payments()
    {
        $structure1 = FeeStructure::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->classModel->id,
            'name' => 'Structure A',
            'status' => 'active'
        ]);

        $structure2 = FeeStructure::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->classModel->id,
            'name' => 'Structure B',
            'status' => 'active'
        ]);

        $student = Student::create([
            'school_id' => $this->school->id,
            'admission_no' => 'ADM-PAY01',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'Male',
            'date_of_birth' => '2010-05-15',
            'admission_date' => '2025-06-01',
            'status' => 'active'
        ]);

        StudentAcademicRecord::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->classModel->id,
            'section_id' => $this->section->id,
            'roll_no' => '42'
        ]);

        // Assign structure 1 first
        StudentFeeAssignment::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'student_id' => $student->id,
            'fee_structure_id' => $structure1->id,
            'assigned_date' => '2025-06-01'
        ]);

        // Record a payment under structure 1
        \App\Models\FeeCollection::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'student_id' => $student->id,
            'amount_due' => 1000.00,
            'amount_paid' => 500.00,
            'payment_date' => '2025-06-10',
            'payment_method' => 'cash',
            'collected_by' => $this->schoolAdmin->id
        ]);

        // Attempting to change to structure 2 individually should be blocked
        $response = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/fee-assignments', [
                'academic_year_id' => $this->academicYear->id,
                'student_id' => $student->id,
                'fee_structure_id' => $structure2->id,
                'assigned_date' => '2025-06-01'
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cannot change the assigned fee structure because the student has already paid one or more installments under the currently assigned structure.'
            ]);

        // Attempting to bulk assign structure 2 with overwrite should skip this student
        $responseBulk = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/fee-assignments/bulk', [
                'academic_year_id' => $this->academicYear->id,
                'class_id' => $this->classModel->id,
                'fee_structure_id' => $structure2->id,
                'assigned_date' => '2025-06-01',
                'overwrite_existing' => true
            ]);

        $responseBulk->assertStatus(200);

        // Verify the student's assignment was NOT changed
        $assignment = StudentFeeAssignment::where('student_id', $student->id)
            ->where('academic_year_id', $this->academicYear->id)
            ->first();

        $this->assertEquals($structure1->id, $assignment->fee_structure_id);
    }
}

