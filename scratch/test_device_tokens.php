<?php

use App\Models\Student;
use App\Models\StudentDeviceToken;
use App\Models\School;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Helper to print test results
function assertTest($description, $condition) {
    if ($condition) {
        echo "✓ PASS: $description\n";
    } else {
        echo "✗ FAIL: $description\n";
        exit(1);
    }
}

// 1. Get or create dummy school & student
$school = School::first();
if (!$school) {
    echo "No school found to test.\n";
    exit(1);
}

// Create a unique student to avoid interfering with existing data
$admissionNo = 'TEST' . rand(1000, 9999);
$student = Student::create([
    'school_id' => $school->id,
    'admission_no' => $admissionNo,
    'first_name' => 'Test',
    'last_name' => 'Student',
    'gender' => 'male',
    'date_of_birth' => '2010-01-01',
    'admission_date' => '2026-06-01',
    'status' => 'active',
    'password' => Hash::make('password123'),
    'password_changed' => 1,
]);

assertTest('Student created successfully', $student instanceof Student);

// Generate token
$token = $student->createToken('mobile-api', [
    "school:{$school->id}",
    "academic_year:1"
])->plainTextToken;

assertTest('Sanctum token generated', !empty($token));

// 2. Perform register-device test via simulation
$controller = new \App\Http\Controllers\Api\StudentDeviceTokenController();

// Simulate request
$request = \Illuminate\Http\Request::create('/api/mobile/register-device', 'POST', [
    'firebase_token' => 'test_fcm_token_123',
    'device_type' => 'android',
    'device_name' => 'Google Pixel 8',
    'app_version' => '1.0.0',
]);

// Authenticate request as student
$request->setUserResolver(function () use ($student) {
    return $student;
});
$request->attributes->add([
    'school_id' => $school->id,
]);

$response = $controller->registerDevice($request);
assertTest('Register device request returned 200 status', $response->status() === 200);

$data = json_decode($response->getContent(), true);
assertTest('Response indicates success', $data['success'] === true);
assertTest('Device token saved in database', StudentDeviceToken::where('firebase_token', 'test_fcm_token_123')->exists());

$savedToken = StudentDeviceToken::where('firebase_token', 'test_fcm_token_123')->first();
assertTest('Student ID matched correctly', (int)$savedToken->student_id === (int)$student->id);
assertTest('School ID matched correctly', (int)$savedToken->school_id === (int)$school->id);
assertTest('Status is Active (1)', (int)$savedToken->status === 1);

// 3. Registering the same token with a different name (checks upsert/update rules)
$requestUpdate = \Illuminate\Http\Request::create('/api/mobile/register-device', 'POST', [
    'firebase_token' => 'test_fcm_token_123',
    'device_type' => 'android',
    'device_name' => 'Google Pixel 8 Pro',
    'app_version' => '1.0.1',
]);
$requestUpdate->setUserResolver(function () use ($student) {
    return $student;
});
$requestUpdate->attributes->add([
    'school_id' => $school->id,
]);

$responseUpdate = $controller->registerDevice($requestUpdate);
assertTest('Update device request returned 200 status', $responseUpdate->status() === 200);

$savedToken->refresh();
assertTest('Device name updated correctly', $savedToken->device_name === 'Google Pixel 8 Pro');
assertTest('App version updated correctly', $savedToken->app_version === '1.0.1');
assertTest('Status remains Active (1)', (int)$savedToken->status === 1);

// 3.5. Registering a completely new token for the same student (should delete the old token)
$requestNewToken = \Illuminate\Http\Request::create('/api/mobile/register-device', 'POST', [
    'firebase_token' => 'new_different_fcm_token_456',
    'device_type' => 'android',
    'device_name' => 'Google Pixel 9',
    'app_version' => '1.0.2',
]);
$requestNewToken->setUserResolver(function () use ($student) {
    return $student;
});
$requestNewToken->attributes->add([
    'school_id' => $school->id,
]);

$responseNewToken = $controller->registerDevice($requestNewToken);
assertTest('Register new device token returned 200 status', $responseNewToken->status() === 200);
assertTest('Old device token test_fcm_token_123 was deleted', !StudentDeviceToken::where('firebase_token', 'test_fcm_token_123')->exists());
assertTest('New device token new_different_fcm_token_456 exists', StudentDeviceToken::where('firebase_token', 'new_different_fcm_token_456')->exists());

// Update savedToken reference for logout test and cleanup
$savedToken = StudentDeviceToken::where('firebase_token', 'new_different_fcm_token_456')->first();

// 4. Logout test (deactivates token and revokes Sanctum token)
$accessToken = PersonalAccessToken::findToken($token);
$student->withAccessToken($accessToken);

$requestLogout = \Illuminate\Http\Request::create('/api/mobile/logout', 'POST', [
    'firebase_token' => 'new_different_fcm_token_456',
]);
$requestLogout->setUserResolver(function () use ($student) {
    return $student;
});

$responseLogout = $controller->logout($requestLogout);
assertTest('Logout response returned 200 status', $responseLogout->status() === 200);

$savedToken->refresh();
assertTest('Token status updated to Inactive (0)', (int)$savedToken->status === 0);
assertTest('Sanctum token was revoked (deleted)', PersonalAccessToken::findToken($token) === null);

// Clean up
$savedToken->delete();
$student->delete();

echo "\nAll device token tests passed successfully!\n";
