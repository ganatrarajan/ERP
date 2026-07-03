<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use App\Models\TeacherAssignment;
use App\Models\Student;
use App\Models\StudentAcademicRecord;
use App\Models\Homework;
use App\Models\Notice;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\ExamMark;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Carbon\Carbon;

class TeacherMobileApiTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected AcademicYear $academicYear;
    protected ClassModel $class;
    protected Section $section;
    protected Subject $subject;
    protected User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ModuleSeeder::class);

        $this->school = School::create([
            'name' => 'Eduvora School',
            'email' => 'edu@school.com',
            'status' => 'active',
            'default_report_card_template' => 'basic',
        ]);
        $this->school->refresh(); // Retrieve auto-generated code

        $this->academicYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'title' => '2025-2026',
            'start_date' => '2025-06-01',
            'end_date' => '2026-05-31',
            'is_current' => true,
            'status' => 'active',
            'is_delete' => 0,
        ]);

        $this->class = ClassModel::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Grade 10',
            'status' => 'active',
            'is_delete' => 0,
        ]);

        $this->section = Section::create([
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'name' => 'A',
            'status' => 'active',
            'is_delete' => 0,
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'English Lit',
            'code' => 'ENG101',
            'status' => 'active',
            'evaluation_type' => 'marks',
        ]);

        $this->teacher = User::create([
            'school_id' => $this->school->id,
            'name' => 'Jane Teacher',
            'email' => 'jane@school.com',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);

        $this->teacher->assignRole('Teacher');

        // Assign teacher to class & section & subject
        TeacherAssignment::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'teacher_id' => $this->teacher->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'is_class_teacher' => true,
        ]);
    }

    public function test_teacher_login()
    {
        $response = $this->postJson('/api/mobile/teacher/login', [
            'school_code' => $this->school->school_code,
            'email' => 'jane@school.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Logged in successfully.'
        ]);
        $this->assertNotNull($response->json('token'));
    }

    public function test_teacher_login_invalid_role()
    {
        $admin = User::create([
            'school_id' => $this->school->id,
            'name' => 'School Admin',
            'email' => 'admin@school.com',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);

        $admin->assignRole('School Admin');

        $response = $this->postJson('/api/mobile/teacher/login', [
            'school_code' => $this->school->school_code,
            'email' => 'admin@school.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Unauthorized. This portal is for teachers only.'
        ]);
    }

    public function test_teacher_profile_read_and_update()
    {
        $token = $this->teacher->createToken('test-token', [
            "school:{$this->school->id}",
            "academic_year:{$this->academicYear->id}"
        ])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/mobile/teacher/profile');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'teacher' => [
                'name' => 'Jane Teacher',
                'email' => 'jane@school.com'
            ]
        ]);

        // Update profile
        $updateResponse = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/mobile/teacher/profile/update', [
                'address' => '123 New Teacher Street',
                'mobile' => '9876543210'
            ]);

        $updateResponse->assertStatus(200);
        $updateResponse->assertJsonPath('teacher.address', '123 New Teacher Street');
    }

    public function test_teacher_dashboard()
    {
        $token = $this->teacher->createToken('test-token', [
            "school:{$this->school->id}",
            "academic_year:{$this->academicYear->id}"
        ])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/mobile/teacher/dashboard');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'assigned_classes_count',
            'today_attendance_marked_sections',
            'pending_homework_count',
            'recent_notices'
        ]);
    }

    public function test_teacher_assignments()
    {
        $token = $this->teacher->createToken('test-token', [
            "school:{$this->school->id}",
            "academic_year:{$this->academicYear->id}"
        ])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/mobile/teacher/assignments');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'assignments');
    }

    public function test_teacher_attendance_marking()
    {
        $token = $this->teacher->createToken('test-token', [
            "school:{$this->school->id}",
            "academic_year:{$this->academicYear->id}"
        ])->plainTextToken;

        // Create student
        $student = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'admission_no' => 'ADM-001',
            'gender' => 'Male',
            'date_of_birth' => '2015-05-15',
            'admission_date' => '2025-06-01',
            'status' => 'active',
        ]);

        StudentAcademicRecord::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'student_id' => $student->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'status' => 'active',
            'roll_no' => '01'
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/mobile/teacher/attendance/save', [
                'class_id' => $this->class->id,
                'section_id' => $this->section->id,
                'attendance_date' => '2026-07-02',
                'students' => [
                    [
                        'student_id' => $student->id,
                        'status' => 'Present',
                        'remarks' => 'On Time'
                    ]
                ]
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'status' => 'Present',
            'attendance_date' => '2026-07-02'
        ]);
    }

    public function test_teacher_homework_lifecycle()
    {
        $token = $this->teacher->createToken('test-token', [
            "school:{$this->school->id}",
            "academic_year:{$this->academicYear->id}"
        ])->plainTextToken;

        // Create homework
        $createResponse = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/mobile/teacher/homeworks', [
                'class_id' => $this->class->id,
                'section_id' => $this->section->id,
                'subject_id' => $this->subject->id,
                'title' => 'Algebra Exercise',
                'description' => 'Solve problems 1-10.',
                'submission_date' => Carbon::tomorrow()->format('Y-m-d'),
                'max_marks' => 100
            ]);

        $createResponse->assertStatus(201);
        $homeworkId = $createResponse->json('homework.id');

        $this->assertDatabaseHas('homeworks', [
            'id' => $homeworkId,
            'title' => 'Algebra Exercise'
        ]);

        // Update homework
        $updateResponse = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/mobile/teacher/homeworks/{$homeworkId}", [
                'title' => 'Algebra Exercise V2'
            ]);

        $updateResponse->assertStatus(200);
        $this->assertDatabaseHas('homeworks', [
            'id' => $homeworkId,
            'title' => 'Algebra Exercise V2'
        ]);

        // Delete homework
        $deleteResponse = $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson("/api/mobile/teacher/homeworks/{$homeworkId}");

        $deleteResponse->assertStatus(200);
        $this->assertDatabaseHas('homeworks', [
            'id' => $homeworkId,
            'is_delete' => 1
        ]);
    }
}
