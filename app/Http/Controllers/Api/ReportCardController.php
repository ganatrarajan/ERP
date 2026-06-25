<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\ExamMark;
use App\Models\Student;
use App\Models\StudentAcademicRecord;
use App\Models\Subject;
use App\Models\GradeScale;
use App\Models\School;
use App\Models\Attendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportCardController extends Controller
{
    public function getStudentsReportStatus(Request $request): JsonResponse
    {
        $this->authorize('result.view');

        $request->validate([
            'academic_year_id' => 'required|integer',
            'exam_id' => 'required|integer',
            'exam_id_2' => 'nullable|integer',
            'class_id' => 'required|integer',
            'section_id' => 'required|integer',
            'include_graded' => 'nullable|string|in:yes,no',
        ]);

        $user = $request->user();
        $schoolId = $user->isSuperAdmin()
            ? \App\Models\ClassModel::where('id', $request->class_id)->value('school_id')
            : $user->school_id;

        $academicYearId = $request->academic_year_id;
        $examId = $request->exam_id;
        $classId = $request->class_id;
        $sectionId = $request->section_id;
        $includeGraded = $request->input('include_graded', 'no');

        // Fetch students
        $recordsQuery = StudentAcademicRecord::with(['student'])
            ->where('academic_year_id', $academicYearId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('status', 'active');
        if (!$user->isSuperAdmin()) {
            $recordsQuery->where('school_id', $schoolId);
        }
        $records = $recordsQuery->whereHas('student', function ($q) {
            $q->where('is_delete', 0)->where('status', 'active');
        })->get();

        // Calculate results for all students in the section to rank them
        $results = $this->calculateSectionResults($schoolId, $academicYearId, $examId, $classId, $sectionId, $records, $request->exam_id_2, $includeGraded);

        return response()->json([
            'results' => $results
        ]);
    }

    public function downloadPdf(Request $request)
    {
        $this->authorize('report_card.view');

        $request->validate([
            'academic_year_id' => 'required|integer',
            'exam_id' => 'required|integer',
            'exam_id_2' => 'nullable|integer',
            'class_id' => 'required|integer',
            'section_id' => 'required|integer',
            'student_id' => 'nullable|integer',
            'template' => 'nullable|string|in:basic,detailed,cbse',
            'include_graded' => 'nullable|string|in:yes,no',
        ]);

        $user = $request->user();
        $schoolId = $user->isSuperAdmin()
            ? \App\Models\ClassModel::where('id', $request->class_id)->value('school_id')
            : $user->school_id;

        if (!$schoolId) {
            return response()->json(['message' => 'School ID could not be determined.'], 422);
        }

        $school = School::findOrFail($schoolId);
        $academicYearId = $request->academic_year_id;
        $examId = $request->exam_id;
        $examId2 = $request->exam_id_2;
        $classId = $request->class_id;
        $sectionId = $request->section_id;
        $studentId = $request->student_id;
        $includeGraded = $request->input('include_graded', 'no');

        // Determine template key
        $templateKey = $request->input('template') ?: ($school->default_report_card_template ?: 'basic');

        // Fetch students
        $recordsQuery = StudentAcademicRecord::with(['student'])
            ->where('academic_year_id', $academicYearId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('status', 'active')
            ->where('school_id', $schoolId);
        
        if ($studentId) {
            $recordsQuery->where('student_id', $studentId);
        }

        $records = $recordsQuery->whereHas('student', function ($q) {
            $q->where('is_delete', 0);
        })->get();

        if ($records->isEmpty()) {
            return response()->json(['message' => 'No student records found.'], 404);
        }

        // Calculate section results (needed for rankings)
        $allRecordsQuery = StudentAcademicRecord::with(['student'])
            ->where('academic_year_id', $academicYearId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('status', 'active')
            ->where('school_id', $schoolId);
        $allRecords = $allRecordsQuery->get();

        $sectionResults = $this->calculateSectionResults($schoolId, $academicYearId, $examId, $classId, $sectionId, $allRecords, $examId2, $includeGraded);
        $resultsByKey = collect($sectionResults)->keyBy('student_id');

        $exam = Exam::with('examType')->findOrFail($examId);
        $exam2 = $examId2 ? Exam::with('examType')->findOrFail($examId2) : null;
        $classModel = \App\Models\ClassModel::findOrFail($classId);
        $sectionModel = \App\Models\Section::findOrFail($sectionId);
        $academicYear = \App\Models\AcademicYear::findOrFail($academicYearId);

        // Prep data for PDF view
        $reportCards = [];
        foreach ($records as $record) {
            $res = $resultsByKey->get($record->student_id);
            if ($res) {
                // Fetch attendance summary
                $attendanceStats = $this->getStudentAttendanceStats($schoolId, $academicYearId, $record->student_id);
                $res['attendance'] = $attendanceStats;
                $res['school'] = $school;
                $res['exam'] = $exam;
                $res['exam2'] = $exam2;
                $res['class'] = $classModel;
                $res['section'] = $sectionModel;
                $res['academic_year'] = $academicYear;
                $reportCards[] = $res;
            }
        }

        $grades = GradeScale::where('school_id', $schoolId)
            ->where('status', 'active')
            ->orderBy('min_percentage', 'desc')
            ->get();

        $logoBase64 = $this->getLogoBase64($school->logo);

        $pdfData = [
            'reportCards' => $reportCards,
            'templateKey' => $templateKey,
            'printDate' => Carbon::now()->format('d M Y'),
            'grades' => $grades,
            'logo_base64' => $logoBase64,
        ];

        $pdf = Pdf::loadView('reports.' . $templateKey, $pdfData);

        $filename = 'Report_Cards_' . str_replace(' ', '_', $classModel->name . '_' . $sectionModel->name) . '.pdf';
        if ($studentId && count($reportCards) === 1) {
            $student = $reportCards[0]['student'];
            $filename = 'Report_Card_' . str_replace(' ', '_', $student->first_name . '_' . $student->last_name) . '.pdf';
        }

        return $pdf->stream($filename);
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
                            $totalObtainedMarks += $obtained;
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
                            $totalObt1 += $obt1;
                        }
                    }
                    if ($sched2 && $vis2 === 'included_in_result' && $subj->evaluation_type === 'marks') {
                        $totalMax2 += $max2;
                        if ($obt2 !== null && $obt2 !== 'Ab') {
                            $totalObt2 += $obt2;
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

    private function getStudentAttendanceStats($schoolId, $academicYearId, $studentId): array
    {
        $atts = Attendance::where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('is_delete', 0)
            ->get();

        $totalDays = $atts->count();
        $presentDays = $atts->whereIn('status', ['Present', 'Late', 'Half Day'])->count();

        return [
            'total_days' => $totalDays,
            'present_days' => $presentDays,
            'attendance_rate' => $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 100,
        ];
    }

    private function getLogoBase64($logoUrl)
    {
        if (!$logoUrl) {
            return null;
        }

        if (str_starts_with($logoUrl, 'data:image')) {
            return $logoUrl;
        }

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
