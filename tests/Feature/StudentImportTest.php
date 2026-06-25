<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentAcademicRecord;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $schoolAdmin;
    protected School $school;
    protected AcademicYear $academicYear;
    protected ClassModel $class;
    protected Section $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\ModuleSeeder::class);

        // Fetch dummy school and admin created by seeder
        $this->schoolAdmin = User::whereHas('roles', function ($query) {
            $query->where('name', 'School Admin');
        })->first();
        $this->school = School::find($this->schoolAdmin->school_id);

        // Setup academic year, class, section
        $this->academicYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'title' => '2025-2026',
            'start_date' => '2025-06-01',
            'end_date' => '2026-05-31',
            'is_current' => true,
            'status' => 'active'
        ]);

        $this->class = ClassModel::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Class 1',
            'status' => 'active'
        ]);

        $this->section = Section::create([
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'name' => 'A',
            'status' => 'active'
        ]);
    }

    public function test_can_download_demo_csv()
    {
        $response = $this->actingAs($this->schoolAdmin)
            ->get('/api/students/import/demo');

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename="student_import_demo.csv"');
        $this->assertStringContainsString('admission_no,first_name,last_name,gender', $response->streamedContent());
    }

    public function test_can_import_valid_csv()
    {
        $this->withoutExceptionHandling();
        $csvContent = "admission_no,first_name,last_name,gender,date_of_birth,admission_date,roll_no,academic_year,class,section,mobile,email,father_name,father_mobile,father_email,mother_name,mother_mobile,mother_email,guardian_name,guardian_mobile,address\n"
            . "ADM-IMP-001,John,Doe,Male,2015-05-15,2025-06-01,01,2025-2026,Class 1,A,1234567,john@doe.com,Richard,1234568,richard@doe.com,Mary,1234569,mary@doe.com,,,123 School Lane\n"
            . "ADM-IMP-002,Jane,Smith,Female,2016-08-20,2025-06-01,02,2025-2026,Class 1,A,7654321,jane@smith.com,Robert,7654322,robert@smith.com,Sarah,7654323,sarah@smith.com,,,456 Learning Blvd\n";

        $file = UploadedFile::fake()->createWithContent('import.csv', $csvContent);

        $response = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/students/import', [
                'file' => $file
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success_count', 2);

        $this->assertDatabaseHas('students', [
            'school_id' => $this->school->id,
            'admission_no' => 'ADM-IMP-001',
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);

        $this->assertDatabaseHas('student_academic_records', [
            'school_id' => $this->school->id,
            'roll_no' => '01',
            'class_id' => $this->class->id,
            'section_id' => $this->section->id
        ]);
    }


    public function test_import_fails_on_duplicate_admission_no()
    {
        // Add a student with admission no ADM-IMP-001 beforehand
        $student = Student::create([
            'school_id' => $this->school->id,
            'admission_no' => 'ADM-IMP-001',
            'first_name' => 'Existing',
            'last_name' => 'Student',
            'gender' => 'Male',
            'date_of_birth' => '2015-05-15',
            'admission_date' => '2025-06-01',
        ]);

        $csvContent = "admission_no,first_name,last_name,gender,date_of_birth,admission_date,roll_no,academic_year,class,section\n"
            . "ADM-IMP-001,John,Doe,Male,2015-05-15,2025-06-01,01,2025-2026,Class 1,A\n";

        $file = UploadedFile::fake()->createWithContent('import.csv', $csvContent);

        $response = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/students/import', [
                'file' => $file
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success_count', 0);

        $this->assertStringContainsString('already exists in this school', json_encode($response->json('errors')));
    }

    public function test_import_fails_on_duplicate_roll_no_in_section()
    {
        // Add a student and academic record with roll no 01 in Class 1 A
        $student = Student::create([
            'school_id' => $this->school->id,
            'admission_no' => 'ADM-EXISTING',
            'first_name' => 'Existing',
            'last_name' => 'Student',
            'gender' => 'Male',
            'date_of_birth' => '2015-05-15',
            'admission_date' => '2025-06-01',
        ]);

        StudentAcademicRecord::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'roll_no' => '01',
            'status' => 'active'
        ]);

        // Attempt importing another student with same roll number
        $csvContent = "admission_no,first_name,last_name,gender,date_of_birth,admission_date,roll_no,academic_year,class,section\n"
            . "ADM-IMP-001,John,Doe,Male,2015-05-15,2025-06-01,01,2025-2026,Class 1,A\n";

        $file = UploadedFile::fake()->createWithContent('import.csv', $csvContent);

        $response = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/students/import', [
                'file' => $file
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success_count', 0);

        $this->assertStringContainsString('already exists in this class/section', json_encode($response->json('errors'), JSON_UNESCAPED_SLASHES));
    }

    public function test_import_fails_on_missing_entities()
    {
        $csvContent = "admission_no,first_name,last_name,gender,date_of_birth,admission_date,roll_no,academic_year,class,section\n"
            . "ADM-IMP-001,John,Doe,Male,2015-05-15,2025-06-01,01,2025-2026,Class 2,A\n"; // Class 2 does not exist

        $file = UploadedFile::fake()->createWithContent('import.csv', $csvContent);

        $response = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/students/import', [
                'file' => $file
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success_count', 0);

        $this->assertStringContainsString("Class 'Class 2' does not exist in this school.", json_encode($response->json('errors')));
    }
}
