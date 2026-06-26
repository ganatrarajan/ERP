<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentDeviceToken;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class StudentDeviceTokenController extends Controller
{
    protected function successResponse($data = [], $message = null, $code = 200): JsonResponse
    {
        $response = ['success' => true];
        if ($message) $response['message'] = $message;
        if (is_array($data)) {
            $response = array_merge($response, $data);
        } else {
            $response['data'] = $data;
        }
        return response()->json($response, $code);
    }

    protected function errorResponse($message, $code = 400, $errors = null): JsonResponse
    {
        $response = ['success' => false, 'message' => $message];
        if ($errors) $response['errors'] = $errors;
        return response()->json($response, $code);
    }

    /**
     * POST /api/mobile/register-device
     * Register or update student FCM device token.
     */
    public function registerDevice(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'firebase_token' => 'required|string|max:255',
            'device_type' => 'required|string|max:50',
            'device_name' => 'nullable|string|max:255',
            'app_version' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors());
        }

        $student = $request->user();
        if (!$student) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        // Retrieve school_id from middleware context or fallback to student profile
        $schoolId = $request->attributes->get('school_id') ?? $student->school_id;

        // Check if the same firebase_token already exists
        $deviceToken = StudentDeviceToken::where('firebase_token', $request->firebase_token)->first();

        if ($deviceToken) {
            // Update existing token's properties and ownership
            $deviceToken->update([
                'school_id' => $schoolId,
                'student_id' => $student->id,
                'device_type' => $request->device_type,
                'device_name' => $request->device_name,
                'app_version' => $request->app_version,
                'last_login_at' => now(),
                'status' => 1, // 1 = Active
            ]);
        } else {
            // Never overwrite existing device records, create a new one instead
            $deviceToken = StudentDeviceToken::create([
                'school_id' => $schoolId,
                'student_id' => $student->id,
                'device_type' => $request->device_type,
                'device_name' => $request->device_name,
                'app_version' => $request->app_version,
                'firebase_token' => $request->firebase_token,
                'last_login_at' => now(),
                'status' => 1, // 1 = Active
            ]);
        }

        // Delete all other device tokens for the same student and school to prevent duplicates
        StudentDeviceToken::where('student_id', $student->id)
            ->where('school_id', $schoolId)
            ->where('firebase_token', '!=', $request->firebase_token)
            ->delete();

        return $this->successResponse($deviceToken, 'Device registered successfully.');
    }

    /**
     * POST /api/mobile/logout
     * Deactivate device token and revoke Sanctum access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'firebase_token' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors());
        }

        $student = $request->user();
        if (!$student) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        // Deactivate current device token
        $deviceToken = StudentDeviceToken::where('firebase_token', $request->firebase_token)
            ->where('student_id', $student->id)
            ->first();

        if ($deviceToken) {
            $deviceToken->update([
                'status' => 0, // 0 = Inactive
                'last_login_at' => now(),
            ]);
        }

        // Revoke the current access token
        $currentAccessToken = $student->currentAccessToken();
        if ($currentAccessToken) {
            $currentAccessToken->delete();
        }

        return $this->successResponse([], 'Logged out and device deactivated successfully.');
    }
}
