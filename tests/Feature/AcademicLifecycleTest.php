<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Student;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicLifecycleTest extends TestCase
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

    public function test_classes_filtering_and_soft_delete()
    {
        // 1. Create one active class, one inactive class
        $activeYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'title' => '2025-2026',
            'start_date' => '2025-06-01',
            'end_date' => '2026-05-31',
            'is_current' => true,
            'status' => 'active'
        ]);

        $activeClass = ClassModel::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $activeYear->id,
            'name' => 'Active Class',
            'status' => 'active',
            'is_delete' => 0
        ]);

        $inactiveClass = ClassModel::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $activeYear->id,
            'name' => 'Inactive Class',
            'status' => 'inactive',
            'is_delete' => 0
        ]);

        // 2. Fetch classes listing without filter (should return both)
        $response = $this->actingAs($this->schoolAdmin)->getJson('/api/classes');
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('classes'));

        // 3. Fetch classes listing with status=active (should return only active)
        $responseActive = $this->actingAs($this->schoolAdmin)->getJson('/api/classes?status=active');
        $responseActive->assertStatus(200);
        $this->assertCount(1, $responseActive->json('classes'));
        $this->assertEquals('Active Class', $responseActive->json('classes.0.name'));

        // 4. Soft delete the active class
        $deleteResponse = $this->actingAs($this->schoolAdmin)->deleteJson("/api/classes/{$activeClass->id}");
        $deleteResponse->assertStatus(200);

        // Assert is_delete set to 1 in DB
        $this->assertDatabaseHas('classes', [
            'id' => $activeClass->id,
            'is_delete' => 1
        ]);

        // 5. Fetch classes listing again (should only return inactive class, active class excluded as it is deleted)
        $responseAfterDelete = $this->actingAs($this->schoolAdmin)->getJson('/api/classes');
        $responseAfterDelete->assertStatus(200);
        $this->assertCount(1, $responseAfterDelete->json('classes'));
        $this->assertEquals('Inactive Class', $responseAfterDelete->json('classes.0.name'));
    }

    public function test_sections_filtering_and_soft_delete()
    {
        $activeYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'title' => '2025-2026',
            'start_date' => '2025-06-01',
            'end_date' => '2026-05-31',
            'is_current' => true,
            'status' => 'active'
        ]);

        $class = ClassModel::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $activeYear->id,
            'name' => 'General Class',
            'status' => 'active'
        ]);

        $activeSection = Section::create([
            'school_id' => $this->school->id,
            'class_id' => $class->id,
            'name' => 'Active Sec',
            'status' => 'active',
            'is_delete' => 0
        ]);

        $inactiveSection = Section::create([
            'school_id' => $this->school->id,
            'class_id' => $class->id,
            'name' => 'Inactive Sec',
            'status' => 'inactive',
            'is_delete' => 0
        ]);

        // Fetch without filter (should return both)
        $response = $this->actingAs($this->schoolAdmin)->getJson('/api/sections');
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('sections'));

        // Fetch with status=active (should return only active)
        $responseActive = $this->actingAs($this->schoolAdmin)->getJson('/api/sections?status=active');
        $responseActive->assertStatus(200);
        $this->assertCount(1, $responseActive->json('sections'));
        $this->assertEquals('Active Sec', $responseActive->json('sections.0.name'));

        // Soft delete active section
        $this->actingAs($this->schoolAdmin)->deleteJson("/api/sections/{$activeSection->id}")->assertStatus(200);
        $this->assertDatabaseHas('sections', [
            'id' => $activeSection->id,
            'is_delete' => 1
        ]);

        // Listing should only return inactive one now
        $responseAfterDelete = $this->actingAs($this->schoolAdmin)->getJson('/api/sections');
        $this->assertCount(1, $responseAfterDelete->json('sections'));
        $this->assertEquals('Inactive Sec', $responseAfterDelete->json('sections.0.name'));
    }

    public function test_academic_years_filtering_and_soft_delete()
    {
        $activeYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'title' => '2025-2026',
            'start_date' => '2025-06-01',
            'end_date' => '2026-05-31',
            'is_current' => true,
            'status' => 'active',
            'is_delete' => 0
        ]);

        $inactiveYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'title' => '2024-2025',
            'start_date' => '2024-06-01',
            'end_date' => '2025-05-31',
            'is_current' => false,
            'status' => 'inactive',
            'is_delete' => 0
        ]);

        // Fetch without filter (should return both)
        $response = $this->actingAs($this->schoolAdmin)->getJson('/api/academic-years');
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('academic_years'));

        // Fetch with status=active (should return only active)
        $responseActive = $this->actingAs($this->schoolAdmin)->getJson('/api/academic-years?status=active');
        $responseActive->assertStatus(200);
        $this->assertCount(1, $responseActive->json('academic_years'));

        // Soft delete active year
        $this->actingAs($this->schoolAdmin)->deleteJson("/api/academic-years/{$activeYear->id}")->assertStatus(200);
        $this->assertDatabaseHas('academic_years', [
            'id' => $activeYear->id,
            'is_delete' => 1
        ]);

        // Listing should only return inactive one
        $responseAfterDelete = $this->actingAs($this->schoolAdmin)->getJson('/api/academic-years');
        $this->assertCount(1, $responseAfterDelete->json('academic_years'));
    }

    public function test_inactive_parent_entities_hide_child_data()
    {
        $academicYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'title' => '2025-2026',
            'start_date' => '2025-06-01',
            'end_date' => '2026-05-31',
            'is_current' => true,
            'status' => 'active'
        ]);

        // Create inactive class
        $inactiveClass = ClassModel::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'Inactive Class',
            'status' => 'inactive'
        ]);

        // Create section belonging to inactive class
        $sectionInInactiveClass = Section::create([
            'school_id' => $this->school->id,
            'class_id' => $inactiveClass->id,
            'name' => 'Sec In Inactive Class',
            'status' => 'active'
        ]);

        // Create active class
        $activeClass = ClassModel::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'Active Class',
            'status' => 'active'
        ]);

        // Create inactive section in active class
        $inactiveSection = Section::create([
            'school_id' => $this->school->id,
            'class_id' => $activeClass->id,
            'name' => 'Inactive Sec',
            'status' => 'inactive'
        ]);

        // Create active section in active class
        $activeSection = Section::create([
            'school_id' => $this->school->id,
            'class_id' => $activeClass->id,
            'name' => 'Active Sec',
            'status' => 'active'
        ]);

        // Create student in inactive class
        $studentInInactiveClass = Student::create([
            'school_id' => $this->school->id,
            'admission_no' => 'ADM-INACTIVE-CLASS',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'Male',
            'date_of_birth' => '2015-05-15',
            'admission_date' => '2025-06-01',
            'status' => 'active'
        ]);
        \App\Models\StudentAcademicRecord::create([
            'school_id' => $this->school->id,
            'student_id' => $studentInInactiveClass->id,
            'academic_year_id' => $academicYear->id,
            'class_id' => $inactiveClass->id,
            'section_id' => $sectionInInactiveClass->id,
            'roll_no' => '01'
        ]);

        // Create student in inactive section of active class
        $studentInInactiveSec = Student::create([
            'school_id' => $this->school->id,
            'admission_no' => 'ADM-INACTIVE-SEC',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'gender' => 'Female',
            'date_of_birth' => '2016-08-20',
            'admission_date' => '2025-06-01',
            'status' => 'active'
        ]);
        \App\Models\StudentAcademicRecord::create([
            'school_id' => $this->school->id,
            'student_id' => $studentInInactiveSec->id,
            'academic_year_id' => $academicYear->id,
            'class_id' => $activeClass->id,
            'section_id' => $inactiveSection->id,
            'roll_no' => '02'
        ]);

        // Create student in active section of active class
        $studentActive = Student::create([
            'school_id' => $this->school->id,
            'admission_no' => 'ADM-ACTIVE',
            'first_name' => 'Bob',
            'last_name' => 'Johnson',
            'gender' => 'Male',
            'date_of_birth' => '2017-01-10',
            'admission_date' => '2025-06-01',
            'status' => 'active'
        ]);
        \App\Models\StudentAcademicRecord::create([
            'school_id' => $this->school->id,
            'student_id' => $studentActive->id,
            'academic_year_id' => $academicYear->id,
            'class_id' => $activeClass->id,
            'section_id' => $activeSection->id,
            'roll_no' => '03'
        ]);

        // 1. Verify section list hides section belonging to inactive class
        $sectionResponse = $this->actingAs($this->schoolAdmin)->getJson('/api/sections');
        $sectionResponse->assertStatus(200);
        $sectionIds = collect($sectionResponse->json('sections'))->pluck('id')->all();
        $this->assertNotContains($sectionInInactiveClass->id, $sectionIds);
        $this->assertContains($activeSection->id, $sectionIds);
        $this->assertContains($inactiveSection->id, $sectionIds);

        // 2. Verify student list hides student in inactive class and student in inactive section
        $studentResponse = $this->actingAs($this->schoolAdmin)->getJson('/api/students');
        $studentResponse->assertStatus(200);
        $studentIds = collect($studentResponse->json('data'))->pluck('id')->all();
        $this->assertNotContains($studentInInactiveClass->id, $studentIds);
        $this->assertNotContains($studentInInactiveSec->id, $studentIds);
        $this->assertContains($studentActive->id, $studentIds);
    }
}
