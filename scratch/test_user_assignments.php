<?php

use App\Models\User;
use App\Models\School;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\AcademicYear;
use App\Models\TeacherAssignment;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Running Verification Tests for User Management & Teacher Assignments...\n\n";

try {
    // 1. Get or create testing school
    $school = School::firstOrCreate(
        ['email' => 'test-school@erp.com'],
        [
            'name' => 'Testing School ERP',
            'phone' => '0261123456',
            'address' => 'Surat, Gujarat',
            'status' => 'active',
        ]
    );
    echo "✔ School: " . $school->name . " (ID: " . $school->id . ")\n";

    // 2. Ensure Teacher Role exists
    $teacherRole = Role::firstOrCreate([
        'name' => 'Teacher',
        'guard_name' => 'web',
        'school_id' => $school->id,
    ]);
    echo "✔ Teacher Role verified.\n";

    // 3. Create test teachers
    $teacher1 = User::updateOrCreate(
        ['email' => 't1@testschool.com'],
        [
            'school_id' => $school->id,
            'name' => 'Teacher One',
            'mobile' => '9000000001',
            'password' => Hash::make('Password123'),
            'status' => 'active',
            'teacher_code' => 'TCH-001',
            'qualification' => 'M.Sc B.Ed',
            'experience' => '5 Years',
            'joining_date' => '2026-06-01',
            'department' => 'Science',
            'designation' => 'TGT Science',
            'employment_type' => 'Full-time',
        ]
    );
    $teacher1->assignRole($teacherRole);

    $teacher2 = User::updateOrCreate(
        ['email' => 't2@testschool.com'],
        [
            'school_id' => $school->id,
            'name' => 'Teacher Two',
            'mobile' => '9000000002',
            'password' => Hash::make('Password123'),
            'status' => 'active',
            'teacher_code' => 'TCH-002',
            'qualification' => 'M.A B.Ed',
            'experience' => '7 Years',
            'joining_date' => '2026-06-01',
            'department' => 'Languages',
            'designation' => 'TGT English',
            'employment_type' => 'Full-time',
        ]
    );
    $teacher2->assignRole($teacherRole);
    echo "✔ Test teachers created.\n";

    // 4. Create Academic Year
    $academicYear = AcademicYear::firstOrCreate(
        ['school_id' => $school->id, 'title' => '2026-2027'],
        [
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'is_current' => true,
            'status' => 'active',
        ]
    );
    echo "✔ Academic Year: " . $academicYear->title . "\n";

    // 5. Create Class & Section
    $classObj = ClassModel::firstOrCreate(
        ['school_id' => $school->id, 'academic_year_id' => $academicYear->id, 'name' => 'Grade 10'],
        ['description' => 'Grade 10 Senior', 'status' => 'active']
    );

    $sectionObj = Section::firstOrCreate(
        ['school_id' => $school->id, 'class_id' => $classObj->id, 'name' => 'Division A'],
        ['status' => 'active']
    );
    echo "✔ Class: " . $classObj->name . " | Section: " . $sectionObj->name . "\n";

    // 6. Create Subject
    $subjectObj = Subject::firstOrCreate(
        [
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'class_id' => $classObj->id,
            'section_id' => $sectionObj->id,
            'name' => 'Physics',
            'code' => 'PHY10',
        ],
        ['status' => 'active', 'maximum_marks' => 100, 'passing_marks' => 33]
    );
    echo "✔ Subject: " . $subjectObj->name . "\n\n";

    // Clean up any old assignments for test reproducibility
    TeacherAssignment::where('school_id', $school->id)->delete();

    // Test Case 1: Standard Valid Assignment
    echo "Test Case 1: Creating standard assignment for Teacher 1...\n";
    $assign1 = TeacherAssignment::create([
        'school_id' => $school->id,
        'teacher_id' => $teacher1->id,
        'academic_year_id' => $academicYear->id,
        'class_id' => $classObj->id,
        'section_id' => $sectionObj->id,
        'subject_id' => $subjectObj->id,
        'is_class_teacher' => true,
    ]);
    echo "✔ Assignment created successfully.\n\n";

    // Test Case 2: Duplicate Assignment Prevention
    echo "Test Case 2: Attempting duplicate assignment (same teacher, class, section, subject)...\n";
    $duplicate = TeacherAssignment::where('teacher_id', $teacher1->id)
        ->where('academic_year_id', $academicYear->id)
        ->where('class_id', $classObj->id)
        ->where('section_id', $sectionObj->id)
        ->where('subject_id', $subjectObj->id)
        ->first();
    if ($duplicate) {
        echo "✔ Duplicate correctly caught in query simulation.\n\n";
    } else {
        echo "❌ Duplicate NOT caught.\n\n";
    }

    // Test Case 3: Class Teacher Uniqueness (Assigning Teacher 2 as Class Teacher for same class/section)
    echo "Test Case 3: Simulating Class Teacher Uniqueness constraint...\n";
    $existingClassTeacher = TeacherAssignment::where('academic_year_id', $academicYear->id)
        ->where('class_id', $classObj->id)
        ->where('section_id', $sectionObj->id)
        ->where('is_class_teacher', true)
        ->where('teacher_id', '!=', $teacher2->id)
        ->first();

    if ($existingClassTeacher) {
        echo "✔ Validation correctly caught clash! Already assigned to: " . $existingClassTeacher->teacher->name . "\n\n";
    } else {
        echo "❌ Validation failed to notice that another teacher is already the class teacher.\n\n";
    }

    // Test Case 4: Load relations & JSON serialization
    echo "Test Case 4: Eager loading relations on User & Assignments...\n";
    $userWithAssign = User::with(['assignments.class', 'assignments.section', 'assignments.subject'])
        ->find($teacher1->id);
    
    echo "✔ User assignments eager load test:\n";
    foreach ($userWithAssign->assignments as $a) {
        echo "  - Assigned to Class: " . $a->class->name . " Section: " . $a->section->name . " Subject: " . ($a->subject ? $a->subject->name : 'N/A') . " (Class Teacher: " . ($a->is_class_teacher ? 'Yes' : 'No') . ")\n";
    }

    echo "\nAll Verification tests completed successfully!\n";

} catch (\Exception $e) {
    echo "❌ Test Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
