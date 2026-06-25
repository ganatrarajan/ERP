<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\AcademicYear;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class MobileAuthController extends Controller
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
     * POST /api/mobile/school/verify
     */
    public function verifySchool(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'school_code' => 'required|string|size:5'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors());
        }

        $school = School::where('school_code', $request->school_code)
            ->where('status', 'active')
            ->first();

        if (!$school) {
            return $this->errorResponse('Invalid school code or school is inactive.', 404);
        }

        $mobileAcademicYearId = $school->mobile_academic_year_id;
        if (!$mobileAcademicYearId) {
            $mobileAcademicYearId = AcademicYear::where('school_id', $school->id)
                ->where('is_current', true)
                ->where('is_delete', 0)
                ->value('id') ?? AcademicYear::where('school_id', $school->id)
                ->where('is_delete', 0)
                ->value('id');
        }

        $academicYear = AcademicYear::find($mobileAcademicYearId);

        return response()->json([
            'success' => true,
            'school_id' => $school->id,
            'school_name' => $school->name,
            'school_logo' => $school->logo ? url($school->logo) : null,
            'school_address' => $school->address,
            'school_phone' => $school->phone,
            'mobile_academic_year_id' => $mobileAcademicYearId,
            'mobile_academic_year_title' => $academicYear ? $academicYear->title : null,
        ]);
    }

    /**
     * GET /api/mobile/academic-years/{school_code}
     */
    public function academicYears($school_code): JsonResponse
    {
        $school = School::where('school_code', $school_code)
            ->where('status', 'active')
            ->first();

        if (!$school) {
            return $this->errorResponse('Invalid school code.', 404);
        }

        $years = AcademicYear::where('school_id', $school->id)
            ->where('is_delete', 0)
            ->select('id', 'title as name', 'is_current')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($years);
    }

    /**
     * POST /api/mobile/login
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'school_code' => 'required|string|size:5',
            'academic_year_id' => 'sometimes|exists:academic_years,id',
            'admission_no' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors());
        }

        $school = School::where('school_code', $request->school_code)
            ->where('status', 'active')
            ->first();

        if (!$school) {
            return $this->errorResponse('Invalid school code.', 404);
        }

        $mobileAcademicYearId = $request->academic_year_id;
        if (!$mobileAcademicYearId) {
            $mobileAcademicYearId = $school->mobile_academic_year_id;
        }
        if (!$mobileAcademicYearId) {
            $mobileAcademicYearId = AcademicYear::where('school_id', $school->id)
                ->where('is_current', true)
                ->where('is_delete', 0)
                ->value('id') ?? AcademicYear::where('school_id', $school->id)
                ->where('is_delete', 0)
                ->value('id');
        }

        // Validate academic year belongs to school
        $academicYear = AcademicYear::where('id', $mobileAcademicYearId)
            ->where('school_id', $school->id)
            ->where('is_delete', 0)
            ->first();

        if (!$academicYear) {
            return $this->errorResponse('Invalid academic year for this school.', 400);
        }

        // Check if student belongs to school
        $student = Student::where('school_id', $school->id)
            ->where('admission_no', $request->admission_no)
            ->where('status', 'active')
            ->where('is_delete', 0)
            ->first();

        if (!$student || !Hash::check($request->password, $student->password)) {
            return $this->errorResponse('Invalid admission number or password.', 401);
        }

        // Optional: Check if student has an active academic record in this year? 
        // Typically a student must be enrolled in the chosen year.
        $hasRecord = \App\Models\StudentAcademicRecord::where('student_id', $student->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'active')
            ->exists();

        if (!$hasRecord) {
            return $this->errorResponse('Student is not enrolled in this academic year.', 403);
        }

        if ($student->password_changed === 0) {
            return response()->json([
                'force_password_change' => true,
                'student_id' => $student->id
            ], 200);
        }

        // Generate context token
        $token = $student->createToken('mobile-api', [
            "school:{$school->id}",
            "academic_year:{$academicYear->id}"
        ])->plainTextToken;

        $student->load(['parent', 'currentAcademicRecord.class', 'currentAcademicRecord.section']);
        $student->setAttribute('school_name', $school->name);
        $student->setAttribute('mobile_academic_year', $academicYear ? $academicYear->title : 'N/A');

        return $this->successResponse([
            'token' => $token,
            'student_id' => $student->id,
            'student' => $student
        ], 'Logged in successfully.');
    }
}
