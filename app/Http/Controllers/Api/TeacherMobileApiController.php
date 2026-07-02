<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDeviceToken;
use App\Models\School;
use App\Models\AcademicYear;
use App\Models\TeacherAssignment;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentAcademicRecord;
use App\Models\Attendance;
use App\Models\Homework;
use App\Models\Notice;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\ExamMark;
use App\Models\GradeScale;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TeacherMobileApiController extends Controller
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

    protected function verifyAssignment(int $teacherId, int $academicYearId, int $classId, ?int $sectionId = null, ?int $subjectId = null): bool
    {
        $query = TeacherAssignment::where('teacher_id', $teacherId)
            ->where('academic_year_id', $academicYearId)
            ->where('class_id', $classId);
            
        if ($sectionId) {
            $query->where(function ($q) use ($sectionId) {
                $q->where('section_id', $sectionId)->orWhereNull('section_id');
            });
        }
        
        if ($subjectId) {
            $query->where(function ($q) use ($subjectId) {
                $q->where('subject_id', $subjectId)->orWhereNull('subject_id');
            });
        }
        
        return $query->exists();
    }

    /**
     * POST /mobile/teacher/login
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'school_code' => 'required|string|size:5',
            'academic_year_id' => 'sometimes|exists:academic_years,id',
            'email' => 'required|email',
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

        $academicYearId = $request->academic_year_id;
        if (!$academicYearId) {
            $academicYearId = $school->mobile_academic_year_id ?? AcademicYear::where('school_id', $school->id)->where('is_current', true)->value('id') ?? AcademicYear::where('school_id', $school->id)->value('id');
        }

        $teacher = User::where('school_id', $school->id)
            ->where('email', $request->email)
            ->where('status', 'active')
            ->first();

        if (!$teacher || !Hash::check($request->password, $teacher->password)) {
            return $this->errorResponse('Invalid credentials or password.', 401);
        }

        // Verify user has 'Teacher' role
        if (!$teacher->hasRole('Teacher')) {
            return $this->errorResponse('Unauthorized. This portal is for teachers only.', 403);
        }

        $token = $teacher->createToken('mobile-api', [
            "school:{$school->id}",
            "academic_year:{$academicYearId}"
        ])->plainTextToken;

        return $this->successResponse([
            'token' => $token,
            'teacher' => $teacher->load('roles')
        ], 'Logged in successfully.');
    }

    /**
     * POST /mobile/teacher/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $teacher = $request->user();
        if ($teacher) {
            $token = $teacher->currentAccessToken();
            if ($token) {
                $token->delete();
            }
            UserDeviceToken::where('user_id', $teacher->id)->update(['status' => 0]);
        }
        return $this->successResponse([], 'Logged out successfully.');
    }

    /**
     * GET /mobile/teacher/profile
     */
    public function profile(Request $request): JsonResponse
    {
        $teacher = $request->user();
        return $this->successResponse(['teacher' => $teacher]);
    }

    /**
     * POST /mobile/teacher/profile/update
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $validator = Validator::make($request->all(), [
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'mobile' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors());
        }

        $teacher->update($request->only([
            'emergency_contact_name',
            'emergency_contact_mobile',
            'address',
            'mobile',
        ]));

        return $this->successResponse(['teacher' => $teacher], 'Profile updated successfully.');
    }

    /**
     * POST /mobile/teacher/change-password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:6',
            'confirm_password' => 'required|string|same:new_password',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors());
        }

        if (!Hash::check($request->old_password, $teacher->password)) {
            return $this->errorResponse('The old password does not match.', 422, [
                'old_password' => ['The old password does not match.']
            ]);
        }

        $teacher->update([
            'password' => Hash::make($request->new_password)
        ]);

        return $this->successResponse([], 'Password changed successfully.');
    }

    /**
     * POST /mobile/teacher/fcm-token
     */
    public function fcmToken(Request $request): JsonResponse
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

        $teacher = $request->user();
        $schoolId = $request->attributes->get('school_id') ?? $teacher->school_id;

        $deviceToken = UserDeviceToken::where('firebase_token', $request->firebase_token)->first();

        if ($deviceToken) {
            $deviceToken->update([
                'school_id' => $schoolId,
                'user_id' => $teacher->id,
                'device_type' => $request->device_type,
                'device_name' => $request->device_name,
                'app_version' => $request->app_version,
                'last_login_at' => now(),
                'status' => 1,
            ]);
        } else {
            $deviceToken = UserDeviceToken::create([
                'school_id' => $schoolId,
                'user_id' => $teacher->id,
                'device_type' => $request->device_type,
                'device_name' => $request->device_name,
                'app_version' => $request->app_version,
                'firebase_token' => $request->firebase_token,
                'last_login_at' => now(),
                'status' => 1,
            ]);
        }

        UserDeviceToken::where('user_id', $teacher->id)
            ->where('school_id', $schoolId)
            ->where('firebase_token', '!=', $request->firebase_token)
            ->delete();

        return $this->successResponse($deviceToken, 'Device registered successfully.');
    }

    /**
     * GET /mobile/teacher/dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $academicYearId = $request->attributes->get('academic_year_id');
        $today = Carbon::today()->format('Y-m-d');

        // Assignments
        $assigned = TeacherAssignment::with(['class', 'section', 'subject'])
            ->where('teacher_id', $teacher->id)
            ->where('academic_year_id', $academicYearId)
            ->get();

        // Count of classes assigned
        $classCount = $assigned->unique(function ($item) {
            return $item->class_id . '-' . $item->section_id;
        })->count();

        // Today's attendance summary (number of sections marked today)
        $markedAttendanceCount = Attendance::where('academic_year_id', $academicYearId)
            ->where('attendance_date', $today)
            ->whereIn('class_id', $assigned->pluck('class_id'))
            ->whereIn('section_id', $assigned->pluck('section_id')->filter())
            ->where('is_delete', 0)
            ->distinct('section_id')
            ->count();

        // Pending homework count
        $pendingHomeworkCount = Homework::where('academic_year_id', $academicYearId)
            ->where('school_id', $teacher->school_id)
            ->where('is_delete', 0)
            ->where('submission_date', '>=', $today)
            ->where('created_by', $teacher->id)
            ->count();

        // Recent notices (visible to teachers / school)
        $notices = Notice::with('creator')
            ->where('school_id', $teacher->school_id)
            ->where('is_delete', 0)
            ->where(function ($q) {
                $q->where('target_type', 'Entire School')
                  ->orWhere('target_type', 'Class Wise')
                  ->orWhere('target_type', 'Section Wise');
            })
            ->orderBy('notice_date', 'desc')
            ->take(5)
            ->get();

        return $this->successResponse([
            'assigned_classes_count' => $classCount,
            'today_attendance_marked_sections' => $markedAttendanceCount,
            'pending_homework_count' => $pendingHomeworkCount,
            'recent_notices' => $notices
        ]);
    }

    /**
     * GET /mobile/teacher/assignments
     */
    public function assignments(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $academicYearId = $request->attributes->get('academic_year_id');
        $assignments = TeacherAssignment::with(['class', 'section', 'subject'])
            ->where('teacher_id', $teacher->id)
            ->where('academic_year_id', $academicYearId)
            ->get()
            ->map(function ($assignment) use ($academicYearId) {
                // Count active students in this class and section
                $studentCount = StudentAcademicRecord::where('class_id', $assignment->class_id)
                    ->where('section_id', $assignment->section_id)
                    ->where('academic_year_id', $academicYearId)
                    ->whereHas('student', function ($q) {
                        $q->where('status', 'active')->where('is_delete', 0);
                    })
                    ->count();
                
                $assignment->total_students = $studentCount;
                return $assignment;
            });
        return $this->successResponse(['assignments' => $assignments]);
    }

    /**
     * GET /mobile/teacher/attendance/classes
     */
    public function attendanceClasses(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $academicYearId = $request->attributes->get('academic_year_id');
        $classes = TeacherAssignment::with(['class', 'section'])
            ->where('teacher_id', $teacher->id)
            ->where('academic_year_id', $academicYearId)
            ->get()
            ->unique(function ($item) {
                return $item->class_id . '-' . $item->section_id;
            })
            ->values();
        return $this->successResponse(['classes' => $classes]);
    }

    /**
     * GET /mobile/teacher/attendance/students
     */
    public function attendanceStudents(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'class_id' => 'required|integer|exists:classes,id',
            'section_id' => 'required|integer|exists:sections,id',
            'attendance_date' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors());
        }

        $teacher = $request->user();
        $academicYearId = $request->attributes->get('academic_year_id');

        if (!$this->verifyAssignment($teacher->id, $academicYearId, $request->class_id, $request->section_id)) {
            return $this->errorResponse('Unauthorized. You are not assigned to this class and section.', 403);
        }

        $records = StudentAcademicRecord::with(['student'])
            ->where('academic_year_id', $academicYearId)
            ->where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->where('status', 'active')
            ->whereHas('student', function ($q) {
                $q->where('is_delete', 0)->where('status', 'active');
            })->get();

        $existingAttendances = Attendance::where('academic_year_id', $academicYearId)
            ->where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->where('attendance_date', $request->attendance_date)
            ->where('is_delete', 0)
            ->get()
            ->keyBy('student_id');

        $students = $records->map(function ($record) use ($existingAttendances, $teacher, $request, $academicYearId) {
            $student = $record->student;
            $holiday = \App\Services\HolidayService::isHolidayForStudent($teacher->school_id, $student->id, $request->attendance_date, $request->class_id, $request->section_id, $academicYearId);
            $att = $existingAttendances->get($student->id);
            return [
                'student_id' => $student->id,
                'roll_no' => $record->roll_no,
                'name' => $student->first_name . ' ' . $student->last_name,
                'admission_no' => $student->admission_no,
                'gender' => $student->gender,
                'attendance_status' => $holiday ? 'Holiday' : ($att ? $att->status : 'Present'),
                'remarks' => $holiday ? 'Holiday: ' . $holiday->title : ($att ? $att->remarks : ''),
                'attendance_id' => $att ? $att->id : null,
                'is_holiday' => !!$holiday,
            ];
        })->sortBy('roll_no')->values();

        return $this->successResponse(['students' => $students]);
    }

    /**
     * POST /mobile/teacher/attendance/save
     */
    public function submitAttendance(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'class_id' => 'required|integer|exists:classes,id',
            'section_id' => 'required|integer|exists:sections,id',
            'attendance_date' => 'required|date_format:Y-m-d',
            'students' => 'required|array|min:1',
            'students.*.student_id' => 'required|integer|exists:students,id',
            'students.*.status' => 'required|string|in:Present,Absent,Late,Half Day,Leave,Holiday',
            'students.*.remarks' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors());
        }

        $teacher = $request->user();
        $academicYearId = $request->attributes->get('academic_year_id');

        if (!$this->verifyAssignment($teacher->id, $academicYearId, $request->class_id, $request->section_id)) {
            return $this->errorResponse('Unauthorized. You are not assigned to this class and section.', 403);
        }

        $service = resolve(\App\Services\AttendanceService::class);
        $service->markAttendance(
            array_merge($request->only(['class_id', 'section_id', 'attendance_date', 'students']), [
                'academic_year_id' => $academicYearId
            ]),
            $teacher->school_id,
            $teacher->id
        );

        return $this->successResponse([], 'Attendance saved successfully.');
    }

    /**
     * POST /mobile/teacher/attendance/update
     */
    public function updateAttendance(Request $request): JsonResponse
    {
        return $this->submitAttendance($request);
    }

    /**
     * GET /mobile/teacher/attendance/history
     */
    public function attendanceHistory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'class_id' => 'required|integer|exists:classes,id',
            'section_id' => 'required|integer|exists:sections,id',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors());
        }

        $teacher = $request->user();
        $academicYearId = $request->attributes->get('academic_year_id');

        if (!$this->verifyAssignment($teacher->id, $academicYearId, $request->class_id, $request->section_id)) {
            return $this->errorResponse('Unauthorized.', 403);
        }

        $history = Attendance::where('academic_year_id', $academicYearId)
            ->where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->whereBetween('attendance_date', [$request->start_date, $request->end_date])
            ->where('is_delete', 0)
            ->orderBy('attendance_date', 'desc')
            ->get();

        return $this->successResponse(['history' => $history]);
    }

    /**
     * GET /mobile/teacher/attendance/monthly
     */
    public function monthlyAttendance(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'class_id' => 'required|integer|exists:classes,id',
            'section_id' => 'required|integer|exists:sections,id',
            'month' => 'required|date_format:Y-m',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors());
        }

        $teacher = $request->user();
        $academicYearId = $request->attributes->get('academic_year_id');

        if (!$this->verifyAssignment($teacher->id, $academicYearId, $request->class_id, $request->section_id)) {
            return $this->errorResponse('Unauthorized.', 403);
        }

        $carbonMonth = Carbon::parse($request->month . '-01');
        $daysInMonth = $carbonMonth->daysInMonth;
        $startDate = $carbonMonth->copy()->startOfMonth()->format('Y-m-d');
        $endDate = $carbonMonth->copy()->endOfMonth()->format('Y-m-d');

        $records = StudentAcademicRecord::with(['student'])
            ->where('academic_year_id', $academicYearId)
            ->where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->where('status', 'active')
            ->whereHas('student', function ($q) {
                $q->where('is_delete', 0);
            })->get();

        $attendances = Attendance::where('academic_year_id', $academicYearId)
            ->where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->where('is_delete', 0)
            ->get()
            ->groupBy('student_id');

        $matrix = $records->map(function ($record) use ($attendances, $daysInMonth, $request, $teacher, $academicYearId) {
            $student = $record->student;
            $studentAttendances = $attendances->get($student->id) ?? collect();

            $daysData = [];
            $present = 0;
            $absent = 0;
            $late = 0;
            $halfDay = 0;
            $leave = 0;
            $holidayCount = 0;

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $dateStr = sprintf('%s-%02d', $request->month, $day);
                $att = $studentAttendances->firstWhere('attendance_date', $dateStr);

                $status = $att ? $att->status : '-';
                if ($status === '-') {
                    $holiday = \App\Services\HolidayService::isHolidayForStudent($teacher->school_id, $student->id, $dateStr, $request->class_id, $request->section_id, $academicYearId);
                    if ($holiday) {
                        $status = 'Holiday';
                    }
                }
                $daysData[$day] = $status;

                if ($status === 'Present') $present++;
                elseif ($status === 'Absent') $absent++;
                elseif ($status === 'Late') $late++;
                elseif ($status === 'Half Day') $halfDay++;
                elseif ($status === 'Leave') $leave++;
                elseif ($status === 'Holiday') $holidayCount++;
            }

            return [
                'student_id' => $student->id,
                'roll_no' => $record->roll_no,
                'name' => $student->first_name . ' ' . $student->last_name,
                'days' => $daysData,
                'stats' => [
                    'Present' => $present,
                    'Absent' => $absent,
                    'Late' => $late,
                    'Half Day' => $halfDay,
                    'Leave' => $leave,
                    'Holiday' => $holidayCount,
                ]
            ];
        })->sortBy('roll_no')->values();

        return $this->successResponse([
            'days_in_month' => $daysInMonth,
            'matrix' => $matrix
        ]);
    }

    /**
     * GET /mobile/teacher/homeworks
     */
    public function homeworks(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $academicYearId = $request->attributes->get('academic_year_id');

        $query = Homework::with(['class', 'section', 'subject'])
            ->where('academic_year_id', $academicYearId)
            ->where('school_id', $teacher->school_id)
            ->where('is_delete', 0);

        // Filter by creator (own homeworks)
        $query->where('created_by', $teacher->id);

        if ($request->filled('class_id')) $query->where('class_id', $request->class_id);
        if ($request->filled('section_id')) $query->where('section_id', $request->section_id);

        $homeworks = $query->orderBy('id', 'desc')->paginate(15);
        return $this->successResponse($homeworks);
    }

    /**
     * GET /mobile/teacher/homeworks/{id}
     */
    public function homeworkDetails(Request $request, $id): JsonResponse
    {
        $teacher = $request->user();
        $homework = Homework::with(['class', 'section', 'subject'])->findOrFail($id);

        if ($homework->school_id !== $teacher->school_id || $homework->is_delete) {
            return $this->errorResponse('Homework not found.', 404);
        }

        return $this->successResponse(['homework' => $homework]);
    }

    /**
     * POST /mobile/teacher/homeworks
     */
    public function createHomework(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'class_id' => 'required|integer|exists:classes,id',
            'section_id' => 'required|integer|exists:sections,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'submission_date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'max_marks' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors());
        }

        $teacher = $request->user();
        $academicYearId = $request->attributes->get('academic_year_id');

        if (!$this->verifyAssignment($teacher->id, $academicYearId, $request->class_id, $request->section_id, $request->subject_id)) {
            return $this->errorResponse('Unauthorized. You are not assigned to this class, section, and subject.', 403);
        }

        $homework = Homework::create([
            'school_id' => $teacher->school_id,
            'academic_year_id' => $academicYearId,
            'class_id' => $request->class_id,
            'section_id' => $request->section_id,
            'subject_id' => $request->subject_id,
            'title' => $request->title,
            'description' => $request->description,
            'submission_date' => $request->submission_date,
            'max_marks' => $request->max_marks,
            'status' => 'active',
            'created_by' => $teacher->id,
        ]);

        return $this->successResponse(['homework' => $homework], 'Homework created successfully.', 201);
    }

    /**
     * PUT /mobile/teacher/homeworks/{id}
     */
    public function updateHomework(Request $request, $id): JsonResponse
    {
        $teacher = $request->user();
        $homework = Homework::findOrFail($id);

        if ($homework->school_id !== $teacher->school_id || $homework->created_by !== $teacher->id) {
            return $this->errorResponse('Unauthorized to update this homework.', 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'submission_date' => 'sometimes|required|date_format:Y-m-d|after_or_equal:today',
            'max_marks' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors());
        }

        $homework->update($request->only(['title', 'description', 'submission_date', 'max_marks']));

        return $this->successResponse(['homework' => $homework], 'Homework updated successfully.');
    }

    /**
     * DELETE /mobile/teacher/homeworks/{id}
     */
    public function deleteHomework(Request $request, $id): JsonResponse
    {
        $teacher = $request->user();
        $homework = Homework::findOrFail($id);

        if ($homework->school_id !== $teacher->school_id || $homework->created_by !== $teacher->id) {
            return $this->errorResponse('Unauthorized to delete this homework.', 403);
        }

        $homework->update(['is_delete' => 1]);

        return $this->successResponse([], 'Homework deleted successfully.');
    }

    /**
     * GET /mobile/teacher/notices
     */
    public function notices(Request $request): JsonResponse
    {
        $teacher = $request->user();
        
        $query = Notice::with('creator')
            ->where('school_id', $teacher->school_id)
            ->where('is_delete', 0);

        // Notices visible to entire school
        $query->where(function ($q) {
            $q->where('target_type', 'Entire School')
              ->orWhere('target_type', 'Class Wise')
              ->orWhere('target_type', 'Section Wise');
        });

        $notices = $query->orderBy('notice_date', 'desc')->orderBy('id', 'desc')->paginate(15);
        return $this->successResponse($notices);
    }

    /**
     * GET /mobile/teacher/notices/{id}
     */
    public function noticeDetails(Request $request, $id): JsonResponse
    {
        $teacher = $request->user();
        $notice = Notice::with('creator')->findOrFail($id);

        if ($notice->school_id !== $teacher->school_id || $notice->is_delete) {
            return $this->errorResponse('Notice not found.', 404);
        }

        return $this->successResponse(['notice' => $notice]);
    }

    /**
     * GET /mobile/teacher/exams
     */
    public function exams(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $academicYearId = $request->attributes->get('academic_year_id');

        $query = Exam::where('academic_year_id', $academicYearId)
            ->where('school_id', $teacher->school_id)
            ->where('status', 'published')
            ->where('is_delete', 0);

        if ($request->filled('class_id') && $request->filled('section_id') && $request->filled('subject_id')) {
            $query->whereHas('schedules', function ($q) use ($request) {
                $q->where('class_id', $request->class_id)
                  ->where('section_id', $request->section_id)
                  ->where('subject_id', $request->subject_id)
                  ->where('is_delete', 0);
            });
        }

        $exams = $query->get();

        return $this->successResponse(['exams' => $exams]);
    }

    /**
     * GET /mobile/teacher/exams/{exam}/marks
     */
    public function examMarks(Request $request, $examId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'class_id' => 'required|integer|exists:classes,id',
            'section_id' => 'required|integer|exists:sections,id',
            'subject_id' => 'required|integer|exists:subjects,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors());
        }

        $teacher = $request->user();
        $academicYearId = $request->attributes->get('academic_year_id');

        if (!$this->verifyAssignment($teacher->id, $academicYearId, $request->class_id, $request->section_id, $request->subject_id)) {
            return $this->errorResponse('Unauthorized. You are not assigned to this class, section, and subject.', 403);
        }

        $schedule = ExamSchedule::where('exam_id', $examId)
            ->where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->where('subject_id', $request->subject_id)
            ->where('is_delete', 0)
            ->first();

        if (!$schedule) {
            return $this->errorResponse('No exam schedule found for this configuration.', 422);
        }

        $query = StudentAcademicRecord::with(['student'])
            ->where('academic_year_id', $academicYearId)
            ->where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->where('status', 'active')
            ->whereHas('student', function ($q) {
                $q->where('is_delete', 0)->where('status', 'active');
            });

        $subject = Subject::find($request->subject_id);
        if ($subject && ($subject->is_optional == 1 || $subject->is_optional == true)) {
            $query->whereIn('student_id', function ($subQuery) use ($request, $academicYearId) {
                $subQuery->select('student_id')
                    ->from('student_optional_subjects')
                    ->where('subject_id', $request->subject_id);
                if ($academicYearId) {
                    $subQuery->where('academic_year_id', $academicYearId);
                }
            });
        }

        $records = $query->get();

        $existingMarks = ExamMark::where('exam_id', $examId)
            ->where('exam_schedule_id', $schedule->id)
            ->where('subject_id', $request->subject_id)
            ->get()
            ->keyBy('student_id');

        $grades = GradeScale::where('school_id', $teacher->school_id)
            ->where('status', 'active')
            ->get();

        $students = $records->map(function ($record) use ($existingMarks) {
            $student = $record->student;
            $mark = $existingMarks->get($student->id);
            return [
                'student_id' => $student->id,
                'roll_no' => $record->roll_no,
                'name' => $student->first_name . ' ' . $student->last_name,
                'admission_no' => $student->admission_no,
                'marks_obtained' => $mark ? $mark->marks_obtained : null,
                'is_absent' => $mark ? (bool)$mark->is_absent : false,
                'grade_id' => $mark ? $mark->grade_id : null,
                'remarks' => $mark ? $mark->remarks : '',
                'mark_id' => $mark ? $mark->id : null,
            ];
        })->sortBy('roll_no')->values();

        return $this->successResponse([
            'schedule' => $schedule,
            'grades' => $grades,
            'students' => $students
        ]);
    }

    /**
     * POST /mobile/teacher/exams/marks/save
     */
    public function saveExamMarks(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|exists:exams,id',
            'exam_schedule_id' => 'required|exists:exam_schedules,id',
            'subject_id' => 'required|exists:subjects,id',
            'marks' => 'required|array',
            'marks.*.student_id' => 'required|exists:students,id',
            'marks.*.marks_obtained' => 'nullable|numeric|min:0',
            'marks.*.is_absent' => 'nullable|boolean',
            'marks.*.grade_id' => 'nullable|exists:grade_scales,id',
            'marks.*.remarks' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors());
        }

        $teacher = $request->user();
        $academicYearId = $request->attributes->get('academic_year_id');

        $schedule = ExamSchedule::findOrFail($request->exam_schedule_id);

        if (!$this->verifyAssignment($teacher->id, $academicYearId, $schedule->class_id, $schedule->section_id, $request->subject_id)) {
            return $this->errorResponse('Unauthorized.', 403);
        }

        // Validate max marks
        foreach ($request->marks as $markData) {
            if (isset($markData['marks_obtained']) && $markData['marks_obtained'] > $schedule->max_marks) {
                return $this->errorResponse("Marks obtained cannot exceed maximum marks ({$schedule->max_marks}) of the subject.", 422);
            }
        }

        $savedMarks = DB::transaction(function () use ($teacher, $academicYearId, $request, $schedule) {
            $results = [];
            foreach ($request->marks as $markData) {
                $isAbsent = isset($markData['is_absent']) ? (bool)$markData['is_absent'] : false;
                $gradeId = $markData['grade_id'] ?? null;
                
                // Auto Grade calculation if needed
                if (!$isAbsent && isset($markData['marks_obtained'])) {
                    $percentage = ($markData['marks_obtained'] / $schedule->max_marks) * 100;
                    $matchedGrade = GradeScale::where('school_id', $teacher->school_id)
                        ->where('status', 'active')
                        ->where('min_percentage', '<=', $percentage)
                        ->orderBy('min_percentage', 'desc')
                        ->first();
                    if ($matchedGrade) {
                        $gradeId = $matchedGrade->id;
                    }
                }

                $record = ExamMark::updateOrCreate(
                    [
                        'school_id' => $teacher->school_id,
                        'academic_year_id' => $academicYearId,
                        'exam_id' => $request->exam_id,
                        'exam_schedule_id' => $schedule->id,
                        'subject_id' => $request->subject_id,
                        'student_id' => $markData['student_id'],
                    ],
                    [
                        'marks_obtained' => $isAbsent ? null : ($markData['marks_obtained'] ?? null),
                        'is_absent' => $isAbsent ? 1 : 0,
                        'grade_id' => $gradeId,
                        'remarks' => $markData['remarks'] ?? '',
                    ]
                );
                $results[] = $record;
            }
            return $results;
        });

        return $this->successResponse(['saved_marks' => $savedMarks], 'Exam marks saved successfully.');
    }

    /**
     * GET /mobile/teacher/documents
     */
    public function documents(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $documents = $teacher->documents()->get();
        return $this->successResponse(['documents' => $documents]);
    }

    /**
     * GET /mobile/teacher/exams/{exam}/subjects
     */
    public function examSubjects(Request $request, $examId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'class_id' => 'required|integer|exists:classes,id',
            'section_id' => 'required|integer|exists:sections,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors());
        }

        $teacher = $request->user();
        $academicYearId = $request->attributes->get('academic_year_id');

        $assignedSubjectIds = TeacherAssignment::where('teacher_id', $teacher->id)
            ->where('academic_year_id', $academicYearId)
            ->where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->pluck('subject_id');

        $subjects = Subject::whereIn('id', function ($query) use ($examId, $request, $assignedSubjectIds) {
            $query->select('subject_id')
                ->from('exam_schedules')
                ->where('exam_id', $examId)
                ->where('class_id', $request->class_id)
                ->where('section_id', $request->section_id)
                ->whereIn('subject_id', $assignedSubjectIds)
                ->where('is_delete', 0);
        })->where('is_delete', 0)->get();

        return $this->successResponse(['subjects' => $subjects]);
    }
}
