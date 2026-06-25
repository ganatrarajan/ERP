<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\StudentAcademicRecord;
use App\Models\StudentOptionalSubject;
use App\Models\School;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class AdmitCardController extends Controller
{
    public function downloadPdf(Request $request)
    {
        $this->authorize('exam_schedule.view');

        $request->validate([
            'academic_year_id' => 'required|integer',
            'exam_id' => 'required|integer',
            'class_id' => 'required|integer',
            'section_id' => 'required|integer',
            'student_id' => 'nullable|integer',
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
        $classId = $request->class_id;
        $sectionId = $request->section_id;
        $studentId = $request->student_id;
        $includeGraded = $request->input('include_graded') === 'yes';

        // Fetch students
        $recordsQuery = StudentAcademicRecord::with(['student', 'student.parent'])
            ->where('academic_year_id', $academicYearId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('status', 'active')
            ->where('school_id', $schoolId);

        if ($studentId) {
            $recordsQuery->where('student_id', $studentId);
        }

        $records = $recordsQuery->whereHas('student', function ($q) {
            $q->where('is_delete', 0)->where('status', 'active');
        })->get();

        if ($records->isEmpty()) {
            return response()->json(['message' => 'No student records found.'], 404);
        }

        // Fetch all exam schedules for this exam/class/section
        $schedules = ExamSchedule::with(['subject'])
            ->where('exam_id', $examId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('is_delete', 0)
            ->orderBy('exam_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        if ($schedules->isEmpty()) {
            return response()->json(['message' => 'No exam schedules found. Please configure schedules first.'], 422);
        }

        $exam = Exam::findOrFail($examId);
        $classModel = \App\Models\ClassModel::findOrFail($classId);
        $sectionModel = \App\Models\Section::findOrFail($sectionId);
        $academicYear = AcademicYear::findOrFail($academicYearId);

        // Fetch optional subjects mapping for these students in this academic year
        $optionalSubjectsMap = \Illuminate\Support\Facades\DB::table('student_optional_subjects')
            ->where('academic_year_id', $academicYearId)
            ->whereIn('student_id', $records->pluck('student_id')->toArray())
            ->get()
            ->groupBy('student_id')
            ->map(function ($items) {
                return $items->pluck('subject_id')->toArray();
            });

        // Prep logo base64
        $logoBase64 = $this->getLogoBase64($school->logo);

        $admitCards = [];
        foreach ($records as $record) {
            $student = $record->student;

            // Filter schedules: student should see all regular subjects + optional subjects they are enrolled in
            $studentSchedules = [];
            foreach ($schedules as $sched) {
                $subj = $sched->subject;
                if (!$includeGraded && $subj->evaluation_type === 'grades') {
                    continue;
                }
                if ($subj->is_optional) {
                    $chosenSubjects = $optionalSubjectsMap->get($student->id) ?? [];
                    if (!in_array($subj->id, $chosenSubjects)) {
                        continue;
                    }
                }
                $studentSchedules[] = [
                    'subject_name' => $subj->name . ($subj->is_optional ? ' (Optional)' : ''),
                    'subject_code' => $subj->code,
                    'exam_date' => Carbon::parse($sched->exam_date)->format('d M Y (D)'),
                    'start_time' => Carbon::parse($sched->start_time)->format('h:i A'),
                    'end_time' => Carbon::parse($sched->end_time)->format('h:i A'),
                    'max_marks' => $sched->max_marks,
                ];
            }

            $fatherName = $student->parent ? $student->parent->father_name : 'N/A';

            $admitCards[] = [
                'student' => $student,
                'father_name' => $fatherName,
                'roll_no' => $record->roll_no,
                'admission_no' => $student->admission_no,
                'schedules' => $studentSchedules,
            ];
        }

        $pdfData = [
            'school' => $school,
            'exam' => $exam,
            'class' => $classModel,
            'section' => $sectionModel,
            'academic_year' => $academicYear,
            'admitCards' => $admitCards,
            'logo_base64' => $logoBase64,
            'printDate' => Carbon::now()->format('d M Y'),
        ];

        $pdf = Pdf::loadView('reports.admit_card', $pdfData);
        $filename = 'Admit_Cards_' . str_replace(' ', '_', $classModel->name . '_' . $sectionModel->name) . '.pdf';
        return $pdf->stream($filename);
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
