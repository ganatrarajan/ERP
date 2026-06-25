<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentAcademicRecord;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\School;
use App\Models\Attendance;
use App\Models\Homework;
use App\Models\Notice;
use App\Models\Subject;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\ExamMark;
use App\Models\GradeScale;
use App\Models\FeeReceipt;
use App\Models\FeeCollection;
use App\Models\ReportCardSetup;
use App\Services\FeeCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class StudentMobileApiController extends Controller
{
    protected FeeCalculationService $feeCalcService;

    public function __construct(FeeCalculationService $feeCalcService)
    {
        $this->feeCalcService = $feeCalcService;
    }

    protected function successResponse($data = [], $message = null, $code = 200): JsonResponse
    {
        $response = ['success' => true];
        if ($message) $response['message'] = $message;
        if (is_array($data)) $response = array_merge($response, $data);
        else $response['data'] = $data;
        return response()->json($response, $code);
    }

    protected function errorResponse($message, $code = 400, $errors = null): JsonResponse
    {
        $response = ['success' => false, 'message' => $message];
        if ($errors) $response['errors'] = $errors;
        return response()->json($response, $code);
    }

    /**
     * POST /mobile/change-password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:6',
            'confirm_password' => 'required|string|same:new_password',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors());
        }

        $student = Student::findOrFail($request->student_id);

        if (!Hash::check($request->old_password, $student->password)) {
            return $this->errorResponse('Validation error', 422, [
                'old_password' => ['The old password does not match.']
            ]);
        }

        $student->password = Hash::make($request->new_password);
        $student->password_changed = 1;
        $student->save();

        return $this->successResponse([], 'Password changed successfully.');
    }

    /**
     * GET /mobile/dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        $student = $request->user();
        $academicYearId = $request->attributes->get('academic_year_id');

        $record = StudentAcademicRecord::where('student_id', $student->id)->first();
        if (!$record) {
            return $this->errorResponse('Student academic record not found for the current academic year.', 404);
        }

        // Attendance Percentage (Global scope applied on Attendance)
        $attendanceStats = $this->getStudentAttendanceStats($student->id);
        $attendancePercentage = $attendanceStats['attendance_rate'];

        // Pending Homework Count
        $pendingHomeworkCount = Homework::where('class_id', $record->class_id)
            ->where('section_id', $record->section_id)
            ->where('is_delete', 0)
            ->where('submission_date', '>=', Carbon::today()->format('Y-m-d'))
            ->whereHas('subject', function ($q) use ($student, $academicYearId) {
                $q->where('is_delete', 0)
                  ->where('status', 'active')
                  ->where(function ($q2) use ($student, $academicYearId) {
                      $q2->where('is_optional', false)
                        ->orWhere(function ($q3) use ($student, $academicYearId) {
                            $q3->where('is_optional', true)
                              ->whereIn('id', function ($subQuery) use ($student, $academicYearId) {
                                  $subQuery->select('subject_id')
                                    ->from('student_optional_subjects')
                                    ->where('student_id', $student->id);
                                  if ($academicYearId) {
                                      $subQuery->where('academic_year_id', $academicYearId);
                                  }
                              });
                        });
                  });
            })
            ->count();

        // Latest Notice Count
        $latestNoticeCount = Notice::where('notice_date', '>=', Carbon::today()->subDays(30)->format('Y-m-d'))
            ->where(function ($q) use ($record) {
                $q->where('target_type', 'Entire School')
                  ->orWhere(function ($q2) use ($record) {
                      $q2->where('target_type', 'Class Wise')->where('class_id', $record->class_id);
                  })
                  ->orWhere(function ($q2) use ($record) {
                      $q2->where('target_type', 'Section Wise')->where('section_id', $record->section_id);
                  });
            })
            ->count();

        // Pending Fee Amount
        $duesData = $this->feeCalcService->getStudentFeeDues($student->id, $academicYearId);
        $pendingFeeAmount = $duesData['outstanding_balance'] ?? 0.00;

        // Latest Exam Result
        $latestExamResult = null;
        $latestExam = Exam::where('status', 'published')
            ->whereHas('schedules', function ($q) use ($record) {
                $q->where('class_id', $record->class_id)->where('section_id', $record->section_id);
            })
            ->orderBy('end_date', 'desc')
            ->first();

        if ($latestExam) {
            $examRes = $this->calculateStudentRankAndResult($latestExam->id, $record->class_id, $record->section_id, $student->id);
            if ($examRes) {
                $latestExamResult = [
                    'exam_id' => $latestExam->id,
                    'exam_name' => $latestExam->name,
                    'total_max_marks' => $examRes['total_max_marks'],
                    'total_obtained_marks' => $examRes['total_obtained_marks'],
                    'percentage' => $examRes['percentage'],
                    'grade' => $examRes['grade'],
                    'result' => $examRes['result'],
                    'rank' => $examRes['rank'],
                ];
            }
        }

        $school = School::find($student->school_id);
        $academicYear = AcademicYear::find($academicYearId);

        $student->load(['parent', 'currentAcademicRecord.class', 'currentAcademicRecord.section']);
        $student->setAttribute('school_name', $school ? $school->name : 'N/A');
        $student->setAttribute('mobile_academic_year', $academicYear ? $academicYear->title : 'N/A');

        return $this->successResponse([
            'student' => $student,
            'attendance_percentage' => $attendancePercentage,
            'pending_homework_count' => $pendingHomeworkCount,
            'latest_notice_count' => $latestNoticeCount,
            'pending_fee_amount' => $pendingFeeAmount,
            'latest_exam_result' => $latestExamResult,
            'mobile_academic_year' => $academicYear ? $academicYear->title : 'N/A',
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        $student = Student::with(['parent', 'currentAcademicRecord.class', 'currentAcademicRecord.section'])
            ->findOrFail($request->user()->id);

        $school = School::find($student->school_id);
        $academicYearId = $request->attributes->get('academic_year_id');
        $academicYear = AcademicYear::find($academicYearId);

        $student->setAttribute('school_name', $school ? $school->name : 'N/A');
        $student->setAttribute('mobile_academic_year', $academicYear ? $academicYear->title : 'N/A');

        return $this->successResponse(['student' => $student]);
    }

    public function attendance(Request $request): JsonResponse
    {
        $student = $request->user();
        $query = Attendance::where('student_id', $student->id);

        if ($request->filled('month')) {
            $query->where('attendance_date', 'like', "{$request->month}%");
        }

        $attendances = $query->orderBy('attendance_date', 'desc')->get();
        $stats = [
            'Present' => $attendances->where('status', 'Present')->count(),
            'Absent' => $attendances->where('status', 'Absent')->count(),
            'Late' => $attendances->where('status', 'Late')->count(),
            'Half Day' => $attendances->where('status', 'Half Day')->count(),
            'Leave' => $attendances->where('status', 'Leave')->count(),
            'total' => $attendances->count(),
        ];
        $presents = $stats['Present'] + $stats['Late'] + $stats['Half Day'];
        $stats['attendance_rate'] = $stats['total'] > 0 ? round(($presents / $stats['total']) * 100, 2) : 100.00;

        return $this->successResponse(['attendances' => $attendances, 'stats' => $stats]);
    }

    public function homework(Request $request): JsonResponse
    {
        $student = $request->user();
        $record = StudentAcademicRecord::where('student_id', $student->id)->first();
        if (!$record) return $this->errorResponse('Student academic record not found.', 404);

        $academicYearId = $request->attributes->get('academic_year_id');

        $query = Homework::with('subject')
            ->where('class_id', $record->class_id)
            ->where('section_id', $record->section_id)
            ->where('is_delete', 0)
            ->whereHas('subject', function ($q) use ($student, $academicYearId) {
                $q->where('is_delete', 0)
                  ->where('status', 'active')
                  ->where(function ($q2) use ($student, $academicYearId) {
                      $q2->where('is_optional', false)
                        ->orWhere(function ($q3) use ($student, $academicYearId) {
                            $q3->where('is_optional', true)
                              ->whereIn('id', function ($subQuery) use ($student, $academicYearId) {
                                  $subQuery->select('subject_id')
                                    ->from('student_optional_subjects')
                                    ->where('student_id', $student->id);
                                  if ($academicYearId) {
                                      $subQuery->where('academic_year_id', $academicYearId);
                                  }
                              });
                        });
                  });
            });

        if ($request->filled('subject_id')) $query->where('subject_id', $request->input('subject_id'));
        if ($request->filled('date')) $query->where('submission_date', $request->input('date'));

        $homeworks = $query->orderBy('submission_date', 'desc')->orderBy('id', 'desc')->get();
        return $this->successResponse(['homeworks' => $homeworks]);
    }

    public function notices(Request $request): JsonResponse
    {
        $student = $request->user();
        $record = StudentAcademicRecord::where('student_id', $student->id)->first();
        if (!$record) return $this->errorResponse('Student academic record not found.', 404);

        $query = Notice::with('creator');
        $type = $request->input('type');
        if ($type === 'school') {
            $query->where('target_type', 'Entire School');
        } elseif ($type === 'class') {
            $query->where(function ($q) use ($record) {
                $q->where('target_type', 'Class Wise')->where('class_id', $record->class_id)
                  ->orWhere(function ($q2) use ($record) { $q2->where('target_type', 'Section Wise')->where('section_id', $record->section_id); });
            });
        } else {
            $query->where(function ($q) use ($record) {
                $q->where('target_type', 'Entire School')
                  ->orWhere(function ($q2) use ($record) { $q2->where('target_type', 'Class Wise')->where('class_id', $record->class_id); })
                  ->orWhere(function ($q2) use ($record) { $q2->where('target_type', 'Section Wise')->where('section_id', $record->section_id); });
            });
        }

        $notices = $query->orderBy('notice_date', 'desc')->orderBy('id', 'desc')->get();
        return $this->successResponse(['notices' => $notices]);
    }

    public function subjects(Request $request): JsonResponse
    {
        $student = $request->user();
        $record = StudentAcademicRecord::where('student_id', $student->id)->first();
        if (!$record) return $this->errorResponse('Student academic record not found.', 404);

        $academicYearId = $request->attributes->get('academic_year_id');

        $query = Subject::where('school_id', $student->school_id)
            ->where('is_delete', 0)
            ->where('status', 'active');

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        $query->where('class_id', $record->class_id)
            ->where(function ($q) use ($record) {
                $q->where('section_id', $record->section_id)
                  ->orWhereNull('section_id');
            })
            ->where(function ($q) use ($student, $academicYearId) {
                $q->where('is_optional', false)
                  ->orWhere(function ($q2) use ($student, $academicYearId) {
                      $q2->where('is_optional', true)
                        ->whereIn('id', function ($subQuery) use ($student, $academicYearId) {
                            $subQuery->select('subject_id')
                              ->from('student_optional_subjects')
                              ->where('student_id', $student->id);
                            if ($academicYearId) {
                                $subQuery->where('academic_year_id', $academicYearId);
                            }
                        });
                  });
            });

        $subjects = $query->orderBy('name', 'asc')->get();
        return $this->successResponse(['subjects' => $subjects]);
    }

    public function results(Request $request): JsonResponse
    {
        $student = $request->user();
        $record = StudentAcademicRecord::where('student_id', $student->id)->first();
        if (!$record) return $this->errorResponse('Student academic record not found.', 404);

        // Fetch published exams for this student's class and section
        $exams = Exam::where('status', 'published')
            ->whereHas('schedules', function ($q) use ($record) {
                $q->where('class_id', $record->class_id)->where('section_id', $record->section_id);
            })
            ->orderBy('end_date', 'desc')
            ->get();

        $results = [];
        foreach ($exams as $exam) {
            $examRes = $this->calculateStudentRankAndResult($exam->id, $record->class_id, $record->section_id, $student->id);
            if ($examRes) {
                $results[] = [
                    'exam_id' => $exam->id,
                    'exam_name' => $exam->name,
                    'start_date' => $exam->start_date instanceof Carbon ? $exam->start_date->format('Y-m-d') : (Carbon::parse($exam->start_date)->format('Y-m-d')),
                    'end_date' => $exam->end_date instanceof Carbon ? $exam->end_date->format('Y-m-d') : (Carbon::parse($exam->end_date)->format('Y-m-d')),
                    'total_max_marks' => $examRes['total_max_marks'],
                    'total_obtained_marks' => $examRes['total_obtained_marks'],
                    'percentage' => $examRes['percentage'],
                    'grade' => $examRes['grade'],
                    'result' => $examRes['result'],
                    'rank' => $examRes['rank'],
                    'subject_details' => $examRes['subject_details'],
                ];
            }
        }
        return $this->successResponse(['results' => $results]);
    }

    public function reportCards(Request $request): JsonResponse
    {
        $student = $request->user();
        $record = StudentAcademicRecord::where('student_id', $student->id)->first();
        if (!$record) return $this->errorResponse('Student academic record not found.', 404);

        $setups = ReportCardSetup::where('status', 'published')
            ->where('class_id', $record->class_id)
            ->where(function ($q) use ($record) {
                $q->whereNull('section_id')->orWhere('section_id', $record->section_id);
            })->orderBy('created_at', 'desc')->get();

        $reportCards = $setups->map(function ($setup) {
            $encodedId = ($setup->id * 1000000) + ($setup->updated_at->timestamp % 1000000);
            return [
                'id' => $encodedId,
                'exam_name' => $setup->name,
                'start_date' => $setup->created_at->format('Y-m-d'),
                'end_date' => $setup->updated_at->format('Y-m-d'),
                'pdf_url' => url("/api/mobile/report-card/" . $encodedId),
            ];
        });
        return $this->successResponse(['report_cards' => $reportCards]);
    }

    public function reportCardUrl($id, Request $request): JsonResponse
    {
        $realId = (int) ($id / 1000000);
        $setup = ReportCardSetup::where('id', $realId)->where('status', 'published')->first();
        if (!$setup) return $this->errorResponse('Report card / setup not found.', 404);
        return $this->successResponse(['pdf_url' => url("/api/mobile/report-card/{$id}/download")]);
    }

    public function downloadReportCard($id, Request $request)
    {
        $student = $request->user();
        if (!$student) abort(401, 'Unauthenticated.');

        $school = School::findOrFail($student->school_id);
        $academicYearId = $request->attributes->get('academic_year_id');
        $currentYear = AcademicYear::findOrFail($academicYearId);

        $record = StudentAcademicRecord::where('student_id', $student->id)->first();
        if (!$record) abort(404, 'Student academic record not found.');

        $realId = (int) ($id / 1000000);
        $setup = ReportCardSetup::where('id', $realId)->where('status', 'published')->first();
        if (!$setup) abort(404, 'Report card / setup not found.');

        $exam = Exam::findOrFail($setup->exam_id_1);
        $exam2 = $setup->exam_id_2 ? Exam::findOrFail($setup->exam_id_2) : null;

        $examRes = $this->calculateStudentRankAndResult($setup->exam_id_1, $record->class_id, $record->section_id, $student->id, $setup->exam_id_2, $setup->include_graded ?: 'no');
        if (!$examRes) abort(404, 'No marks entered for this report card.');

        $classModel = ClassModel::findOrFail($record->class_id);
        $sectionModel = Section::findOrFail($record->section_id);

        $examRes['attendance'] = $this->getStudentAttendanceStats($student->id);
        $examRes['school'] = $school;
        
        $examCopy = clone $exam;
        $examCopy->name = $setup->name;
        $examRes['exam'] = $examCopy;
        $examRes['exam2'] = $exam2;
        
        $examRes['class'] = $classModel;
        $examRes['section'] = $sectionModel;
        $examRes['academic_year'] = $currentYear;

        $grades = GradeScale::where('school_id', $school->id)->where('status', 'active')->orderBy('min_percentage', 'desc')->get();
        $logoBase64 = $this->getLogoBase64($school->logo);
        $templateKey = $setup->template ?: ($school->default_report_card_template ?: 'basic');

        $pdfData = [
            'reportCards' => [$examRes],
            'templateKey' => $templateKey,
            'printDate' => Carbon::now()->format('d M Y'),
            'grades' => $grades,
            'logo_base64' => $logoBase64,
        ];

        $pdf = Pdf::loadView('reports.' . $templateKey, $pdfData);
        $filename = 'Report_Card_' . $setup->id . '_' . $setup->updated_at->timestamp . '_' . str_replace(' ', '_', $student->first_name . '_' . $student->last_name) . '.pdf';
        return $pdf->stream($filename);
    }

    public function fees(Request $request): JsonResponse
    {
        $student = $request->user();
        $academicYearId = $request->attributes->get('academic_year_id');

        $duesData = $this->feeCalcService->getStudentFeeDues($student->id, $academicYearId);
        return $this->successResponse([
            'total_fees' => $duesData['total_fee'] ?? 0.00,
            'paid_fees' => $duesData['total_paid'] ?? 0.00,
            'pending_fees' => $duesData['outstanding_balance'] ?? 0.00,
            'installment_details' => $duesData['installments'] ?? []
        ]);
    }

    public function receipts(Request $request): JsonResponse
    {
        $student = $request->user();
        $receipts = FeeReceipt::with(['collection.installment'])
            ->whereHas('collection', function ($q) use ($student) {
                $q->where('student_id', $student->id);
            })
            ->orderBy('generated_at', 'desc')->get();

        $list = $receipts->map(function ($receipt) {
            return [
                'id' => $receipt->id,
                'receipt_number' => $receipt->receipt_number,
                'amount_paid' => $receipt->collection ? (float)$receipt->collection->amount_paid : 0.00,
                'payment_date' => $receipt->collection ? $receipt->collection->payment_date->format('Y-m-d') : '',
                'installment_name' => $receipt->collection && $receipt->collection->installment ? $receipt->collection->installment->installment_name : '',
                'pdf_url' => url("/api/mobile/receipt/{$receipt->id}"),
            ];
        });
        return $this->successResponse(['receipts' => $list]);
    }

    public function receiptUrl($id, Request $request): JsonResponse
    {
        $student = $request->user();
        $receipt = FeeReceipt::where('id', $id)
            ->whereHas('collection', function ($q) use ($student) {
                $q->where('student_id', $student->id);
            })->first();
        if (!$receipt) return $this->errorResponse('Receipt not found.', 404);
        return $this->successResponse(['pdf_url' => url("/api/mobile/receipt/{$id}/download")]);
    }

    public function downloadReceipt($id, Request $request)
    {
        $student = $request->user();
        if (!$student) abort(401, 'Unauthenticated.');

        $receipt = FeeReceipt::with(['collection.installment', 'collection.student', 'collection.collectedBy'])
            ->where('id', $id)
            ->whereHas('collection', function ($q) use ($student) {
                $q->where('student_id', $student->id);
            })->firstOrFail();

        $collection = $receipt->collection;
        $installment = $collection->installment;
        $school = School::findOrFail($receipt->school_id);
        
        $academicRecord = StudentAcademicRecord::withoutGlobalScope('mobile_filter')
            ->with(['class', 'section', 'academicYear'])
            ->where('student_id', $student->id)
            ->where('academic_year_id', $collection->academic_year_id)
            ->first();

        $className = $academicRecord && $academicRecord->class ? $academicRecord->class->name : 'N/A';
        $sectionName = $academicRecord && $academicRecord->section ? $academicRecord->section->name : 'N/A';
        $academicYearTitle = $academicRecord && $academicRecord->academicYear ? $academicRecord->academicYear->title : 'N/A';

        $pdfData = [
            'school' => $school,
            'receipt' => $receipt,
            'collection' => $collection,
            'student' => $student,
            'installment' => $installment,
            'class_name' => $className,
            'section_name' => $sectionName,
            'academic_year_title' => $academicYearTitle,
            'collected_by_name' => $collection->collectedBy ? $collection->collectedBy->name : 'N/A',
            'logo_base64' => $this->getLogoBase64($school->logo),
            'single_copy' => true,
        ];

        $pdf = Pdf::loadView('reports.fee_receipt', $pdfData);
        return $pdf->stream('Receipt_' . $receipt->receipt_number . '.pdf');
    }

    /* Helpers */

    private function calculateStudentRankAndResult($examId, $classId, $sectionId, $studentId, $examId2 = null, $includeGraded = 'no'): ?array
    {
        $records = StudentAcademicRecord::with(['student'])
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->whereHas('student', function ($q) {
                $q->where('is_delete', 0)->where('status', 'active');
            })->get();

        $academicYearId = request()->attributes->get('academic_year_id');
        $schoolId = request()->attributes->get('school_id');

        $sectionResults = $this->calculateSectionResults($schoolId, $academicYearId, $examId, $classId, $sectionId, $records, $examId2, $includeGraded);
        foreach ($sectionResults as $res) {
            if ($res['student_id'] == $studentId) return $res;
        }
        return null;
    }

    private function calculateSectionResults($schoolId, $academicYearId, $examId, $classId, $sectionId, $records, $examId2 = null, $includeGraded = 'no'): array
    {
        // Fetch all optional subjects mapping for these students in this academic year
        $optionalSubjectsMap = \Illuminate\Support\Facades\DB::table('student_optional_subjects')
            ->where('academic_year_id', $academicYearId)
            ->whereIn('student_id', $records->pluck('student_id')->toArray())
            ->get()
            ->groupBy('student_id')
            ->map(function ($items) {
                return $items->pluck('subject_id')->toArray();
            });

        // 1. Single Exam Mode
        if (!$examId2) {
            // Fetch schedules for this exam/class/section
            $schedules = ExamSchedule::with(['subject'])
                ->where('exam_id', $examId)
                ->where('class_id', $classId)
                ->where('section_id', $sectionId)
                ->where('is_delete', 0)
                ->get();

            // Fetch marks entered
            $marks = ExamMark::where('exam_id', $examId)
                ->where('academic_year_id', $academicYearId)
                ->get()
                ->groupBy('student_id');

            // Fetch grade scales
            $grades = GradeScale::where('school_id', $schoolId)
                ->where('status', 'active')
                ->orderBy('min_percentage', 'desc')
                ->get();

            $calculated = [];

            foreach ($records as $record) {
                $student = $record->student;
                $studentMarks = $marks->get($student->id) ?? collect();

                $subjectDetails = [];
                $totalMaxMarks = 0;
                $totalObtainedMarks = 0;
                $passedAllMandatory = true;
                $hasMarksEntry = false;

                $scholasticSubjects = [];
                $coScholasticSubjects = [];

                foreach ($schedules as $sched) {
                    $subj = $sched->subject;
                    if ($includeGraded !== 'yes' && $subj->evaluation_type === 'grades') {
                        continue;
                    }
                    if ($subj->is_optional) {
                        $chosenSubjects = $optionalSubjectsMap->get($student->id) ?? [];
                        if (!in_array($subj->id, $chosenSubjects)) {
                            continue;
                        }
                    }
                    $visibility = $sched->report_card_visibility ?? 'included_in_result';
                    if ($visibility === 'hidden') {
                        continue;
                    }

                    $mark = $studentMarks->firstWhere('subject_id', $subj->id);
                    $obtained = ($mark && $mark->is_absent) ? 'Ab' : ($mark ? $mark->marks_obtained : null);
                    if ($mark) {
                        $hasMarksEntry = true;
                    }

                    // Get Grade
                    $gradeName = '-';
                    if ($mark && $mark->is_absent) {
                        $gradeName = 'Ab';
                    } elseif ($subj->evaluation_type === 'grades') {
                        if ($mark && $mark->gradeScale) {
                            $gradeName = $mark->gradeScale->grade;
                        } elseif ($mark && $mark->grade_id) {
                            $gradeName = GradeScale::where('id', $mark->grade_id)->value('grade') ?? '-';
                        }
                    } else {
                        // Check numeric mark and map grade if scale exists
                        if ($obtained !== null && $obtained !== 'Ab') {
                            $subjMax = $sched->max_marks ?: 100;
                            $pct = ($obtained / $subjMax) * 100;
                            
                            // Map using subject's specific scale or school's general scale
                            $scaleToUse = (!empty($subj->grade_scale_id) && is_array($subj->grade_scale_id))
                                ? GradeScale::whereIn('id', $subj->grade_scale_id)->get()
                                : $grades;

                            $matched = $scaleToUse->where('min_percentage', '<=', $pct)->sortByDesc('min_percentage')->first();
                            if ($matched) {
                                $gradeName = $matched->grade;
                            }
                        }
                    }

                    // Check passing
                    $passedSubj = true;
                    if ($mark && $mark->is_absent) {
                        $passedSubj = false;
                        if ($visibility === 'included_in_result') {
                            $passedAllMandatory = false;
                        }
                    } elseif ($subj->evaluation_type === 'marks' && $obtained !== null && $obtained !== 'Ab') {
                        $passCriteria = $subj->passing_marks ?: 35;
                        if ($obtained < $passCriteria) {
                            $passedSubj = false;
                            if ($visibility === 'included_in_result') {
                                $passedAllMandatory = false;
                            }
                        }
                    }

                    $subjData = [
                        'subject_id' => $subj->id,
                        'subject_name' => $subj->name . ($subj->is_optional ? ' (Optional)' : ''),
                        'subject_code' => $subj->code,
                        'evaluation_type' => $subj->evaluation_type,
                        'subject_category' => $subj->subject_category,
                        'visibility' => $visibility,
                        'max_marks' => $sched->max_marks,
                        'obtained_marks' => $obtained,
                        'passing_marks' => $subj->passing_marks,
                        'grade' => $gradeName,
                        'is_pass' => $passedSubj,
                        'remarks' => $mark ? $mark->remarks : '',
                    ];

                    if ($visibility === 'included_in_result' && $subj->evaluation_type === 'marks') {
                        $totalMaxMarks += $sched->max_marks;
                        if ($obtained !== null && $obtained !== 'Ab') {
                            $totalObtainedMarks += (float)$obtained;
                        }
                    }

                    if ($subj->subject_category === 'co_scholastic') {
                        $coScholasticSubjects[] = $subjData;
                    } else {
                        $scholasticSubjects[] = $subjData;
                    }

                    $subjectDetails[] = $subjData;
                }

                // Calculations
                $percentage = null;
                $overallGrade = '-';
                if ($hasMarksEntry) {
                    $percentage = $totalMaxMarks > 0 ? round(($totalObtainedMarks / $totalMaxMarks) * 100, 2) : 0;
                    $matched = $grades->where('min_percentage', '<=', $percentage)->sortByDesc('min_percentage')->first();
                    if ($matched) {
                        $overallGrade = $matched->grade;
                    }
                } else {
                    $totalObtainedMarks = null;
                }

                $resultStatus = 'N/A';
                if ($hasMarksEntry) {
                    $resultStatus = $passedAllMandatory ? 'Pass' : 'Fail';
                }

                $calculated[] = [
                    'student_id' => $student->id,
                    'admission_no' => $student->admission_no,
                    'roll_no' => $record->roll_no,
                    'student' => $student,
                    'subject_details' => $subjectDetails,
                    'scholastic_subjects' => $scholasticSubjects,
                    'co_scholastic_subjects' => $coScholasticSubjects,
                    'total_max_marks' => $totalMaxMarks,
                    'total_obtained_marks' => $totalObtainedMarks,
                    'percentage' => $percentage,
                    'grade' => $overallGrade,
                    'result' => $resultStatus,
                    'rank' => 0, // Assigned below
                ];
            }
        }
        // 2. Consolidated Exam Mode
        else {
            // Fetch schedules for both exams
            $schedules1 = ExamSchedule::with(['subject'])
                ->where('exam_id', $examId)
                ->where('class_id', $classId)
                ->where('section_id', $sectionId)
                ->where('is_delete', 0)
                ->get();

            $schedules2 = ExamSchedule::with(['subject'])
                ->where('exam_id', $examId2)
                ->where('class_id', $classId)
                ->where('section_id', $sectionId)
                ->where('is_delete', 0)
                ->get();

            // Fetch marks entered for both exams
            $marks1 = ExamMark::where('exam_id', $examId)
                ->where('academic_year_id', $academicYearId)
                ->get()
                ->groupBy('student_id');

            $marks2 = ExamMark::where('exam_id', $examId2)
                ->where('academic_year_id', $academicYearId)
                ->get()
                ->groupBy('student_id');

            // Fetch unique subject IDs scheduled across both exams
            $subjectIds = $schedules1->pluck('subject_id')
                ->merge($schedules2->pluck('subject_id'))
                ->unique();
            $subjects = Subject::whereIn('id', $subjectIds)->get()->keyBy('id');

            // Fetch grade scales
            $grades = GradeScale::where('school_id', $schoolId)
                ->where('status', 'active')
                ->orderBy('min_percentage', 'desc')
                ->get();

            $calculated = [];

            foreach ($records as $record) {
                $student = $record->student;
                $studentMarks1 = $marks1->get($student->id) ?? collect();
                $studentMarks2 = $marks2->get($student->id) ?? collect();

                $subjectDetails = [];
                $scholasticSubjects = [];
                $coScholasticSubjects = [];

                $totalMax1 = 0; $totalObt1 = 0; $hasEntry1 = false;
                $totalMax2 = 0; $totalObt2 = 0; $hasEntry2 = false;
                $totalCombinedMax = 0; $totalCombinedObt = 0; $hasCombinedEntry = false;
                $passedAllMandatory = true;

                foreach ($subjects as $subj) {
                    if ($includeGraded !== 'yes' && $subj->evaluation_type === 'grades') {
                        continue;
                    }
                    if ($subj->is_optional) {
                        $chosenSubjects = $optionalSubjectsMap->get($student->id) ?? [];
                        if (!in_array($subj->id, $chosenSubjects)) {
                            continue;
                        }
                    }
                    $sched1 = $schedules1->firstWhere('subject_id', $subj->id);
                    $sched2 = $schedules2->firstWhere('subject_id', $subj->id);

                    $vis1 = $sched1 ? ($sched1->report_card_visibility ?? 'included_in_result') : 'hidden';
                    $vis2 = $sched2 ? ($sched2->report_card_visibility ?? 'included_in_result') : 'hidden';

                    // If hidden in both, skip
                    if ($vis1 === 'hidden' && $vis2 === 'hidden') {
                        continue;
                    }

                    // Exam 1 details
                    $max1 = $sched1 ? $sched1->max_marks : 100;
                    $mark1 = $sched1 ? $studentMarks1->firstWhere('subject_id', $subj->id) : null;
                    $obt1 = ($mark1 && $mark1->is_absent) ? 'Ab' : ($mark1 ? $mark1->marks_obtained : null);
                    if ($mark1) {
                        $hasEntry1 = true;
                    }

                    $grade1 = '-';
                    if ($sched1 && $vis1 !== 'hidden') {
                        if ($mark1 && $mark1->is_absent) {
                            $grade1 = 'Ab';
                        } elseif ($subj->evaluation_type === 'grades') {
                            if ($mark1 && $mark1->grade_id) {
                                $grade1 = GradeScale::where('id', $mark1->grade_id)->value('grade') ?? '-';
                            }
                        } else {
                            if ($obt1 !== null && $obt1 !== 'Ab') {
                                $pct1 = ($obt1 / $max1) * 100;
                                $scaleToUse = (!empty($subj->grade_scale_id) && is_array($subj->grade_scale_id)) ? GradeScale::whereIn('id', $subj->grade_scale_id)->get() : $grades;
                                $matched = $scaleToUse->where('min_percentage', '<=', $pct1)->sortByDesc('min_percentage')->first();
                                if ($matched) {
                                    $grade1 = $matched->grade;
                                }
                            }
                        }
                    }

                    // Exam 2 details
                    $max2 = $sched2 ? $sched2->max_marks : 100;
                    $mark2 = $sched2 ? $studentMarks2->firstWhere('subject_id', $subj->id) : null;
                    $obt2 = ($mark2 && $mark2->is_absent) ? 'Ab' : ($mark2 ? $mark2->marks_obtained : null);
                    if ($mark2) {
                        $hasEntry2 = true;
                    }

                    $grade2 = '-';
                    if ($sched2 && $vis2 !== 'hidden') {
                        if ($mark2 && $mark2->is_absent) {
                            $grade2 = 'Ab';
                        } elseif ($subj->evaluation_type === 'grades') {
                            if ($mark2 && $mark2->grade_id) {
                                $grade2 = GradeScale::where('id', $mark2->grade_id)->value('grade') ?? '-';
                            }
                        } else {
                            if ($obt2 !== null && $obt2 !== 'Ab') {
                                $pct2 = ($obt2 / $max2) * 100;
                                $scaleToUse = (!empty($subj->grade_scale_id) && is_array($subj->grade_scale_id)) ? GradeScale::whereIn('id', $subj->grade_scale_id)->get() : $grades;
                                $matched = $scaleToUse->where('min_percentage', '<=', $pct2)->sortByDesc('min_percentage')->first();
                                if ($matched) {
                                    $grade2 = $matched->grade;
                                }
                            }
                        }
                    }

                    // Combined details
                    $combinedMax = 0;
                    $combinedObt = null;
                    $hasCombinedMark = false;
                    $bothAbsent = ($mark1 && $mark1->is_absent && $mark2 && $mark2->is_absent);

                    if ($sched1 && $vis1 === 'included_in_result' && $obt1 !== null) {
                        $combinedMax += $max1;
                        if ($obt1 !== 'Ab') {
                            $combinedObt = ($combinedObt ?? 0) + $obt1;
                        }
                        $hasCombinedMark = true;
                    }
                    if ($sched2 && $vis2 === 'included_in_result' && $obt2 !== null) {
                        $combinedMax += $max2;
                        if ($obt2 !== 'Ab') {
                            $combinedObt = ($combinedObt ?? 0) + $obt2;
                        }
                        $hasCombinedMark = true;
                    }

                    $combinedGrade = '-';
                    $isPass = true;
                    $passCriteriaPct = $subj->maximum_marks > 0 ? ($subj->passing_marks / $subj->maximum_marks) * 100 : 35;

                    if ($subj->evaluation_type === 'grades') {
                        $combinedGrade = ($grade2 !== '-') ? $grade2 : $grade1;
                    } else {
                        if ($bothAbsent) {
                            $combinedGrade = 'Ab';
                            $isPass = false;
                            $passedAllMandatory = false;
                        } elseif ($hasCombinedMark && $combinedMax > 0) {
                            $combinedPct = $combinedObt !== null ? ($combinedObt / $combinedMax) * 100 : 0;
                            $scaleToUse = (!empty($subj->grade_scale_id) && is_array($subj->grade_scale_id)) ? GradeScale::whereIn('id', $subj->grade_scale_id)->get() : $grades;
                            $matched = $scaleToUse->where('min_percentage', '<=', $combinedPct)->sortByDesc('min_percentage')->first();
                            if ($matched) {
                                $combinedGrade = $matched->grade;
                            }
                            if ($combinedPct < $passCriteriaPct || ($mark1 && $mark1->is_absent) || ($mark2 && $mark2->is_absent)) {
                                $isPass = false;
                                $passedAllMandatory = false;
                            }
                            $hasCombinedEntry = true;
                        }
                    }

                    $subjData = [
                        'subject_id' => $subj->id,
                        'subject_name' => $subj->name . ($subj->is_optional ? ' (Optional)' : ''),
                        'subject_code' => $subj->code,
                        'evaluation_type' => $subj->evaluation_type,
                        'subject_category' => $subj->subject_category,
                        
                        'exam1_scheduled' => (bool)$sched1,
                        'exam1_visibility' => $vis1,
                        'exam1_max_marks' => $sched1 ? $max1 : null,
                        'exam1_obtained_marks' => ($sched1 && $vis1 !== 'hidden') ? $obt1 : null,
                        'exam1_grade' => $grade1,

                        'exam2_scheduled' => (bool)$sched2,
                        'exam2_visibility' => $vis2,
                        'exam2_max_marks' => $sched2 ? $max2 : null,
                        'exam2_obtained_marks' => ($sched2 && $vis2 !== 'hidden') ? $obt2 : null,
                        'exam2_grade' => $grade2,

                        'max_marks' => $hasCombinedMark ? $combinedMax : null,
                        'obtained_marks' => $combinedObt,
                        'grade' => $combinedGrade,
                        'is_pass' => $isPass,
                        'remarks' => ($mark2 ? $mark2->remarks : '') ?: ($mark1 ? $mark1->remarks : ''),
                    ];

                    // Add to overall totals
                    if ($sched1 && $vis1 === 'included_in_result' && $subj->evaluation_type === 'marks') {
                        $totalMax1 += $max1;
                        if ($obt1 !== null && $obt1 !== 'Ab') {
                            $totalObt1 += (float)$obt1;
                        }
                    }
                    if ($sched2 && $vis2 === 'included_in_result' && $subj->evaluation_type === 'marks') {
                        $totalMax2 += $max2;
                        if ($obt2 !== null && $obt2 !== 'Ab') {
                            $totalObt2 += (float)$obt2;
                        }
                    }
                    if ($hasCombinedMark) {
                        $totalCombinedMax += $combinedMax;
                        if ($combinedObt !== null) {
                            $totalCombinedObt += $combinedObt;
                        }
                    }

                    if ($subj->subject_category === 'co_scholastic') {
                        $coScholasticSubjects[] = $subjData;
                    } else {
                        $scholasticSubjects[] = $subjData;
                    }

                    $subjectDetails[] = $subjData;
                }

                // Overall totals calculations
                $pct1 = ($hasEntry1 && $totalMax1 > 0) ? round(($totalObt1 / $totalMax1) * 100, 2) : null;
                $gradeName1 = '-';
                if ($pct1 !== null) {
                    $matched = $grades->where('min_percentage', '<=', $pct1)->sortByDesc('min_percentage')->first();
                    $gradeName1 = $matched ? $matched->grade : '-';
                }

                $pct2 = ($hasEntry2 && $totalMax2 > 0) ? round(($totalObt2 / $totalMax2) * 100, 2) : null;
                $gradeName2 = '-';
                if ($pct2 !== null) {
                    $matched = $grades->where('min_percentage', '<=', $pct2)->sortByDesc('min_percentage')->first();
                    $gradeName2 = $matched ? $matched->grade : '-';
                }

                $percentage = ($hasCombinedEntry && $totalCombinedMax > 0) ? round(($totalCombinedObt / $totalCombinedMax) * 100, 2) : null;
                $overallGrade = '-';
                if ($percentage !== null) {
                    $matched = $grades->where('min_percentage', '<=', $percentage)->sortByDesc('min_percentage')->first();
                    $overallGrade = $matched ? $matched->grade : '-';
                }

                $resultStatus = 'N/A';
                if ($hasCombinedEntry) {
                    $resultStatus = $passedAllMandatory ? 'Pass' : 'Fail';
                }

                $calculated[] = [
                    'student_id' => $student->id,
                    'admission_no' => $student->admission_no,
                    'roll_no' => $record->roll_no,
                    'student' => $student,
                    'subject_details' => $subjectDetails,
                    'scholastic_subjects' => $scholasticSubjects,
                    'co_scholastic_subjects' => $coScholasticSubjects,

                    'exam1_max_marks' => $totalMax1,
                    'exam1_obtained_marks' => $hasEntry1 ? $totalObt1 : null,
                    'exam1_percentage' => $pct1,
                    'exam1_grade' => $gradeName1,

                    'exam2_max_marks' => $totalMax2,
                    'exam2_obtained_marks' => $hasEntry2 ? $totalObt2 : null,
                    'exam2_percentage' => $pct2,
                    'exam2_grade' => $gradeName2,

                    'total_max_marks' => $totalCombinedMax,
                    'total_obtained_marks' => $hasCombinedEntry ? $totalCombinedObt : null,
                    'percentage' => $percentage,
                    'grade' => $overallGrade,
                    'result' => $resultStatus,
                    'rank' => 0, // Assigned below
                ];
            }
        }

        // Rank Calculation (Only rank active students who have marks entered and status is Pass/Fail)
        usort($calculated, function ($a, $b) {
            return $b['total_obtained_marks'] <=> $a['total_obtained_marks'];
        });

        $rank = 1;
        $prevMarks = null;
        foreach ($calculated as $index => &$res) {
            if ($res['result'] === 'N/A') {
                $res['rank'] = '-';
                continue;
            }
            if ($prevMarks !== null && $res['total_obtained_marks'] < $prevMarks) {
                $rank = $index + 1;
            }
            $res['rank'] = $rank;
            $prevMarks = $res['total_obtained_marks'];
        }
        unset($res); // Unset reference

        // Re-sort by rank before returning (ranks are 1, 2, 3... and '-' for N/A)
        usort($calculated, function ($a, $b) {
            $rA = $a['rank'];
            $rB = $b['rank'];

            if ($rA === '-' && $rB === '-') {
                return $a['roll_no'] <=> $b['roll_no'];
            }
            if ($rA === '-') return 1;
            if ($rB === '-') return -1;

            if ($rA === $rB) {
                return $a['roll_no'] <=> $b['roll_no'];
            }
            return $rA <=> $rB;
        });

        return $calculated;
    }

    private function getStudentAttendanceStats($studentId): array
    {
        $atts = Attendance::where('student_id', $studentId)->get();
        $totalDays = $atts->count();
        $presentDays = $atts->whereIn('status', ['Present', 'Late', 'Half Day'])->count();

        return [
            'total_days' => $totalDays,
            'present_days' => $presentDays,
            'attendance_rate' => $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 100.00,
        ];
    }

    private function getLogoBase64($logoUrl)
    {
        if (!$logoUrl) return null;
        if (str_starts_with($logoUrl, 'data:image')) return $logoUrl;

        $path = parse_url($logoUrl, PHP_URL_PATH);
        if ($path) {
            $relativePath = ltrim($path, '/');
            $absolutePath = public_path($relativePath);
            if (file_exists($absolutePath) && is_file($absolutePath)) {
                $type = pathinfo($absolutePath, PATHINFO_EXTENSION);
                $data = file_get_contents($absolutePath);
                return 'data:image/' . $type . ';base64,' . base64_encode($data);
            }
        }

        $fallbackPath = public_path($logoUrl);
        if (file_exists($fallbackPath) && is_file($fallbackPath)) {
            $type = pathinfo($fallbackPath, PATHINFO_EXTENSION);
            $data = file_get_contents($fallbackPath);
            return 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        return null;
    }
}
