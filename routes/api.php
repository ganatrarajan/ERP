<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SchoolController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ImpersonationController;
use App\Http\Controllers\Api\AcademicYearController;
use App\Http\Controllers\Api\ClassController;
use App\Http\Controllers\Api\SectionController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\PromotionController;
use App\Http\Controllers\Api\SchoolModuleController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\StudentOptionalSubjectController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\HomeworkController;
use App\Http\Controllers\Api\NoticeController;
use App\Http\Controllers\Api\HolidayController;
use App\Http\Controllers\Api\StaffAttendanceController;
use App\Http\Controllers\Api\WeekendSettingController;
use App\Http\Controllers\Api\FeeTypeController;
use App\Http\Controllers\Api\FeeStructureController;
use App\Http\Controllers\Api\StudentFeeAssignmentController;
use App\Http\Controllers\Api\FeeDiscountController;
use App\Http\Controllers\Api\FeeFineRuleController;
use App\Http\Controllers\Api\FeeCollectionController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\StudentLedgerController;
use App\Http\Controllers\Api\FeeReportController;

// Public Auth Routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

// Protected API Routes (using session auth)
Route::middleware('auth')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('/auth/academic-year', [AuthController::class, 'updateSelectedAcademicYear'])
        ->middleware('permission:academic_year.switch');

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])
        ->middleware('permission:dashboard.view');

    // Schools (CRUDS protected via controller middleware / gates)
    Route::apiResource('schools', SchoolController::class);
    Route::post('/schools/upload-logo', [SchoolController::class, 'uploadLogo']);
    Route::get('/schools/{school}/modules', [SchoolModuleController::class, 'index']);
    Route::put('/schools/{school}/modules', [SchoolModuleController::class, 'update']);
    Route::get('/modules', [SchoolModuleController::class, 'systemModules']);

    // Users
    Route::apiResource('users', UserController::class);

    // Roles & Permissions
    Route::get('/permissions', [RoleController::class, 'permissions'])
        ->middleware('permission:permission.view');
    Route::apiResource('roles', RoleController::class);

    // Impersonation
    Route::post('/impersonate/login/{school}', [ImpersonationController::class, 'impersonate']);
    Route::post('/impersonate/exit', [ImpersonationController::class, 'exitImpersonation']);

    // Password Resets (Super Admin only)
    Route::get('/password-resets', [\App\Http\Controllers\Api\PasswordResetController::class, 'index']);
    Route::post('/password-resets/{id}/action', [\App\Http\Controllers\Api\PasswordResetController::class, 'handleAction']);

    // Academics Module Secured Routes
    Route::middleware(['module:academics'])->group(function () {
        // Academic Years
        Route::get('/academic-years', [AcademicYearController::class, 'index'])->middleware('permission:academic_year.view');
        Route::post('/academic-years', [AcademicYearController::class, 'store'])->middleware('permission:academic_year.create');
        Route::get('/academic-years/{academic_year}', [AcademicYearController::class, 'show'])->middleware('permission:academic_year.view');
        Route::put('/academic-years/{academic_year}', [AcademicYearController::class, 'update'])->middleware('permission:academic_year.edit');
        Route::delete('/academic-years/{academic_year}', [AcademicYearController::class, 'destroy'])->middleware('permission:academic_year.delete');

        // Classes
        Route::get('/classes', [ClassController::class, 'index'])->middleware('permission:class.view');
        Route::post('/classes', [ClassController::class, 'store'])->middleware('permission:class.create');
        Route::get('/classes/{class}', [ClassController::class, 'show'])->middleware('permission:class.view');
        Route::put('/classes/{class}', [ClassController::class, 'update'])->middleware('permission:class.edit');
        Route::delete('/classes/{class}', [ClassController::class, 'destroy'])->middleware('permission:class.delete');

        // Sections
        Route::get('/sections', [SectionController::class, 'index'])->middleware('permission:section.view');
        Route::post('/sections', [SectionController::class, 'store'])->middleware('permission:section.create');
        Route::get('/sections/{section}', [SectionController::class, 'show'])->middleware('permission:section.view');
        Route::put('/sections/{section}', [SectionController::class, 'update'])->middleware('permission:section.edit');
        Route::delete('/sections/{section}', [SectionController::class, 'destroy'])->middleware('permission:section.delete');
    });

    // Students Module Secured Routes
    Route::middleware(['module:students'])->group(function () {
        // Students Import
        Route::get('/students/import/demo', [StudentController::class, 'demoCSV'])->middleware('permission:student.create');
        Route::post('/students/import', [StudentController::class, 'importCSV'])->middleware('permission:student.create');
        Route::post('/students/upload-photo', [StudentController::class, 'uploadPhoto'])->middleware('permission:student.create|student.edit');

        // Students
        Route::get('/students', [StudentController::class, 'index'])->middleware('permission:student.view');
        Route::post('/students', [StudentController::class, 'store'])->middleware('permission:student.create');
        Route::get('/students/{student}', [StudentController::class, 'show'])->middleware('permission:student.view');
        Route::put('/students/{student}', [StudentController::class, 'update'])->middleware('permission:student.edit');
        Route::delete('/students/{student}', [StudentController::class, 'destroy'])->middleware('permission:student.delete');

        // Student Promotions
        Route::get('/promotions/logs', [PromotionController::class, 'index'])->middleware('permission:promotion.view');
        Route::post('/promotions', [PromotionController::class, 'store'])->middleware('permission:promotion.create');
    });

    // Subjects Module Secured Routes
    Route::middleware(['module:subjects'])->group(function () {
        Route::get('/subjects', [SubjectController::class, 'index'])->middleware('permission:subject.view');
        Route::post('/subjects', [SubjectController::class, 'store'])->middleware('permission:subject.create');
        Route::get('/subjects/{subject}', [SubjectController::class, 'show'])->middleware('permission:subject.view');
        Route::put('/subjects/{subject}', [SubjectController::class, 'update'])->middleware('permission:subject.edit');
        Route::patch('/subjects/{subject}/toggle-status', [SubjectController::class, 'toggleStatus'])->middleware('permission:subject.edit');
        Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy'])->middleware('permission:subject.delete');

        // Optional Subjects Mapping
        Route::get('/optional-subjects', [StudentOptionalSubjectController::class, 'index'])->middleware('permission:subject.view');
        Route::post('/optional-subjects', [StudentOptionalSubjectController::class, 'store'])->middleware('permission:subject.edit');
    });

    // Attendance Module Secured Routes
    Route::middleware(['module:attendance'])->group(function () {
        Route::get('/attendances/students', [AttendanceController::class, 'loadStudents'])->middleware('permission:attendance.view');
        Route::post('/attendances/save', [AttendanceController::class, 'store'])->middleware('permission:attendance.create');
        Route::get('/attendances/monthly', [AttendanceController::class, 'monthlyView'])->middleware('permission:attendance.view');
        Route::get('/attendances/student-report', [AttendanceController::class, 'studentReport'])->middleware('permission:attendance.view');
        Route::get('/attendances/student-report/pdf', [AttendanceController::class, 'studentReportPdf'])->middleware('permission:attendance.view');
        Route::get('/attendances/class-report', [AttendanceController::class, 'classReport'])->middleware('permission:attendance.view');
        Route::get('/attendances/pdf', [AttendanceController::class, 'downloadPdfReport'])->middleware('permission:attendance.view');

        // Staff Attendance
        Route::get('/staff-attendances/load', [StaffAttendanceController::class, 'loadStaff'])->middleware('permission:attendance.view');
        Route::post('/staff-attendances/save', [StaffAttendanceController::class, 'store'])->middleware('permission:attendance.create');
        Route::get('/staff-attendances/report', [StaffAttendanceController::class, 'report'])->middleware('permission:attendance.view');
        Route::get('/staff-attendances/report/pdf', [StaffAttendanceController::class, 'reportPdf'])->middleware('permission:attendance.view');
        Route::get('/staff-attendances/monthly', [StaffAttendanceController::class, 'monthlyGrid'])->middleware('permission:attendance.view');
        Route::get('/staff-attendances/monthly/pdf', [StaffAttendanceController::class, 'monthlyGridPdf'])->middleware('permission:attendance.view');

        // Holidays
        Route::get('/holidays', [HolidayController::class, 'index'])->middleware('permission:attendance.view');
        Route::post('/holidays', [HolidayController::class, 'store'])->middleware('permission:attendance.create');
        Route::put('/holidays/{id}', [HolidayController::class, 'update'])->middleware('permission:attendance.create');
        Route::delete('/holidays/{id}', [HolidayController::class, 'destroy'])->middleware('permission:attendance.delete');

        // Weekend Settings
        Route::get('/weekend-settings', [WeekendSettingController::class, 'index'])->middleware('permission:settings.view');
        Route::post('/weekend-settings', [WeekendSettingController::class, 'store'])->middleware('permission:settings.edit');
        Route::delete('/weekend-settings/{id}', [WeekendSettingController::class, 'destroy'])->middleware('permission:settings.edit');
    });

    // Homework Module Secured Routes
    Route::middleware(['module:homework'])->group(function () {
        Route::get('/homeworks', [HomeworkController::class, 'index'])->middleware('permission:homework.view');
        Route::post('/homeworks', [HomeworkController::class, 'store'])->middleware('permission:homework.create');
        Route::get('/homeworks/{homework}', [HomeworkController::class, 'show'])->middleware('permission:homework.view');
        Route::put('/homeworks/{homework}', [HomeworkController::class, 'update'])->middleware('permission:homework.edit');
        Route::post('/homeworks/{homework}', [HomeworkController::class, 'update'])->middleware('permission:homework.edit');
        Route::delete('/homeworks/{homework}', [HomeworkController::class, 'destroy'])->middleware('permission:homework.delete');
    });

    // Notices Module Secured Routes
    Route::middleware(['module:notices'])->group(function () {
        Route::get('/notices', [NoticeController::class, 'index'])->middleware('permission:notice.view');
        Route::post('/notices', [NoticeController::class, 'store'])->middleware('permission:notice.create');
        Route::get('/notices/{notice}', [NoticeController::class, 'show'])->middleware('permission:notice.view');
        Route::put('/notices/{notice}', [NoticeController::class, 'update'])->middleware('permission:notice.edit');
        Route::post('/notices/{notice}', [NoticeController::class, 'update'])->middleware('permission:notice.edit');
        Route::delete('/notices/{notice}', [NoticeController::class, 'destroy'])->middleware('permission:notice.delete');
    });

    // Examinations Module Secured Routes
    Route::middleware(['module:examinations'])->group(function () {
        // Grade Scales
        Route::get('/grade-scales', [\App\Http\Controllers\Api\GradeScaleController::class, 'index'])->middleware('permission:exam.view');
        Route::post('/grade-scales', [\App\Http\Controllers\Api\GradeScaleController::class, 'store'])->middleware('permission:exam.create');
        Route::get('/grade-scales/{gradeScale}', [\App\Http\Controllers\Api\GradeScaleController::class, 'show'])->middleware('permission:exam.view');
        Route::put('/grade-scales/{gradeScale}', [\App\Http\Controllers\Api\GradeScaleController::class, 'update'])->middleware('permission:exam.edit');
        Route::patch('/grade-scales/{gradeScale}/toggle-status', [\App\Http\Controllers\Api\GradeScaleController::class, 'toggleStatus'])->middleware('permission:exam.edit');
        Route::delete('/grade-scales/{gradeScale}', [\App\Http\Controllers\Api\GradeScaleController::class, 'destroy'])->middleware('permission:exam.delete');

        // Exam Types
        Route::get('/exam-types', [\App\Http\Controllers\Api\ExamTypeController::class, 'index'])->middleware('permission:exam.view');
        Route::post('/exam-types', [\App\Http\Controllers\Api\ExamTypeController::class, 'store'])->middleware('permission:exam.create');
        Route::get('/exam-types/{examType}', [\App\Http\Controllers\Api\ExamTypeController::class, 'show'])->middleware('permission:exam.view');
        Route::put('/exam-types/{examType}', [\App\Http\Controllers\Api\ExamTypeController::class, 'update'])->middleware('permission:exam.edit');
        Route::patch('/exam-types/{examType}/toggle-status', [\App\Http\Controllers\Api\ExamTypeController::class, 'toggleStatus'])->middleware('permission:exam.edit');
        Route::delete('/exam-types/{examType}', [\App\Http\Controllers\Api\ExamTypeController::class, 'destroy'])->middleware('permission:exam.delete');

        // Exams
        Route::get('/exams', [\App\Http\Controllers\Api\ExamController::class, 'index'])->middleware('permission:exam.view');
        Route::post('/exams', [\App\Http\Controllers\Api\ExamController::class, 'store'])->middleware('permission:exam.create');
        Route::get('/exams/{exam}', [\App\Http\Controllers\Api\ExamController::class, 'show'])->middleware('permission:exam.view');
        Route::put('/exams/{exam}', [\App\Http\Controllers\Api\ExamController::class, 'update'])->middleware('permission:exam.edit');
        Route::patch('/exams/{exam}/toggle-publish', [\App\Http\Controllers\Api\ExamController::class, 'togglePublish'])->middleware('permission:exam.edit');
        Route::delete('/exams/{exam}', [\App\Http\Controllers\Api\ExamController::class, 'destroy'])->middleware('permission:exam.delete');

        // Exam Schedules
        Route::get('/exam-schedules', [\App\Http\Controllers\Api\ExamScheduleController::class, 'index'])->middleware('permission:exam_schedule.view');
        Route::post('/exam-schedules', [\App\Http\Controllers\Api\ExamScheduleController::class, 'store'])->middleware('permission:exam_schedule.create');
        Route::delete('/exam-schedules/{examSchedule}', [\App\Http\Controllers\Api\ExamScheduleController::class, 'destroy'])->middleware('permission:exam_schedule.delete');

        // Exam Marks
        Route::get('/exam-marks/students', [\App\Http\Controllers\Api\ExamMarkController::class, 'loadStudentsForMarks'])->middleware('permission:marks.view');
        Route::post('/exam-marks/save', [\App\Http\Controllers\Api\ExamMarkController::class, 'store'])->middleware('permission:marks.create');

        // Report Cards
        Route::get('/report-cards/students-status', [\App\Http\Controllers\Api\ReportCardController::class, 'getStudentsReportStatus'])->middleware('permission:result.view');
        Route::get('/report-cards/pdf', [\App\Http\Controllers\Api\ReportCardController::class, 'downloadPdf'])->middleware('permission:report_card.view');

        // Admit Cards
        Route::get('/admit-cards/pdf', [\App\Http\Controllers\Api\AdmitCardController::class, 'downloadPdf'])->middleware('permission:exam_schedule.view');
    });

    // Fees Module Secured Routes
    Route::middleware(['module:fees'])->group(function () {
        // Fee Types
        Route::get('/fee-types', [FeeTypeController::class, 'index'])->middleware('permission:fee_type.view');
        Route::post('/fee-types', [FeeTypeController::class, 'store'])->middleware('permission:fee_type.create');
        Route::get('/fee-types/{id}', [FeeTypeController::class, 'show'])->middleware('permission:fee_type.view');
        Route::put('/fee-types/{id}', [FeeTypeController::class, 'update'])->middleware('permission:fee_type.edit');
        Route::patch('/fee-types/{id}/toggle-status', [FeeTypeController::class, 'toggleStatus'])->middleware('permission:fee_type.edit');
        Route::delete('/fee-types/{id}', [FeeTypeController::class, 'destroy'])->middleware('permission:fee_type.delete');

        // Fee Structures
        Route::get('/fee-structures', [FeeStructureController::class, 'index'])->middleware('permission:fee_structure.view');
        Route::post('/fee-structures', [FeeStructureController::class, 'store'])->middleware('permission:fee_structure.create');
        Route::get('/fee-structures/{id}', [FeeStructureController::class, 'show'])->middleware('permission:fee_structure.view');
        Route::put('/fee-structures/{id}', [FeeStructureController::class, 'update'])->middleware('permission:fee_structure.edit');
        Route::post('/fee-structures/{id}/clone', [FeeStructureController::class, 'clone'])->middleware('permission:fee_structure.create');
        Route::delete('/fee-structures/{id}', [FeeStructureController::class, 'destroy'])->middleware('permission:fee_structure.delete');

        // Student Assignments
        Route::get('/fee-assignments', [StudentFeeAssignmentController::class, 'index'])->middleware('permission:fee_structure.view');
        Route::post('/fee-assignments', [StudentFeeAssignmentController::class, 'store'])->middleware('permission:fee_structure.create');
        Route::post('/fee-assignments/bulk', [StudentFeeAssignmentController::class, 'bulkAssign'])->middleware('permission:fee_structure.create');

        // Discounts
        Route::get('/fee-discounts', [FeeDiscountController::class, 'index'])->middleware('permission:fee_collection.view');
        Route::post('/fee-discounts', [FeeDiscountController::class, 'store'])->middleware('permission:fee_collection.create');
        // Note: optional show/edit/delete can use regular routes
        Route::get('/fee-discounts/{id}', [FeeDiscountController::class, 'show'])->middleware('permission:fee_collection.view');
        Route::put('/fee-discounts/{id}', [FeeDiscountController::class, 'update'])->middleware('permission:fee_collection.edit');
        Route::delete('/fee-discounts/{id}', [FeeDiscountController::class, 'destroy'])->middleware('permission:fee_collection.delete');

        // Fine Rules
        Route::get('/fee-fine-rules', [FeeFineRuleController::class, 'index'])->middleware('permission:fee_collection.view');
        Route::post('/fee-fine-rules', [FeeFineRuleController::class, 'store'])->middleware('permission:fee_collection.create');
        Route::get('/fee-fine-rules/{id}', [FeeFineRuleController::class, 'show'])->middleware('permission:fee_collection.view');
        Route::put('/fee-fine-rules/{id}', [FeeFineRuleController::class, 'update'])->middleware('permission:fee_collection.edit');
        Route::delete('/fee-fine-rules/{id}', [FeeFineRuleController::class, 'destroy'])->middleware('permission:fee_collection.delete');

        // Fee Collections
        Route::get('/fee-collections/dues/{student_id}', [FeeCollectionController::class, 'getStudentDues'])->middleware('permission:fee_collection.view');
        Route::post('/fee-collections/collect', [FeeCollectionController::class, 'collect'])->middleware('permission:fee_collection.create');
        Route::get('/fee-collections/history', [FeeCollectionController::class, 'history'])->middleware('permission:fee_collection.view');

        // Receipts
        Route::get('/fee-receipts', [ReceiptController::class, 'index'])->middleware('permission:receipt.view');
        Route::get('/fee-receipts/{id}/pdf', [ReceiptController::class, 'downloadPdf'])->middleware('permission:receipt.view');

        // Student Ledger
        Route::get('/student-ledgers/{student_id}', [StudentLedgerController::class, 'show'])->middleware('permission:ledger.view');

        // Reports
        Route::get('/fee-reports', [FeeReportController::class, 'index'])->middleware('permission:report.view');
    });
});

