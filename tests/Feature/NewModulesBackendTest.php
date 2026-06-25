<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentAcademicRecord;
use App\Models\Subject;
use App\Models\Attendance;
use App\Models\Homework;
use App\Models\Notice;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class NewModulesBackendTest extends TestCase
{
    use RefreshDatabase;

    protected User $schoolAdmin;
    protected School $school;
    protected AcademicYear $academicYear;
    protected ClassModel $class;
    protected Section $section;
    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\ModuleSeeder::class);

        echo "\n--- DB CONNECTION: " . config('database.default') . " ---\n";
        try {
            $tables = \Illuminate\Support\Facades\DB::select("SELECT name FROM sqlite_master WHERE type='table'");
            echo "SQLite Tables:\n";
            print_r($tables);
        } catch (\Exception $e) {
            try {
                $tables = \Illuminate\Support\Facades\DB::select("SHOW TABLES");
                echo "MySQL Tables:\n";
                print_r($tables);
            } catch (\Exception $ex) {
                echo "Error listing tables: " . $ex->getMessage() . "\n";
            }
        }

        // Fetch School Admin user
        $this->schoolAdmin = User::whereHas('roles', function ($query) {
            $query->where('name', 'School Admin');
        })->first();

        $this->school = School::find($this->schoolAdmin->school_id);

        // Enable new modules for school
        $modules = \App\Models\Module::all();
        foreach ($modules as $module) {
            $this->school->modules()->syncWithoutDetaching([
                $module->id => ['is_active' => true]
            ]);
        }

        // Give all permissions to School Admin to allow managing everything in tests
        $role = $this->schoolAdmin->roles()->first();
        $role->syncPermissions(Permission::all());
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        // Create academic setup
        $this->academicYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'title' => '2026-2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
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
            'name' => 'Section A',
            'status' => 'active'
        ]);

        $this->student = Student::create([
            'school_id' => $this->school->id,
            'admission_no' => 'ADM-001',
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'gender' => 'Female',
            'date_of_birth' => '2011-05-15',
            'admission_date' => '2026-06-01',
            'status' => 'active'
        ]);

        StudentAcademicRecord::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'roll_no' => '10',
            'status' => 'active'
        ]);
    }

    public function test_subject_crud()
    {
        // 1. Create Subject
        $response = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/subjects', [
                'academic_year_id' => $this->academicYear->id,
                'class_id' => $this->class->id,
                'section_id' => $this->section->id,
                'name' => 'Mathematics',
                'code' => 'MATH101',
                'description' => 'Algebra and Geometry',
                'status' => 'active'
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('subject.name', 'Mathematics');

        $subjectId = $response->json('subject.id');

        // 2. List Subjects
        $this->actingAs($this->schoolAdmin)
            ->getJson('/api/subjects')
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Mathematics']);

        // 3. Edit/Update Subject
        $this->actingAs($this->schoolAdmin)
            ->putJson("/api/subjects/{$subjectId}", [
                'academic_year_id' => $this->academicYear->id,
                'class_id' => $this->class->id,
                'section_id' => $this->section->id,
                'name' => 'Advanced Math',
                'code' => 'MATH102',
                'description' => 'Calculus',
                'status' => 'active'
            ])
            ->assertStatus(200)
            ->assertJsonPath('subject.name', 'Advanced Math');

        // 4. Toggle Status
        $this->actingAs($this->schoolAdmin)
            ->patchJson("/api/subjects/{$subjectId}/toggle-status")
            ->assertStatus(200)
            ->assertJsonPath('subject.status', 'inactive');

        // 5. Delete Subject
        $this->actingAs($this->schoolAdmin)
            ->deleteJson("/api/subjects/{$subjectId}")
            ->assertStatus(200);

        $this->assertDatabaseHas('subjects', [
            'id' => $subjectId,
            'is_delete' => 1
        ]);
    }

    public function test_class_level_common_subject_retrieval()
    {
        // 1. Create a class-level common subject (section_id = null)
        $commonResponse = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/subjects', [
                'academic_year_id' => $this->academicYear->id,
                'class_id' => $this->class->id,
                'section_id' => null,
                'name' => 'Common Science',
                'code' => 'SCI101',
                'description' => 'Common science for all sections',
                'status' => 'active'
            ]);

        $commonResponse->assertStatus(201);
        $this->assertDatabaseHas('subjects', [
            'name' => 'Common Science',
            'section_id' => null,
            'class_id' => $this->class->id
        ]);

        // 2. Create a section-specific subject
        $specificResponse = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/subjects', [
                'academic_year_id' => $this->academicYear->id,
                'class_id' => $this->class->id,
                'section_id' => $this->section->id,
                'name' => 'Section A Math',
                'code' => 'MATH-A',
                'status' => 'active'
            ]);
        $specificResponse->assertStatus(201);

        // 3. List subjects filtering by the section
        $listResponse = $this->actingAs($this->schoolAdmin)
            ->getJson('/api/subjects?section_id=' . $this->section->id . '&all=true')
            ->assertStatus(200);

        // It should contain both the section-specific subject AND the common subject
        $listResponse->assertJsonFragment(['name' => 'Common Science']);
        $listResponse->assertJsonFragment(['name' => 'Section A Math']);
    }

    public function test_attendance_marking_and_reporting()
    {
        // 1. Load students for attendance
        $this->actingAs($this->schoolAdmin)
            ->getJson('/api/attendances/students?' . http_build_query([
                'academic_year_id' => $this->academicYear->id,
                'class_id' => $this->class->id,
                'section_id' => $this->section->id,
                'attendance_date' => '2026-06-09'
            ]))
            ->assertStatus(200)
            ->assertJsonCount(1, 'students')
            ->assertJsonPath('students.0.student_id', $this->student->id);

        // 2. Mark attendance
        $this->actingAs($this->schoolAdmin)
            ->postJson('/api/attendances/save', [
                'academic_year_id' => $this->academicYear->id,
                'class_id' => $this->class->id,
                'section_id' => $this->section->id,
                'attendance_date' => '2026-06-09',
                'students' => [
                    [
                        'student_id' => $this->student->id,
                        'status' => 'Present',
                        'remarks' => 'On time'
                    ]
                ]
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $this->student->id,
            'attendance_date' => '2026-06-09',
            'status' => 'Present'
        ]);

        // 3. Monthly view
        $this->actingAs($this->schoolAdmin)
            ->getJson('/api/attendances/monthly?' . http_build_query([
                'academic_year_id' => $this->academicYear->id,
                'class_id' => $this->class->id,
                'section_id' => $this->section->id,
                'month' => '2026-06'
            ]))
            ->assertStatus(200)
            ->assertJsonPath('matrix.0.days.9', 'Present');

        // 4. Student report
        $this->actingAs($this->schoolAdmin)
            ->getJson('/api/attendances/student-report?' . http_build_query([
                'student_id' => $this->student->id,
                'academic_year_id' => $this->academicYear->id
            ]))
            ->assertStatus(200)
            ->assertJsonPath('stats.Present', 1);

        // 4b. Student report PDF download
        $this->actingAs($this->schoolAdmin)
            ->get('/api/attendances/student-report/pdf?' . http_build_query([
                'student_id' => $this->student->id,
                'academic_year_id' => $this->academicYear->id
            ]))
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');

        // 5. Class report
        $this->actingAs($this->schoolAdmin)
            ->getJson('/api/attendances/class-report?' . http_build_query([
                'academic_year_id' => $this->academicYear->id,
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-30'
            ]))
            ->assertStatus(200)
            ->assertJsonPath('report.0.presents', 1);

        // 6. Mark Staff Attendance
        $this->actingAs($this->schoolAdmin)
            ->postJson('/api/staff-attendances/save', [
                'attendance_date' => '2026-06-09',
                'staff' => [
                    [
                        'user_id' => $this->schoolAdmin->id,
                        'status' => 'Present',
                        'remarks' => 'Admin present'
                    ]
                ]
            ])
            ->assertStatus(200);

        // 7. Staff report
        $this->actingAs($this->schoolAdmin)
            ->getJson('/api/staff-attendances/report?' . http_build_query([
                'user_id' => $this->schoolAdmin->id,
            ]))
            ->assertStatus(200)
            ->assertJsonPath('stats.Present', 1);

        // 8. Staff report PDF download
        $this->actingAs($this->schoolAdmin)
            ->get('/api/staff-attendances/report/pdf?' . http_build_query([
                'user_id' => $this->schoolAdmin->id,
            ]))
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');

        // 9. Staff monthly grid JSON
        $response = $this->actingAs($this->schoolAdmin)
            ->getJson('/api/staff-attendances/monthly?' . http_build_query([
                'month' => '2026-06'
            ]))
            ->assertStatus(200);
        
        $matrix = $response->json('matrix');
        $adminRow = collect($matrix)->firstWhere('user_id', $this->schoolAdmin->id);
        $this->assertNotNull($adminRow);
        $this->assertEquals('Present', $adminRow['days'][9]);

        // 10. Staff monthly grid PDF download
        $this->actingAs($this->schoolAdmin)
            ->get('/api/staff-attendances/monthly/pdf?' . http_build_query([
                'month' => '2026-06'
            ]))
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_homework_crud_with_attachments()
    {
        Storage::fake('public');

        $subject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'name' => 'English',
            'status' => 'active'
        ]);

        $file = UploadedFile::fake()->create('assignment.pdf', 100);

        // 1. Create homework
        $response = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/homeworks', [
                'academic_year_id' => $this->academicYear->id,
                'class_id' => $this->class->id,
                'section_id' => $this->section->id,
                'subject_id' => $subject->id,
                'title' => 'Read Chapter 3',
                'description' => 'Write a summary of Chapter 3.',
                'submission_date' => '2026-06-15',
                'attachment' => $file,
                'status' => 'active'
            ]);

        $response->assertStatus(201);
        $homeworkId = $response->json('homework.id');
        $attachmentPath = $response->json('homework.attachment');

        $this->assertNotNull($attachmentPath);
        Storage::disk('public')->assertExists($attachmentPath);

        // 2. Update homework (keeping attachment)
        $this->actingAs($this->schoolAdmin)
            ->postJson("/api/homeworks/{$homeworkId}", [
                'academic_year_id' => $this->academicYear->id,
                'class_id' => $this->class->id,
                'section_id' => $this->section->id,
                'subject_id' => $subject->id,
                'title' => 'Read Chapter 3 & 4',
                'description' => 'Summarize chapters.',
                'submission_date' => '2026-06-16',
                'attachment' => $attachmentPath,
                'status' => 'active'
            ])
            ->assertStatus(200)
            ->assertJsonPath('homework.title', 'Read Chapter 3 & 4');

        // 3. Delete homework
        $this->actingAs($this->schoolAdmin)
            ->deleteJson("/api/homeworks/{$homeworkId}")
            ->assertStatus(200);

        $this->assertDatabaseHas('homeworks', [
            'id' => $homeworkId,
            'is_delete' => 1
        ]);
        Storage::disk('public')->assertMissing($attachmentPath);
    }

    public function test_notice_crud()
    {
        Storage::fake('public');

        $file1 = UploadedFile::fake()->create('announcement1.jpg', 100);

        // 1. Create Notice
        $response = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/notices', [
                'title' => 'Annual Day Notice',
                'description' => 'Details of the annual function.',
                'notice_date' => '2026-06-09',
                'target_type' => 'Entire School',
                'attachment' => $file1,
                'status' => 'active'
            ]);

        $response->assertStatus(201);
        $noticeId = $response->json('notice.id');
        $attachmentPath1 = $response->json('notice.attachment');

        $this->assertNotNull($attachmentPath1);
        Storage::disk('public')->assertExists($attachmentPath1);

        // 2. List Notices
        $this->actingAs($this->schoolAdmin)
            ->getJson('/api/notices')
            ->assertStatus(200)
            ->assertJsonFragment(['title' => 'Annual Day Notice']);

        $file2 = UploadedFile::fake()->create('announcement2.jpg', 200);

        // 3. Update Notice to Class Wise with new attachment
        $response2 = $this->actingAs($this->schoolAdmin)
            ->postJson("/api/notices/{$noticeId}", [
                'title' => 'Grade 10 Special Notice',
                'description' => 'Special instruction for grade 10.',
                'notice_date' => '2026-06-10',
                'target_type' => 'Class Wise',
                'class_id' => $this->class->id,
                'attachment' => $file2,
                'status' => 'active',
                '_method' => 'PUT'
            ]);

        $response2->assertStatus(200);
        $attachmentPath2 = $response2->json('notice.attachment');

        $this->assertNotNull($attachmentPath2);
        $this->assertNotEquals($attachmentPath1, $attachmentPath2);
        Storage::disk('public')->assertMissing($attachmentPath1);
        Storage::disk('public')->assertExists($attachmentPath2);

        // 4. Delete Notice
        $this->actingAs($this->schoolAdmin)
            ->deleteJson("/api/notices/{$noticeId}")
            ->assertStatus(200);

        $this->assertDatabaseHas('notices', [
            'id' => $noticeId,
            'is_delete' => 1
        ]);
        Storage::disk('public')->assertMissing($attachmentPath2);
    }

    public function test_holiday_crud()
    {
        // 1. Create Holiday (target_type = all)
        $response = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/holidays', [
                'academic_year_id' => $this->academicYear->id,
                'title' => 'Independence Day',
                'description' => 'National Holiday',
                'holiday_date' => '2026-07-04',
                'target_type' => 'all',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('holiday.title', 'Independence Day');

        $holidayId = $response->json('holiday.id');

        // Verify H attendance is marked for the student
        $this->assertDatabaseHas('attendances', [
            'student_id' => $this->student->id,
            'attendance_date' => '2026-07-04',
            'status' => 'Holiday'
        ]);

        // Verify H attendance is marked for the staff (schoolAdmin)
        $this->assertDatabaseHas('staff_attendances', [
            'user_id' => $this->schoolAdmin->id,
            'attendance_date' => '2026-07-04',
            'status' => 'Holiday'
        ]);

        // 1.2 Create Holiday (target_type = students)
        $responseStudentsOnly = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/holidays', [
                'academic_year_id' => $this->academicYear->id,
                'title' => 'Student Picnic Day',
                'description' => 'Holiday for students only',
                'holiday_date' => '2026-07-10',
                'target_type' => 'students',
            ]);

        $responseStudentsOnly->assertStatus(200)
            ->assertJsonPath('holiday.title', 'Student Picnic Day');

        $studentsOnlyHolidayId = $responseStudentsOnly->json('holiday.id');

        // Verify H attendance is marked for the student on this holiday
        $this->assertDatabaseHas('attendances', [
            'student_id' => $this->student->id,
            'attendance_date' => '2026-07-10',
            'status' => 'Holiday'
        ]);

        // Verify H attendance is NOT marked for the staff on this holiday
        $this->assertDatabaseMissing('staff_attendances', [
            'user_id' => $this->schoolAdmin->id,
            'attendance_date' => '2026-07-10',
            'status' => 'Holiday'
        ]);

        // 2. List Holidays
        $this->actingAs($this->schoolAdmin)
            ->getJson('/api/holidays?academic_year_id=' . $this->academicYear->id)
            ->assertStatus(200)
            ->assertJsonFragment(['title' => 'Independence Day'])
            ->assertJsonFragment(['title' => 'Student Picnic Day']);

        // 3. Update Holiday to inactive
        $this->actingAs($this->schoolAdmin)
            ->putJson("/api/holidays/{$holidayId}", [
                'title' => 'Independence Day Updated',
                'description' => 'National Holiday Updated',
                'status' => 'inactive',
            ])
            ->assertStatus(200);

        // Verify H attendance is removed since holiday is inactive
        $this->assertDatabaseMissing('attendances', [
            'student_id' => $this->student->id,
            'attendance_date' => '2026-07-04',
            'status' => 'Holiday'
        ]);

        // 4. Delete Holiday
        $this->actingAs($this->schoolAdmin)
            ->deleteJson("/api/holidays/{$holidayId}")
            ->assertStatus(200);

        $this->assertDatabaseHas('holidays', [
            'id' => $holidayId,
            'is_delete' => 1
        ]);
    }

    public function test_auto_holiday_insert_for_new_student_and_staff()
    {
        // 1. Create a holiday first
        $holidayDate = '2026-12-25';
        $response = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/holidays', [
                'academic_year_id' => $this->academicYear->id,
                'title' => 'Christmas Holiday',
                'description' => 'Winter break',
                'holiday_date' => $holidayDate,
                'target_type' => 'all',
            ]);

        $response->assertStatus(200);

        // Verify holiday exists in DB
        $this->assertDatabaseHas('holidays', [
            'school_id' => $this->school->id,
            'holiday_date' => $holidayDate,
            'target_type' => 'all'
        ]);

        // 2. Admit a new student via API
        $studentData = [
            'admission_no' => 'ADM-TEMP-999',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'Male',
            'date_of_birth' => '2015-08-12',
            'admission_date' => '2026-06-01',
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'status' => 'active'
        ];

        $studentResponse = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/students', $studentData);

        $studentResponse->assertStatus(201);
        $newStudentId = $studentResponse->json('student.id');

        // Check that Christmas Holiday H attendance is automatically marked for this new student
        $this->assertDatabaseHas('attendances', [
            'school_id' => $this->school->id,
            'student_id' => $newStudentId,
            'attendance_date' => $holidayDate,
            'status' => 'Holiday'
        ]);

        // 3. Create a new staff user via API
        $roleId = $this->schoolAdmin->roles()->first()->id;
        $staffData = [
            'name' => 'New Staff User',
            'email' => 'newstaff999@example.com',
            'password' => 'password123',
            'status' => 'active',
            'role' => $roleId
        ];

        $staffResponse = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/users', $staffData);

        $staffResponse->assertStatus(201);
        $newStaffId = $staffResponse->json('user.id');

        // Check that Christmas Holiday H attendance is automatically marked for this new staff
        $this->assertDatabaseHas('staff_attendances', [
            'school_id' => $this->school->id,
            'user_id' => $newStaffId,
            'attendance_date' => $holidayDate,
            'status' => 'Holiday'
        ]);
    }

    public function test_student_admission_and_roll_number_uniqueness()
    {
        // 1. Same admission number within the same school should fail validation
        $studentDataWithDuplicateAdmission = [
            'admission_no' => 'ADM-001', // already exists
            'first_name' => 'Duplicate',
            'last_name' => 'Admission',
            'gender' => 'Male',
            'date_of_birth' => '2015-08-12',
            'admission_date' => '2026-06-01',
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'status' => 'active'
        ];

        $response = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/students', $studentDataWithDuplicateAdmission);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['admission_no']);

        // 2. Same roll number in the same academic year/class/section should fail validation
        $studentDataWithDuplicateRoll = [
            'admission_no' => 'ADM-UNIQUE-002',
            'first_name' => 'Duplicate',
            'last_name' => 'Roll',
            'gender' => 'Male',
            'date_of_birth' => '2015-08-12',
            'admission_date' => '2026-06-01',
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'roll_no' => '10', // already exists (Alice has 10 in Section A)
            'status' => 'active'
        ];

        $response = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/students', $studentDataWithDuplicateRoll);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['roll_no']);

        // 3. Same roll number in a different section or class should pass validation
        $otherSection = Section::create([
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'name' => 'Section B',
            'status' => 'active'
        ]);

        $studentDataWithRollInDifferentSection = [
            'admission_no' => 'ADM-UNIQUE-003',
            'first_name' => 'Unique',
            'last_name' => 'Student',
            'gender' => 'Male',
            'date_of_birth' => '2015-08-12',
            'admission_date' => '2026-06-01',
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->class->id,
            'section_id' => $otherSection->id,
            'roll_no' => '10', // Alice has 10, but in Section A. This is Section B.
            'status' => 'active'
        ];

        $response = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/students', $studentDataWithRollInDifferentSection);

        $response->assertStatus(201);
    }

    public function test_session_wise_filtering_for_modules()
    {
        // 1. Create a new academic year and set it as active (current)
        $newAcademicYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'title' => '2027-2028',
            'start_date' => '2027-06-01',
            'end_date' => '2028-05-31',
            'is_current' => true,
            'status' => 'active'
        ]);

        // Deactivate the old one to simulate the activation of the new session
        $this->academicYear->update(['is_current' => false]);

        // 2. Create class, section, subject, student, homework and notice in the new academic year
        $newClass = ClassModel::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $newAcademicYear->id,
            'name' => 'New Class',
            'status' => 'active'
        ]);

        $newSection = Section::create([
            'school_id' => $this->school->id,
            'class_id' => $newClass->id,
            'name' => 'New Section',
            'status' => 'active'
        ]);

        $newSubject = \App\Models\Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $newAcademicYear->id,
            'class_id' => $newClass->id,
            'section_id' => $newSection->id,
            'name' => 'New Subject',
            'code' => 'NS101',
            'status' => 'active'
        ]);

        $newStudent = Student::create([
            'school_id' => $this->school->id,
            'admission_no' => 'ADM-NEW-001',
            'first_name' => 'New Student',
            'last_name' => 'Name',
            'gender' => 'Male',
            'date_of_birth' => '2015-08-12',
            'admission_date' => '2027-06-01',
            'status' => 'active'
        ]);

        StudentAcademicRecord::create([
            'school_id' => $this->school->id,
            'student_id' => $newStudent->id,
            'academic_year_id' => $newAcademicYear->id,
            'class_id' => $newClass->id,
            'section_id' => $newSection->id,
            'roll_no' => '01',
            'status' => 'active'
        ]);

        $newHomework = Homework::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $newAcademicYear->id,
            'class_id' => $newClass->id,
            'section_id' => $newSection->id,
            'subject_id' => $newSubject->id,
            'title' => 'New Homework',
            'description' => 'Test',
            'homework_date' => '2027-06-05',
            'submission_date' => '2027-06-10',
            'created_by' => $this->schoolAdmin->id,
            'status' => 'active'
        ]);

        $newNotice = Notice::create([
            'school_id' => $this->school->id,
            'title' => 'New Notice',
            'description' => 'Test',
            'notice_date' => '2027-06-05',
            'target_type' => 'Class Wise',
            'class_id' => $newClass->id,
            'section_id' => $newSection->id,
            'created_by' => $this->schoolAdmin->id,
            'status' => 'active'
        ]);

        // Class index
        $response = $this->actingAs($this->schoolAdmin)->getJson('/api/classes');
        $response->assertStatus(200);
        $classes = $response->json('classes');
        $this->assertCount(1, $classes);
        $this->assertEquals('New Class', $classes[0]['name']);

        // Section index
        $response = $this->actingAs($this->schoolAdmin)->getJson('/api/sections');
        $response->assertStatus(200);
        $sections = $response->json('sections');
        $this->assertCount(1, $sections);
        $this->assertEquals('New Section', $sections[0]['name']);

        // Subject index
        $response = $this->actingAs($this->schoolAdmin)->getJson('/api/subjects?all=true');
        $response->assertStatus(200);
        $subjects = $response->json('subjects');
        $this->assertCount(1, $subjects);
        $this->assertEquals('New Subject', $subjects[0]['name']);

        // Student index
        $response = $this->actingAs($this->schoolAdmin)->getJson('/api/students');
        $response->assertStatus(200);
        $students = $response->json('data');
        $this->assertCount(1, $students);
        $this->assertEquals('ADM-NEW-001', $students[0]['admission_no']);

        // Homework index
        $response = $this->actingAs($this->schoolAdmin)->getJson('/api/homeworks');
        $response->assertStatus(200);
        $homeworks = $response->json('data');
        $this->assertCount(1, $homeworks);
        $this->assertEquals('New Homework', $homeworks[0]['title']);

        // Notice index
        $response = $this->actingAs($this->schoolAdmin)->getJson('/api/notices');
        $response->assertStatus(200);
        $notices = $response->json('data');
        $this->assertCount(1, $notices);
        $this->assertEquals('New Notice', $notices[0]['title']);
    }
}
