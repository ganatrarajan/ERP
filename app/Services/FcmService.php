<?php

namespace App\Services;

use App\Models\StudentDeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    /**
     * Get OAuth2 Access Token for Firebase Cloud Messaging using Service Account JWT.
     */
    private function getAccessToken(): string
    {
        return cache()->remember('fcm_access_token', 3000, function () {
            // Retrieve config path or default
            $configPath = config('firebase.credentials');
            if (!$configPath || !file_exists($configPath)) {
                // Try fallback to absolute path
                $configPath = storage_path('firebase/firebase-service-account.json');
            }

            if (!file_exists($configPath)) {
                throw new \Exception("Firebase credentials file not found at: {$configPath}");
            }

            $json = json_decode(file_get_contents($configPath), true);
            if (!$json) {
                throw new \Exception("Invalid Firebase credentials JSON.");
            }

            $privateKey = $json['private_key'];
            $clientEmail = $json['client_email'];

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
                throw new \Exception("Failed to obtain OAuth2 access token: " . $response->body());
            }

            return $response->json('access_token');
        });
    }

    /**
     * Send FCM push notification to a single device token.
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        try {
            $accessToken = $this->getAccessToken();
            $configPath = config('firebase.credentials') ?: storage_path('firebase/firebase-service-account.json');
            if (!file_exists($configPath)) {
                $configPath = storage_path('firebase/eduvora-erp-firebase-adminsdk-fbsvc-95c4f76254.json');
            }
            $json = json_decode(file_get_contents($configPath), true);
            $projectId = $json['project_id'];

            $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

            // Format all custom data values as strings for FCM compliance
            $stringData = [];
            foreach ($data as $key => $value) {
                $stringData[(string)$key] = (string)$value;
            }

            $payload = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $stringData,
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

            $response = Http::withToken($accessToken)->post($url, $payload);

            // Handle expired or invalid FCM token
            if ($response->status() === 400 || $response->status() === 404 || $response->status() === 410) {
                Log::warning("FCM Token is invalid, deactivating: {$token}");
                StudentDeviceToken::where('firebase_token', $token)->update(['status' => 0]);
                return false;
            }

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("FCM Send Error to token {$token}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send FCM push notification to a specific student's registered devices.
     */
    public function sendToStudent(int $studentId, string $title, string $body, array $data = []): void
    {
        $tokens = StudentDeviceToken::where('student_device_tokens.student_id', $studentId)
            ->where('student_device_tokens.status', 1)
            ->join('students', 'student_device_tokens.student_id', '=', 'students.id')
            ->where('students.status', 'active')
            ->where('students.is_delete', 0)
            ->pluck('student_device_tokens.firebase_token')
            ->toArray();

        foreach ($tokens as $token) {
            $this->sendToToken($token, $title, $body, $data);
        }
    }

    /**
     * Send FCM push notification to all students in a specific class and/or section.
     */
    public function sendToClass(int $schoolId, int $classId, ?int $sectionId = null, string $title, string $body, array $data = [], ?int $academicYearId = null): void
    {
        $query = StudentDeviceToken::where('student_device_tokens.school_id', $schoolId)
            ->where('student_device_tokens.status', 1)
            ->join('student_academic_records', 'student_device_tokens.student_id', '=', 'student_academic_records.student_id')
            ->join('students', 'student_device_tokens.student_id', '=', 'students.id')
            ->where('students.status', 'active')
            ->where('students.is_delete', 0)
            ->where('student_academic_records.status', 'active')
            ->where('student_academic_records.class_id', $classId);

        if ($sectionId) {
            $query->where('student_academic_records.section_id', $sectionId);
        }

        if ($academicYearId) {
            $query->where('student_academic_records.academic_year_id', $academicYearId);
        } else {
            // Find current active academic year for the school
            $activeYearId = \App\Models\AcademicYear::where('school_id', $schoolId)
                ->where('is_current', true)
                ->where('is_delete', 0)
                ->value('id');
            if ($activeYearId) {
                $query->where('student_academic_records.academic_year_id', $activeYearId);
            }
        }

        $tokens = $query->pluck('student_device_tokens.firebase_token')->unique()->toArray();

        foreach ($tokens as $token) {
            $this->sendToToken($token, $title, $body, $data);
        }
    }

    /**
     * Send FCM push notification to all students in a school (Global Notice).
     */
    public function sendToSchool(int $schoolId, string $title, string $body, array $data = []): void
    {
        $tokens = StudentDeviceToken::where('student_device_tokens.school_id', $schoolId)
            ->where('student_device_tokens.status', 1)
            ->join('students', 'student_device_tokens.student_id', '=', 'students.id')
            ->where('students.status', 'active')
            ->where('students.is_delete', 0)
            ->pluck('student_device_tokens.firebase_token')
            ->toArray();

        foreach ($tokens as $token) {
            $this->sendToToken($token, $title, $body, $data);
        }
    }
}