// Mobile Student App Routes
Route::post('/mobile/school/verify', [\App\Http\Controllers\Api\MobileAuthController::class, 'verifySchool']);
Route::get('/mobile/academic-years/{school_code}', [\App\Http\Controllers\Api\MobileAuthController::class, 'academicYears']);
Route::post('/mobile/login', [\App\Http\Controllers\Api\MobileAuthController::class, 'login']);

Route::post('/mobile/change-password', [\App\Http\Controllers\Api\StudentMobileApiController::class, 'changePassword']);

Route::middleware(['auth:sanctum', 'mobile.context'])->prefix('mobile')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Api\StudentMobileApiController::class, 'dashboard']);
    Route::get('/profile', [\App\Http\Controllers\Api\StudentMobileApiController::class, 'profile']);
    Route::get('/attendance', [\App\Http\Controllers\Api\StudentMobileApiController::class, 'attendance']);
    Route::get('/homework', [\App\Http\Controllers\Api\StudentMobileApiController::class, 'homework']);
    Route::get('/notices', [\App\Http\Controllers\Api\StudentMobileApiController::class, 'notices']);
    Route::get('/results', [\App\Http\Controllers\Api\StudentMobileApiController::class, 'results']);
    Route::get('/report-cards', [\App\Http\Controllers\Api\StudentMobileApiController::class, 'reportCards']);
    Route::get('/report-card/{id}', [\App\Http\Controllers\Api\StudentMobileApiController::class, 'reportCardUrl']);
    Route::get('/report-card/{id}/download', [\App\Http\Controllers\Api\StudentMobileApiController::class, 'downloadReportCard']);
    Route::get('/fees', [\App\Http\Controllers\Api\StudentMobileApiController::class, 'fees']);
    Route::get('/receipts', [\App\Http\Controllers\Api\StudentMobileApiController::class, 'receipts']);
    Route::get('/receipt/{id}', [\App\Http\Controllers\Api\StudentMobileApiController::class, 'receiptUrl']);
    Route::get('/receipt/{id}/download', [\App\Http\Controllers\Api\StudentMobileApiController::class, 'downloadReceipt']);
});

