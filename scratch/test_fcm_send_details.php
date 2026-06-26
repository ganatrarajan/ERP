<?php

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\FcmService;
use App\Models\Homework;
use App\Models\StudentDeviceToken;
use Illuminate\Support\Facades\Http;

$homeworkId = 3;
$hw = Homework::findOrFail($homeworkId);

echo "=== DIAGNOSING FCM SEND DETAILS ===\n";

// Get token
$query = StudentDeviceToken::where('student_device_tokens.school_id', $hw->school_id)
    ->where('student_device_tokens.status', 1)
    ->join('student_academic_records', 'student_device_tokens.student_id', '=', 'student_academic_records.student_id')
    ->join('students', 'student_device_tokens.student_id', '=', 'students.id')
    ->where('students.status', 'active')
    ->where('students.is_delete', 0)
    ->where('student_academic_records.status', 'active')
    ->where('student_academic_records.class_id', $hw->class_id);

if ($hw->section_id) {
    $query->where('student_academic_records.section_id', $hw->section_id);
}

if ($hw->academic_year_id) {
    $query->where('student_academic_records.academic_year_id', $hw->academic_year_id);
}

$tokens = $query->pluck('student_device_tokens.firebase_token')->unique()->toArray();

if (count($tokens) === 0) {
    echo "No matching active tokens found.\n";
    exit(1);
}

$token = $tokens[0];
echo "Testing token: " . substr($token, 0, 30) . "...\n";

// Let's manually run the steps of FcmService and output the intermediate values and responses

try {
    // 1. Get credentials
    $configPath = config('firebase.credentials');
    if (!$configPath || !file_exists($configPath)) {
        $configPath = storage_path('firebase/firebase-service-account.json');
    }
    if (!file_exists($configPath)) {
        $configPath = storage_path('firebase/eduvora-erp-firebase-adminsdk-fbsvc-95c4f76254.json');
    }

    echo "Using credentials file: {$configPath}\n";
    $json = json_decode(file_get_contents($configPath), true);
    $projectId = $json['project_id'];
    $privateKey = $json['private_key'];
    $clientEmail = $json['client_email'];

    echo "Project ID: {$projectId}\n";
    echo "Client Email: {$clientEmail}\n";

    // 2. Generate JWT and Access Token
    echo "\nGenerating JWT and fetching Google OAuth2 Access Token...\n";
    
    $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
    $now = time();
    $payload = json_encode([
        'iss' => $clientEmail,
        'sub' => $clientEmail,
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $now + 3600,
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
    ]);

    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

    $signatureInput = $base64UrlHeader . "." . $base64UrlPayload;
    $signature = '';

    if (!openssl_sign($signatureInput, $signature, $privateKey, 'SHA256')) {
        throw new \Exception("Failed to sign JWT with private key.");
    }

    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    $jwt = $signatureInput . "." . $base64UrlSignature;

    $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt,
    ]);

    if (!$response->successful()) {
        echo "✗ Google OAuth2 Token Request Failed!\n";
        echo "Response Status: " . $response->status() . "\n";
        echo "Response Body: " . $response->body() . "\n";
        exit(1);
    }

    $accessToken = $response->json('access_token');
    echo "✓ Access Token successfully generated: " . substr($accessToken, 0, 15) . "...\n";

    // 3. Send Notification to FCM
    $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
    
    $payloadData = [
        'message' => [
            'token' => $token,
            'notification' => [
                'title' => "Test Homework notification",
                'body' => "This is a diagnostic test.",
            ],
            'data' => [
                'type' => 'homework',
                'id' => (string)$hw->id,
            ],
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'sound' => 'default',
                    'channel_id' => 'high_importance_channel',
                ],
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                        'badge' => 1,
                    ],
                ],
            ],
        ]
    ];

    echo "\nSending HTTP Request to FCM API: {$url}...\n";
    $fcmResponse = Http::withToken($accessToken)->post($url, $payloadData);

    echo "FCM API Response Status: " . $fcmResponse->status() . "\n";
    echo "FCM API Response Body: " . $fcmResponse->body() . "\n";

    if ($fcmResponse->successful()) {
        echo "✓ FCM push notification delivered successfully!\n";
    } else {
        echo "✗ FCM push notification delivery failed.\n";
    }

} catch (\Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
echo "=============================================\n";
