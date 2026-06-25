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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentPhotoUploadTest extends TestCase
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

        $this->schoolAdmin = User::whereHas('roles', function ($query) {
            $query->where('name', 'School Admin');
        })->first();
        $this->school = School::find($this->schoolAdmin->school_id);

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
            'name' => 'Grade 10',
            'status' => 'active'
        ]);

        $this->section = Section::create([
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'name' => 'A',
            'status' => 'active'
        ]);
    }

    public function test_photo_upload_unassigned()
    {
        $file = UploadedFile::fake()->create('student_pic.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($this->schoolAdmin)->postJson('/api/students/upload-photo', [
            'photo' => $file,
        ]);

        $response->assertStatus(200);
        $this->assertNotNull($response->json('url'));
        $this->assertStringContainsString('unassigned', $response->json('url'));
        
        // Assert file exists in public/uploads/students/{school_id}/unassigned/unassigned
        $path = $response->json('path');
        $this->assertFileExists(public_path($path));
        
        // Clean up
        if (file_exists(public_path($path))) {
            unlink(public_path($path));
        }
    }

    public function test_photo_upload_assigned_and_relocation_on_create()
    {
        $file = UploadedFile::fake()->create('student_assigned.jpg', 100, 'image/jpeg');

        // 1. Upload to unassigned first
        $uploadResponse = $this->actingAs($this->schoolAdmin)->postJson('/api/students/upload-photo', [
            'photo' => $file,
        ]);
        $uploadResponse->assertStatus(200);
        $tempUrl = $uploadResponse->json('url');
        $tempPath = $uploadResponse->json('path');

        $this->assertFileExists(public_path($tempPath));

        // 2. Submit student form with tempUrl, class, and section
        $studentData = [
            'admission_no' => 'ADM-2026-999',
            'first_name' => 'Bobby',
            'last_name' => 'Tables',
            'gender' => 'Male',
            'date_of_birth' => '2015-05-15',
            'admission_date' => '2025-06-01',
            'photo' => $tempUrl,
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'status' => 'active',
        ];

        $createResponse = $this->actingAs($this->schoolAdmin)->postJson('/api/students', $studentData);
        $createResponse->assertStatus(201);

        // Assert old temp file does not exist anymore
        $this->assertFileDoesNotExist(public_path($tempPath));

        // Assert new file exists in public/uploads/students/{school_id}/{class_id}/{section_id}
        $student = Student::where('admission_no', 'ADM-2026-999')->first();
        $this->assertNotNull($student);
        $this->assertNotNull($student->photo);
        $this->assertStringContainsString("uploads/students/{$this->school->id}/{$this->class->id}/{$this->section->id}/", $student->photo);

        $newRelativePath = parse_url($student->photo, PHP_URL_PATH);
        $newRelativePath = ltrim($newRelativePath, '/');
        $this->assertFileExists(public_path($newRelativePath));

        // Clean up
        if (file_exists(public_path($newRelativePath))) {
            unlink(public_path($newRelativePath));
        }
    }
}
