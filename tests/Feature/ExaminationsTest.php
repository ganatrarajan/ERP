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
use App\Models\GradeScale;
use App\Models\ExamType;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\ExamMark;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ExaminationsTest extends TestCase
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
        $this->seed(ModuleSeeder::class);

        // Fetch School Admin user
        $this->schoolAdmin = User::whereHas('roles', function ($query) {
            $query->where('name', 'School Admin')->orWhere('name', 'Admin');
        })->first();

        if (!$this->schoolAdmin) {
            // Create fallback school and admin if seeder didn't make one
            $this->school = School::create(['name' => 'Test Academy', 'status' => 'active']);
            $this->schoolAdmin = User::create([
                'school_id' => $this->school->id,
                'name' => 'School Admin',
                'email' => 'admin@testacademy.com',
                'password' => bcrypt('password123'),
                'status' => 'active'
            ]);
            $role = \Spatie\Permission\Models\Role::create([
                'name' => 'School Admin',
                'guard_name' => 'web',
                'school_id' => $this->school->id
            ]);
            $this->schoolAdmin->assignRole($role);
        } else {
            $this->school = School::find($this->schoolAdmin->school_id);
        }

        // Enable new modules for school
        $this->school->modules()->syncWithoutDetaching([
            \App\Models\Module::where('slug', 'examinations')->first()->id => ['is_active' => true]
        ]);

        // Give all permissions to School Admin
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
            'admission_no' => 'ADM-EXAM-001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'Male',
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

    public function test_grade_scales_workflow()
    {
        // 1. Create Grade Scale
        $response = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/grade-scales', [
                'grade' => 'A+',
                'min_percentage' => 90.00,
                'grade_point' => 9.5,
                'description' => 'Excellent performance',
                'status' => 'active'
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('grade_scale.grade', 'A+');

        $gradeScaleId = $response->json('grade_scale.id');

        // 2. List Grade Scales
        $this->actingAs($this->schoolAdmin)
            ->getJson('/api/grade-scales')
            ->assertStatus(200)
            ->assertJsonFragment(['grade' => 'A+']);

        // 3. Update Grade Scale
        $this->actingAs($this->schoolAdmin)
            ->putJson("/api/grade-scales/{$gradeScaleId}", [
                'grade' => 'A++',
                'min_percentage' => 95.00,
                'grade_point' => 10.0,
                'description' => 'Outstanding performance',
                'status' => 'active'
            ])
            ->assertStatus(200)
            ->assertJsonPath('grade_scale.grade', 'A++');

        // 4. Toggle Status
        $this->actingAs($this->schoolAdmin)
            ->patchJson("/api/grade-scales/{$gradeScaleId}/toggle-status")
            ->assertStatus(200)
            ->assertJsonPath('grade_scale.status', 'inactive');

        // 5. Delete Grade Scale
        $this->actingAs($this->schoolAdmin)
            ->deleteJson("/api/grade-scales/{$gradeScaleId}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('grade_scales', [
            'id' => $gradeScaleId
        ]);
    }

    public function test_exam_types_workflow()
    {
        // 1. Create Exam Type
        $response = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/exam-types', [
                'name' => 'Mid Term',
                'description' => 'Mid academic session examination',
                'status' => 'active'
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('exam_type.name', 'Mid Term');

        $examTypeId = $response->json('exam_type.id');

        // 2. List Exam Types
        $this->actingAs($this->schoolAdmin)
            ->getJson('/api/exam-types')
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Mid Term']);

        // 3. Update Exam Type
        $this->actingAs($this->schoolAdmin)
            ->putJson("/api/exam-types/{$examTypeId}", [
                'name' => 'Term 1 Mid',
                'description' => 'Updated desc',
                'status' => 'active'
            ])
            ->assertStatus(200)
            ->assertJsonPath('exam_type.name', 'Term 1 Mid');

        // 4. Toggle Status
        $this->actingAs($this->schoolAdmin)
            ->patchJson("/api/exam-types/{$examTypeId}/toggle-status")
            ->assertStatus(200)
            ->assertJsonPath('exam_type.status', 'inactive');

        // 5. Delete Exam Type
        $this->actingAs($this->schoolAdmin)
            ->deleteJson("/api/exam-types/{$examTypeId}")
            ->assertStatus(200);

        $this->assertDatabaseHas('exam_types', [
            'id' => $examTypeId,
            'is_delete' => 1
        ]);
    }

    public function test_exams_master_and_scheduling_workflow()
    {
        $examType = ExamType::create([
            'school_id' => $this->school->id,
            'name' => 'Final Exam',
            'status' => 'active'
        ]);

        $subject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'name' => 'Science',
            'code' => 'SCI-10',
            'status' => 'active'
        ]);

        // 1. Create Exam Master
        $response = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/exams', [
                'academic_year_id' => $this->academicYear->id,
                'exam_type_id' => $examType->id,
                'name' => 'Annual Exam 2026',
                'start_date' => '2026-11-10',
                'end_date' => '2026-11-20',
                'description' => 'Final terms',
                'status' => 'draft'
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('exam.name', 'Annual Exam 2026');

        $examId = $response->json('exam.id');

        // 2. Schedule Config Bulk Save
        $scheduleData = [
            'academic_year_id' => $this->academicYear->id,
            'exam_id' => $examId,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'schedules' => [
                [
                    'subject_id' => $subject->id,
                    'exam_date' => '2026-11-12',
                    'start_time' => '09:00:00',
                    'end_time' => '12:00:00',
                    'max_marks' => 100
                ]
            ]
        ];

        $this->actingAs($this->schoolAdmin)
            ->postJson('/api/exam-schedules', $scheduleData)
            ->assertStatus(200);

        // Try to schedule with a date outside range (start: 2026-11-10, end: 2026-11-20)
        $invalidScheduleData = $scheduleData;
        $invalidScheduleData['schedules'][0]['exam_date'] = '2026-11-25'; // Invalid date (out of range)

        $this->actingAs($this->schoolAdmin)
            ->postJson('/api/exam-schedules', $invalidScheduleData)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['schedules']);

        $this->assertDatabaseHas('exam_schedules', [
            'exam_id' => $examId,
            'subject_id' => $subject->id,
            'max_marks' => 100
        ]);

        // 3. Retrieve Schedules
        $this->actingAs($this->schoolAdmin)
            ->getJson('/api/exam-schedules?' . http_build_query([
                'academic_year_id' => $this->academicYear->id,
                'exam_id' => $examId,
                'class_id' => $this->class->id,
                'section_id' => $this->section->id
            ]))
            ->assertStatus(200)
            ->assertJsonFragment(['subject_id' => $subject->id]);
    }

    public function test_marks_entry_and_results_compilation()
    {
        $gradeScale = GradeScale::create([
            'school_id' => $this->school->id,
            'grade' => 'A',
            'min_percentage' => 80.00,
            'grade_point' => 8.0,
            'status' => 'active'
        ]);

        $examType = ExamType::create([
            'school_id' => $this->school->id,
            'name' => 'Monthly Unit Test',
            'status' => 'active'
        ]);

        $exam = Exam::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'exam_type_id' => $examType->id,
            'name' => 'Unit Test 1',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-05',
            'status' => 'published'
        ]);

        $subject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'name' => 'History',
            'code' => 'HIST-10',
            'evaluation_type' => 'marks',
            'subject_category' => 'scholastic',
            'maximum_marks' => 100,
            'passing_marks' => 40,
            'grade_scale_id' => $gradeScale->id,
            'status' => 'active'
        ]);

        $schedule = ExamSchedule::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'exam_id' => $exam->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'subject_id' => $subject->id,
            'exam_date' => '2026-07-02',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'max_marks' => 100
        ]);

        // 1. Enter Marks
        $marksPayload = [
            'academic_year_id' => $this->academicYear->id,
            'exam_id' => $exam->id,
            'exam_schedule_id' => $schedule->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'subject_id' => $subject->id,
            'marks' => [
                [
                    'student_id' => $this->student->id,
                    'marks_obtained' => 85,
                    'remarks' => 'Good effort'
                ]
            ]
        ];

        $this->actingAs($this->schoolAdmin)
            ->postJson('/api/exam-marks/save', $marksPayload)
            ->assertStatus(200);

        $this->assertDatabaseHas('exam_marks', [
            'student_id' => $this->student->id,
            'marks_obtained' => 85.00
        ]);

        // 2. Fetch Student Marks for Sheet
        $this->actingAs($this->schoolAdmin)
            ->getJson('/api/exam-marks/students?' . http_build_query([
                'academic_year_id' => $this->academicYear->id,
                'exam_id' => $exam->id,
                'exam_schedule_id' => $schedule->id,
                'class_id' => $this->class->id,
                'section_id' => $this->section->id,
                'subject_id' => $subject->id
            ]))
            ->assertStatus(200)
            ->assertJsonFragment(['marks_obtained' => "85.00"]);

        // 3. Compile Consolidated Class Status/Ranks
        $this->actingAs($this->schoolAdmin)
            ->getJson('/api/report-cards/students-status?' . http_build_query([
                'academic_year_id' => $this->academicYear->id,
                'exam_id' => $exam->id,
                'class_id' => $this->class->id,
                'section_id' => $this->section->id
            ]))
            ->assertStatus(200)
            ->assertJsonPath('results.0.percentage', 85)
            ->assertJsonPath('results.0.result', 'Pass');

        // 4. Download Report Card PDF
        $pdfResponse = $this->actingAs($this->schoolAdmin)
            ->get('/api/report-cards/pdf?' . http_build_query([
                'academic_year_id' => $this->academicYear->id,
                'exam_id' => $exam->id,
                'class_id' => $this->class->id,
                'section_id' => $this->section->id,
                'student_id' => $this->student->id
            ]));

        $pdfResponse->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_consolidated_report_card_flow()
    {
        $gradeScale = GradeScale::create([
            'school_id' => $this->school->id,
            'grade' => 'A',
            'min_percentage' => 80.00,
            'grade_point' => 8.0,
            'status' => 'active'
        ]);

        $examType = ExamType::create([
            'school_id' => $this->school->id,
            'name' => 'Consolidated Testing',
            'status' => 'active'
        ]);

        $exam1 = Exam::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'exam_type_id' => $examType->id,
            'name' => 'Mid Term Exam',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-05',
            'status' => 'published'
        ]);

        $exam2 = Exam::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'exam_type_id' => $examType->id,
            'name' => 'Final Term Exam',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-05',
            'status' => 'published'
        ]);

        $subject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'name' => 'Mathematics',
            'code' => 'MATH-10',
            'evaluation_type' => 'marks',
            'subject_category' => 'scholastic',
            'maximum_marks' => 100,
            'passing_marks' => 40,
            'grade_scale_id' => $gradeScale->id,
            'status' => 'active'
        ]);

        // Schedule Exam 1
        $schedule1 = ExamSchedule::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'exam_id' => $exam1->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'subject_id' => $subject->id,
            'exam_date' => '2026-07-02',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'max_marks' => 100,
            'report_card_visibility' => 'included_in_result'
        ]);

        // Schedule Exam 2 with a custom override (display_only)
        $schedule2 = ExamSchedule::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'exam_id' => $exam2->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'subject_id' => $subject->id,
            'exam_date' => '2026-11-02',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'max_marks' => 100,
            'report_card_visibility' => 'display_only'
        ]);

        // Enter Marks for Exam 1 (80 marks)
        ExamMark::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'exam_id' => $exam1->id,
            'exam_schedule_id' => $schedule1->id,
            'student_id' => $this->student->id,
            'subject_id' => $subject->id,
            'marks_obtained' => 80
        ]);

        // Enter Marks for Exam 2 (90 marks)
        ExamMark::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'exam_id' => $exam2->id,
            'exam_schedule_id' => $schedule2->id,
            'student_id' => $this->student->id,
            'subject_id' => $subject->id,
            'marks_obtained' => 90
        ]);

        // Check consolidation results status
        $response = $this->actingAs($this->schoolAdmin)
            ->getJson('/api/report-cards/students-status?' . http_build_query([
                'academic_year_id' => $this->academicYear->id,
                'exam_id' => $exam1->id,
                'exam_id_2' => $exam2->id,
                'class_id' => $this->class->id,
                'section_id' => $this->section->id
            ]));

        $response->assertStatus(200);
        $resultData = $response->json('results.0');

        // Check that marks are loaded correctly for both exams in subject details
        $mathDetails = collect($resultData['subject_details'])->firstWhere('subject_code', 'MATH-10');
        $this->assertEquals(80, $mathDetails['exam1_obtained_marks']);
        $this->assertEquals(90, $mathDetails['exam2_obtained_marks']);

        // Since Exam 2 is "display_only", the combined totals should ONLY include Exam 1 (max 100, obtained 80)
        $this->assertEquals(100, $resultData['total_max_marks']);
        $this->assertEquals(80, $resultData['total_obtained_marks']);
        $this->assertEquals(80, $resultData['percentage']);

        // Since Mathematics in Exam 2 is display_only, Exam 2's overall total should be 0
        $this->assertEquals(0, $resultData['exam2_obtained_marks']);
        $this->assertEquals(0, $resultData['exam2_max_marks']);

        // Download consolidated PDF
        $pdfResponse = $this->actingAs($this->schoolAdmin)
            ->get('/api/report-cards/pdf?' . http_build_query([
                'academic_year_id' => $this->academicYear->id,
                'exam_id' => $exam1->id,
                'exam_id_2' => $exam2->id,
                'class_id' => $this->class->id,
                'section_id' => $this->section->id,
                'student_id' => $this->student->id
            ]));

        $pdfResponse->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_report_card_graded_subject_exclusion()
    {
        $gradeScale = GradeScale::create([
            'school_id' => $this->school->id,
            'grade' => 'B',
            'min_percentage' => 70.0,
            'grade_point' => 7.0,
            'status' => 'active'
        ]);

        $examType = ExamType::create([
            'school_id' => $this->school->id,
            'name' => 'Report Card Testing',
            'status' => 'active'
        ]);

        $exam = Exam::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'exam_type_id' => $examType->id,
            'name' => 'First Term Exam',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-05',
            'status' => 'published'
        ]);

        // 1. Subject evaluated by Marks (English)
        $subjMarks = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'name' => 'English',
            'code' => 'ENG-10',
            'evaluation_type' => 'marks',
            'subject_category' => 'scholastic',
            'maximum_marks' => 100,
            'passing_marks' => 35,
            'status' => 'active'
        ]);

        // 2. Subject evaluated by Grades (Music)
        $subjGrades = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'name' => 'Music',
            'code' => 'MUS-10',
            'evaluation_type' => 'grades',
            'subject_category' => 'scholastic',
            'maximum_marks' => 100,
            'passing_marks' => 35,
            'status' => 'active'
        ]);

        // Schedule both
        $schedMarks = ExamSchedule::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'exam_id' => $exam->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'subject_id' => $subjMarks->id,
            'exam_date' => '2026-07-02',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'max_marks' => 100,
            'report_card_visibility' => 'included_in_result'
        ]);

        $schedGrades = ExamSchedule::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'exam_id' => $exam->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'subject_id' => $subjGrades->id,
            'exam_date' => '2026-07-03',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'max_marks' => 100,
            'report_card_visibility' => 'included_in_result'
        ]);

        // Enter Marks for English
        ExamMark::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'exam_id' => $exam->id,
            'exam_schedule_id' => $schedMarks->id,
            'student_id' => $this->student->id,
            'subject_id' => $subjMarks->id,
            'marks_obtained' => 90
        ]);

        // Enter Grade for Music
        ExamMark::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'exam_id' => $exam->id,
            'exam_schedule_id' => $schedGrades->id,
            'student_id' => $this->student->id,
            'subject_id' => $subjGrades->id,
            'grade_id' => $gradeScale->id
        ]);

        // Check single exam results status
        $response = $this->actingAs($this->schoolAdmin)
            ->getJson('/api/report-cards/students-status?' . http_build_query([
                'academic_year_id' => $this->academicYear->id,
                'exam_id' => $exam->id,
                'class_id' => $this->class->id,
                'section_id' => $this->section->id,
                'include_graded' => 'yes'
            ]));

        $response->assertStatus(200);
        $resultData = $response->json('results.0');

        // Check that Music is marked N/A (Grade Evaluated) on obtained, but has its grade
        $musicDetails = collect($resultData['subject_details'])->firstWhere('subject_code', 'MUS-10');
        $this->assertNull($musicDetails['obtained_marks']);
        $this->assertEquals('B', $musicDetails['grade']);

        // Check that total max marks is 100 (from English only, excluding Music's 100 max marks)
        $this->assertEquals(100, $resultData['total_max_marks']);
        $this->assertEquals(90, $resultData['total_obtained_marks']);
        $this->assertEquals(90, $resultData['percentage']);
    }

    public function test_academic_year_locking_enforced_on_exams_and_subjects()
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

        $examType = ExamType::create([
            'school_id' => $this->school->id,
            'name' => 'Monthly Unit Test',
            'status' => 'active'
        ]);

        // Attempting to create an Exam under locked year should fail
        $response = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/exams', [
                'academic_year_id' => $lockedYear->id,
                'exam_type_id' => $examType->id,
                'name' => 'Locked Session Exam',
                'start_date' => '2024-07-01',
                'end_date' => '2024-07-05',
                'status' => 'draft'
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'This academic session is locked. Modifications are only allowed in the active session.'
            ]);

        // Attempting to create a Subject under locked year should fail
        $response = $this->actingAs($this->schoolAdmin)
            ->postJson('/api/subjects', [
                'academic_year_id' => $lockedYear->id,
                'class_id' => $this->class->id,
                'name' => 'Locked Subject',
                'code' => 'LCK-10',
                'status' => 'active'
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'This academic session is locked. Modifications are only allowed in the active session.'
            ]);
    }
}
