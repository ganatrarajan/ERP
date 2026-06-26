<?php

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\StudentDeviceToken;
use App\Models\StudentAcademicRecord;
use App\Models\Student;
use App\Models\Homework;
use App\Models\Notice;

echo "=== REGISTERED DEVICE TOKENS ===\n";
$tokens = StudentDeviceToken::all();
if ($tokens->isEmpty()) {
    echo "No device tokens found in 'student_device_tokens' table.\n";
} else {
    foreach ($tokens as $token) {
        echo "ID: {$token->id} | School: {$token->school_id} | Student: {$token->student_id} | Device: {$token->device_name} ({$token->device_type}) | Token: " . substr($token->firebase_token, 0, 30) . "... | Status: {$token->status} | Last Login: {$token->last_login_at}\n";
        
        $student = Student::find($token->student_id);
        if ($student) {
            echo "  -> Student: {$student->first_name} {$student->last_name} | Active Status: {$student->status} | Deleted: {$student->is_delete}\n";
        }
        $records = StudentAcademicRecord::where('student_id', $token->student_id)->get();
        foreach ($records as $record) {
            echo "  -> Academic Record: Year ID: {$record->academic_year_id} | Class ID: {$record->class_id} | Section ID: {$record->section_id} | Record Status: {$record->status}\n";
        }
    }
}

echo "\n=== LATEST HOMEWORKS ===\n";
$homeworks = Homework::orderBy('id', 'desc')->take(3)->get();
foreach ($homeworks as $hw) {
    echo "ID: {$hw->id} | School: {$hw->school_id} | Class: {$hw->class_id} | Section: {$hw->section_id} | Year: {$hw->academic_year_id} | Title: {$hw->title} | Created At: {$hw->created_at}\n";
}

echo "\n=== LATEST NOTICES ===\n";
$notices = Notice::orderBy('id', 'desc')->take(3)->get();
foreach ($notices as $not) {
    echo "ID: {$not->id} | School: {$not->school_id} | Class: {$not->class_id} | Section: {$not->section_id} | Target: {$not->target_type} | Title: {$not->title} | Created At: {$not->created_at}\n";
}
