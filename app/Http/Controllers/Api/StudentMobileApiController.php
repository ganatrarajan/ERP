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
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\ExamMark;
use App\Models\GradeScale;
use App\Models\FeeReceipt;
use App\Models\FeeCollection;
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
            ->where('submission_date', '>=', Carbon::today()->format('Y-m-d'))
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

        $query = Homework::with('subject')
            ->where('class_id', $record->class_id)
            ->where('section_id', $record->section_id);

        if ($request->filled('subject_id')) $query->where('subject_id', $request->input('subject_id'));
        if ($request->filled('date')) $query->where('submission_date', $request->input('date'));

        $homeworks = $query->orderBy('submission_date', 'asc')->get();
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

        $notices = $query->orderBy('notice_date', 'desc')->get();
        return $this->successResponse(['notices' => $notices]);
    }

    public function results(Request $request): JsonResponse
    {
        $student = $request->user();
        $record = StudentAcademicRecord::where('student_id', $student->id)->first();
        if (!$record) return $this->errorResponse('Student academic record not found.', 404);

        $exams = Exam::where('status', 'published')
            ->whereHas('schedules', function ($q) use ($record) {
                $q->where('class_id', $record->class_id)->where('section_id', $record->section_id);
            })
            ->orderBy('end_date', 'desc')->get();

        $results = [];
        foreach ($exams as $exam) {
            $examRes = $this->calculateStudentRankAndResult($exam->id, $record->class_id, $record->section_id, $student->id);
            if ($examRes) {
                $results[] = [
                    'exam_id' => $exam->id,
                    'exam_name' => $exam->name,
                    'start_date' => $exam->start_date->format('Y-m-d'),
                    'end_date' => $exam->end_date->format('Y-m-d'),
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

        $exams = Exam::where('status', 'published')
            ->whereHas('schedules', function ($q) use ($record) {
                $q->where('class_id', $record->class_id)->where('section_id', $record->section_id);
            })->orderBy('end_date', 'desc')->get();

        $reportCards = $exams->map(function ($exam) {
            return [
                'id' => $exam->id,
                'exam_name' => $exam->name,
                'start_date' => $exam->start_date->format('Y-m-d'),
                'end_date' => $exam->end_date->format('Y-m-d'),
                'pdf_url' => url("/api/mobile/report-card/{$exam->id}"),
            ];
        });
        return $this->successResponse(['report_cards' => $reportCards]);
    }

    public function reportCardUrl($id, Request $request): JsonResponse
    {
        $exam = Exam::where('id', $id)->where('status', 'published')->first();
        if (!$exam) return $this->errorResponse('Report card / exam not found.', 404);
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

        $exam = Exam::where('id', $id)->where('status', 'published')->first();
        if (!$exam) abort(404, 'Report card / exam not found.');

        $examRes = $this->calculateStudentRankAndResult($id, $record->class_id, $record->section_id, $student->id);
        if (!$examRes) abort(404, 'No marks entered for this report card.');

        $classModel = ClassModel::findOrFail($record->class_id);
        $sectionModel = Section::findOrFail($record->section_id);

        $examRes['attendance'] = $this->getStudentAttendanceStats($student->id);
        $examRes['school'] = $school;
        $examRes['exam'] = $exam;
        $examRes['exam2'] = null;
        $examRes['class'] = $classModel;
        $examRes['section'] = $sectionModel;
        $examRes['academic_year'] = $currentYear;

        $grades = GradeScale::where('school_id', $school->id)->where('status', 'active')->orderBy('min_percentage', 'desc')->get();
        $logoBase64 = $this->getLogoBase64($school->logo);
        $templateKey = $school->default_report_card_template ?: 'basic';

        $pdfData = [
            'reportCards' => [$examRes],
            'templateKey' => $templateKey,
            'printDate' => Carbon::now()->format('d M Y'),
            'grades' => $grades,
            'logo_base64' => $logoBase64,
        ];

        $pdf = Pdf::loadView('reports.' . $templateKey, $pdfData);
        return $pdf->stream('Report_Card_' . str_replace(' ', '_', $student->first_name . '_' . $student->last_name) . '.pdf');
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
        ];

        $pdf = Pdf::loadView('reports.fee_receipt', $pdfData);
        return $pdf->stream('Receipt_' . $receipt->receipt_number . '.pdf');
    }

    /* Helpers */

    private function calculateStudentRankAndResult($examId, $classId, $sectionId, $studentId): ?array
    {
        $records = StudentAcademicRecord::with(['student'])
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->whereHas('student', function ($q) {
                $q->where('is_delete', 0)->where('status', 'active');
            })->get();

        $sectionResults = $this->calculateSectionResults($examId, $classId, $sectionId, $records);
        foreach ($sectionResults as $res) {
            if ($res['student_id'] == $studentId) return $res;
        }
        return null;
    }

    private function calculateSectionResults($examId, $classId, $sectionId, $records): array
    {
        $academicYearId = request()->attributes->get('academic_year_id');
        $schoolId = request()->attributes->get('school_id');

        $optionalSubjectsMap = \Illuminate\Support\Facades\DB::table('student_optional_subjects')
            ->where('academic_year_id', $academicYearId)
            ->whereIn('student_id', $records->pluck('student_id')->toArray())
            ->get()->groupBy('student_id')
            ->map(function ($items) { return $items->pluck('subject_id')->toArray(); });

        $schedules = ExamSchedule::with(['subject'])
            ->where('exam_id', $examId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('is_delete', 0)->get();

        $marks = ExamMark::where('exam_id', $examId)
            ->where('academic_year_id', $academicYearId)
            ->get()->groupBy('student_id');

        $grades = GradeScale::where('school_id', $schoolId)->where('status', 'active')->orderBy('min_percentage', 'desc')->get();

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
                if ($subj->is_optional) {
                    $chosenSubjects = $optionalSubjectsMap->get($student->id) ?? [];
                    if (!in_array($subj->id, $chosenSubjects)) continue;
                }
                $visibility = $sched->report_card_visibility ?? 'included_in_result';
                if ($visibility === 'hidden') continue;

                $mark = $studentMarks->firstWhere('subject_id', $subj->id);
                $obtained = ($mark && $mark->is_absent) ? 'Ab' : ($mark ? $mark->marks_obtained : null);
                if ($mark) $hasMarksEntry = true;

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
                    if ($obtained !== null && $obtained !== 'Ab') {
                        $subjMax = $sched->max_marks ?: 100;
                        $pct = ($obtained / $subjMax) * 100;
                        $scaleToUse = (!empty($subj->grade_scale_id) && is_array($subj->grade_scale_id))
                            ? GradeScale::whereIn('id', $subj->grade_scale_id)->get() : $grades;
                        $matched = $scaleToUse->where('min_percentage', '<=', $pct)->sortByDesc('min_percentage')->first();
                        if ($matched) $gradeName = $matched->grade;
                    }
                }

                $passedSubj = true;
                if ($mark && $mark->is_absent) {
                    $passedSubj = false;
                    if ($visibility === 'included_in_result') $passedAllMandatory = false;
                } elseif ($subj->evaluation_type === 'marks' && $obtained !== null && $obtained !== 'Ab') {
                    $passCriteria = $subj->passing_marks ?: 35;
                    if ($obtained < $passCriteria) {
                        $passedSubj = false;
                        if ($visibility === 'included_in_result') $passedAllMandatory = false;
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
                    if ($obtained !== null && $obtained !== 'Ab') $totalObtainedMarks += (float)$obtained;
                }

                if ($subj->subject_category === 'co_scholastic') {
                    $coScholasticSubjects[] = $subjData;
                } else {
                    $scholasticSubjects[] = $subjData;
                }

                $subjectDetails[] = $subjData;
            }

            $percentage = null;
            $overallGrade = '-';
            if ($hasMarksEntry) {
                $percentage = $totalMaxMarks > 0 ? round(($totalObtainedMarks / $totalMaxMarks) * 100, 2) : 0;
                $matched = $grades->where('min_percentage', '<=', $percentage)->sortByDesc('min_percentage')->first();
                if ($matched) $overallGrade = $matched->grade;
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
                'rank' => 0,
            ];
        }

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
            if ($prevMarks !== null && $res['total_obtained_marks'] < $prevMarks) $rank = $index + 1;
            $res['rank'] = $rank;
            $prevMarks = $res['total_obtained_marks'];
        }
        unset($res);

        usort($calculated, function ($a, $b) {
            $rA = $a['rank'];
            $rB = $b['rank'];
            if ($rA === '-' && $rB === '-') return $a['roll_no'] <=> $b['roll_no'];
            if ($rA === '-') return 1;
            if ($rB === '-') return -1;
            if ($rA === $rB) return $a['roll_no'] <=> $b['roll_no'];
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
