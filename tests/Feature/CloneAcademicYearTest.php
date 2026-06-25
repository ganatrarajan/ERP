<?php
 
namespace Tests\Feature;
 
use App\Models\School;
use App\Models\User;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\FeeType;
use App\Models\FeeStructure;
use App\Models\FeeStructureItem;
use App\Models\FeeInstallment;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
 
class CloneAcademicYearTest extends TestCase
{
    use RefreshDatabase;
 
    protected User $schoolAdmin;
    protected School $school;
 
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
    }
 
    public function test_academic_year_cloning_functionality()
    {
        // 1. Create source academic year
        $sourceYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'title' => '2025-2026',
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'is_current' => true,
            'status' => 'active'
        ]);
 
        // 2. Create source Classes and Sections
        $classA = ClassModel::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $sourceYear->id,
            'name' => 'Class X',
            'description' => 'Tenth grade',
            'status' => 'active'
        ]);
 
        $sectionA1 = Section::create([
            'school_id' => $this->school->id,
            'class_id' => $classA->id,
            'name' => 'Section A',
            'status' => 'active'
        ]);
 
        $sectionA2 = Section::create([
            'school_id' => $this->school->id,
            'class_id' => $classA->id,
            'name' => 'Section B',
            'status' => 'active'
        ]);
 
        // 3. Create source Subjects
        $subject1 = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $sourceYear->id,
            'class_id' => $classA->id,
            'section_id' => $sectionA1->id,
            'name' => 'Mathematics',
            'code' => 'MATH101',
            'description' => 'Algebra and Geometry',
            'status' => 'active',
            'is_optional' => false,
            'evaluation_type' => 'marks',
            'subject_category' => 'scholastic',
            'maximum_marks' => 100,
            'passing_marks' => 35
        ]);
 
        // 4. Create Fee setup
        $feeType = FeeType::create([
            'school_id' => $this->school->id,
            'name' => 'Tuition Fee',
            'code' => 'TUIT',
            'is_optional' => false,
            'status' => 'active'
        ]);
 
        $feeStructure = FeeStructure::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $sourceYear->id,
            'class_id' => $classA->id,
            'name' => 'Class X Standard Fees',
            'status' => 'active'
        ]);
 
        FeeStructureItem::create([
            'fee_structure_id' => $feeStructure->id,
            'fee_type_id' => $feeType->id,
            'amount' => 1200.50
        ]);
 
        // 45 days after start_date of sourceYear
        $sourceDueDate = Carbon::parse('2025-05-16'); 
        $installment = FeeInstallment::create([
            'school_id' => $this->school->id,
            'fee_structure_id' => $feeStructure->id,
            'installment_name' => 'Q1 Installment',
            'due_date' => $sourceDueDate->toDateString(),
            'amount' => 1200.50,
            'sort_order' => 1,
            'status' => 'active'
        ]);
 
        // 5. Send post request to create new academic year with cloning enabled
        $targetStartDate = '2026-04-01';
        $targetEndDate = '2027-03-31';
 
        $response = $this->actingAs($this->schoolAdmin)->postJson('/api/academic-years', [
            'title' => '2026-2027',
            'start_date' => $targetStartDate,
            'end_date' => $targetEndDate,
            'status' => 'active',
            'is_current' => false,
            'clone_source_id' => $sourceYear->id,
            'clone_elements' => ['classes_sections', 'subjects', 'fee_structures']
        ]);
 
        $response->assertStatus(201);
        $newYearId = $response->json('academic_year.id');
        $this->assertNotEmpty($newYearId);
 
        // 6. Verify cloned class and sections exist
        $this->assertDatabaseHas('classes', [
            'school_id' => $this->school->id,
            'academic_year_id' => $newYearId,
            'name' => 'Class X',
            'description' => 'Tenth grade',
            'status' => 'active'
        ]);
        $newClass = ClassModel::where('academic_year_id', $newYearId)->where('name', 'Class X')->first();
        $this->assertNotNull($newClass);
 
        $this->assertDatabaseHas('sections', [
            'school_id' => $this->school->id,
            'class_id' => $newClass->id,
            'name' => 'Section A',
            'status' => 'active'
        ]);
        $this->assertDatabaseHas('sections', [
            'school_id' => $this->school->id,
            'class_id' => $newClass->id,
            'name' => 'Section B',
            'status' => 'active'
        ]);
        $newSection = Section::where('class_id', $newClass->id)->where('name', 'Section A')->first();
        $this->assertNotNull($newSection);
 
        // 7. Verify cloned subject exists and maps to the new class and section correctly
        $this->assertDatabaseHas('subjects', [
            'school_id' => $this->school->id,
            'academic_year_id' => $newYearId,
            'class_id' => $newClass->id,
            'section_id' => $newSection->id,
            'name' => 'Mathematics',
            'code' => 'MATH101',
            'evaluation_type' => 'marks',
            'subject_category' => 'scholastic'
        ]);
 
        // 8. Verify cloned fee structures exist
        $this->assertDatabaseHas('fee_structures', [
            'school_id' => $this->school->id,
            'academic_year_id' => $newYearId,
            'class_id' => $newClass->id,
            'name' => 'Class X Standard Fees',
            'status' => 'active'
        ]);
        $newFeeStructure = FeeStructure::where('academic_year_id', $newYearId)->first();
        $this->assertNotNull($newFeeStructure);
 
        // Verify items cloned
        $this->assertDatabaseHas('fee_structure_items', [
            'fee_structure_id' => $newFeeStructure->id,
            'fee_type_id' => $feeType->id,
            'amount' => 1200.50
        ]);
 
        // Verify shifted due date (difference between 2025-04-01 and 2026-04-01 is 365 days)
        // May 16, 2025 shifted by 365 days is May 16, 2026
        $expectedNewDueDate = Carbon::parse($targetStartDate)->addDays(
            Carbon::parse($sourceYear->start_date)->diffInDays($sourceDueDate, false)
        )->toDateString(); // '2026-05-16'
 
        $newInstallment = FeeInstallment::where('fee_structure_id', $newFeeStructure->id)
            ->where('installment_name', 'Q1 Installment')
            ->first();
        $this->assertNotNull($newInstallment);
        $this->assertEquals($expectedNewDueDate, $newInstallment->due_date->toDateString());
        $this->assertEquals(1200.50, $newInstallment->amount);
        $this->assertEquals(1, $newInstallment->sort_order);
        $this->assertEquals('active', $newInstallment->status);
    }
}
