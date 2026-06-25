<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentAcademicRecord;
use App\Models\Homework;
use App\Models\Notice;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Carbon\Carbon;

class StudentMobileApiTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected School $otherSchool;
    protected AcademicYear $academicYear;
    protected AcademicYear $otherAcademicYear;
    protected ClassModel $class;
    protected Section $section;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ModuleSeeder::class);

        // Fetch / Create School
        $this->school = School::create([
            'name' => 'Test School',
            'email' => 'info@testschool.com',
            'status' => 'active',
            'default_report_card_template' => 'basic',
        ]);
        // The boot method should generate school_code automatically
        $this->school->refresh();

        $this->otherSchool = School::create([
            'name' => 'Other School',
            'email' => 'other@testschool.com',
            'status' => 'active',
        ]);

        $this->academicYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'title' => '2025-2026',
            'start_date' => '2025-06-01',
            'end_date' => '2026-05-31',
            'is_current' => true,
            'status' => 'active',
            'is_delete' => 0,
        ]);

        $this->otherAcademicYear = AcademicYear::create([
            'school_id' => $this->otherSchool->id,
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

        $this->staff = User::create([
            'school_id' => $this->school->id,
            'name' => 'Test Teacher',
            'email' => 'teacher@school.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
    }

    public function test_school_verification()
    {
        $response = $this->postJson('/api/mobile/school/verify', [
            'school_code' => $this->school->school_code,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'school_id' => $this->school->id,
            'school_name' => $this->school->name,
        ]);
    }

    public function test_student_login_and_password_change_flow()
    {
        // 1. Create a student
        $student = Student::create([
            'school_id' => $this->school->id,
            'admission_no' => 'STU-001',
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'gender' => 'Female',
            'date_of_birth' => '2010-04-12',
            'admission_date' => '2025-06-01',
            'status' => 'active',
        ]);

        StudentAcademicRecord::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'roll_no' => '1',
            'status' => 'active',
        ]);

        // Default password logic check
        $this->assertTrue(Hash::check('STU-001', $student->password));
        $this->assertEquals(0, $student->password_changed);

        // 2. Perform Login -> should return force password change
        $loginResponse = $this->postJson('/api/mobile/login', [
            'school_code' => $this->school->school_code,
            'academic_year_id' => $this->academicYear->id,
            'admission_no' => 'STU-001',
            'password' => 'STU-001'
        ]);

        $loginResponse->assertStatus(200);
        $loginResponse->assertJson([
            'force_password_change' => true,
            'student_id' => $student->id
        ]);

        // 3. Try login with wrong password
        $wrongLogin = $this->postJson('/api/mobile/login', [
            'school_code' => $this->school->school_code,
            'academic_year_id' => $this->academicYear->id,
            'admission_no' => 'STU-001',
            'password' => 'wrongpass'
        ]);
        $wrongLogin->assertStatus(401);

        // 4. Change Password
        $changeResponse = $this->postJson('/api/mobile/change-password', [
            'student_id' => $student->id,
            'old_password' => 'STU-001',
            'new_password' => 'newpassword123',
            'confirm_password' => 'newpassword123'
        ]);

        $changeResponse->assertStatus(200);
        $changeResponse->assertJson(['success' => true]);

        // Verify changes in database
        $student->refresh();
        $this->assertEquals(1, $student->password_changed);
        $this->assertTrue(Hash::check('newpassword123', $student->password));

        // 5. Login again with new password -> should return normal login response
        $login2 = $this->postJson('/api/mobile/login', [
            'school_code' => $this->school->school_code,
            'academic_year_id' => $this->academicYear->id,
            'admission_no' => 'STU-001',
            'password' => 'newpassword123'
        ]);

        $login2->assertStatus(200);
        $login2->assertJsonStructure([
            'success',
            'token',
            'student'
        ]);
        $token = $login2->json('token');
        $this->assertNotEmpty($token);
    }

    public function test_data_isolation_and_authenticated_endpoints()
    {
        // 1. Create student and force password change bypass
        $student = Student::create([
            'school_id' => $this->school->id,
            'admission_no' => 'STU-002',
            'first_name' => 'Bob',
            'last_name' => 'Jones',
            'gender' => 'Male',
            'date_of_birth' => '2010-08-15',
            'admission_date' => '2025-06-01',
            'status' => 'active',
            'password' => bcrypt('secretpassword'),
            'password_changed' => 1
        ]);

        $academicRecord = StudentAcademicRecord::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'roll_no' => '12',
            'status' => 'active',
        ]);

        // Generate Token with context abilities
        $token = $student->createToken('test-token', [
            "school:{$this->school->id}",
            "academic_year:{$this->academicYear->id}"
        ])->plainTextToken;
        $headers = ['Authorization' => "Bearer {$token}"];

        // 2. Fetch Profile
        $profileResponse = $this->getJson('/api/mobile/profile', $headers);
        $profileResponse->assertStatus(200);
        $profileResponse->assertJsonPath('student.admission_no', 'STU-002');

        // 3. Create Homework and verify isolation
        $subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Mathematics',
            'code' => 'MTH10',
            'status' => 'active'
        ]);

        // Homework for my school
        Homework::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'subject_id' => $subject->id,
            'title' => 'Algebra Worksheet',
            'description' => 'Complete questions 1 to 10',
            'submission_date' => Carbon::tomorrow()->format('Y-m-d'),
            'created_by' => $this->staff->id,
            'status' => 'active'
        ]);

        $otherClass = ClassModel::create([
            'school_id' => $this->otherSchool->id,
            'academic_year_id' => $this->otherAcademicYear->id,
            'name' => 'Grade 10',
            'status' => 'active',
            'is_delete' => 0,
        ]);

        $otherSection = Section::create([
            'school_id' => $this->otherSchool->id,
            'class_id' => $otherClass->id,
            'name' => 'A',
            'status' => 'active',
            'is_delete' => 0,
        ]);

        // Homework for other school
        Homework::create([
            'school_id' => $this->otherSchool->id,
            'academic_year_id' => $this->otherAcademicYear->id,
            'class_id' => $otherClass->id,
            'section_id' => $otherSection->id,
            'subject_id' => $subject->id,
            'title' => 'Other School Homework',
            'description' => 'Other',
            'submission_date' => Carbon::tomorrow()->format('Y-m-d'),
            'created_by' => $this->staff->id,
            'status' => 'active'
        ]);

        $homeworkResponse = $this->getJson('/api/mobile/homework', $headers);
        $homeworkResponse->assertStatus(200);
        $homeworkResponse->assertJsonCount(1, 'homeworks'); // Only sees my school's homework
        $homeworkResponse->assertJsonPath('homeworks.0.title', 'Algebra Worksheet');

        // 4. Notices
        Notice::create([
            'school_id' => $this->school->id,
            'title' => 'Sports Day',
            'description' => 'Annual sports day notice',
            'notice_date' => Carbon::today()->format('Y-m-d'),
            'target_type' => 'Entire School',
            'created_by' => $this->staff->id,
            'status' => 'active'
        ]);

        $noticeResponse = $this->getJson('/api/mobile/notices', $headers);
        $noticeResponse->assertStatus(200);
        $noticeResponse->assertJsonCount(1, 'notices');

        // 5. Dashboard
        $dashboardResponse = $this->getJson('/api/mobile/dashboard', $headers);
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertJsonFragment([
            'pending_homework_count' => 1,
            'latest_notice_count' => 1,
            'attendance_percentage' => 100.00,
        ]);
    }
}
