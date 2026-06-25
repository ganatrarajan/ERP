<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AcademicYear;

class EnforceActiveAcademicYear
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Bypass active year validation for student promotions or session switching
        if ($request->is('api/promotions') || $request->is('api/promotions/*') || $request->is('api/auth/academic-year') || $request->is('auth/academic-year')) {
            return $next($request);
        }

        // Only enforce on mutations (POST, PUT, PATCH, DELETE)
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $academicYearId = $this->getAcademicYearIdFromRequest($request);

            if ($academicYearId) {
                $academicYear = AcademicYear::find($academicYearId);
                if ($academicYear && !$academicYear->is_current) {
                    return response()->json([
                        'message' => 'This academic session is locked. Modifications are only allowed in the active session.'
                    ], 422);
                }
            }
        }

        return $next($request);
    }

    /**
     * Resolve the target academic year ID from the request.
     */
    private function getAcademicYearIdFromRequest(Request $request): ?int
    {
        // 1. Direct inputs in payload
        $keys = ['academic_year_id', 'from_academic_year_id', 'to_academic_year_id'];
        foreach ($keys as $key) {
            if ($request->has($key)) {
                $val = $request->input($key);
                if (is_numeric($val)) {
                    return (int) $val;
                }
            }
        }

        // 2. Class ID in payload
        if ($request->has('class_id') && is_numeric($request->input('class_id'))) {
            $classModel = \App\Models\ClassModel::find($request->input('class_id'));
            if ($classModel) {
                return (int) $classModel->academic_year_id;
            }
        }

        // 3. Exam ID in payload
        if ($request->has('exam_id') && is_numeric($request->input('exam_id'))) {
            $exam = \App\Models\Exam::find($request->input('exam_id'));
            if ($exam) {
                return (int) $exam->academic_year_id;
            }
        }

        // 4. Exam Schedule ID in payload
        if ($request->has('exam_schedule_id') && is_numeric($request->input('exam_schedule_id'))) {
            $schedule = \App\Models\ExamSchedule::find($request->input('exam_schedule_id'));
            if ($schedule) {
                return (int) $schedule->academic_year_id;
            }
        }

        // 5. Subject ID in payload
        if ($request->has('subject_id') && is_numeric($request->input('subject_id'))) {
            $subject = \App\Models\Subject::find($request->input('subject_id'));
            if ($subject) {
                return (int) $subject->academic_year_id;
            }
        }

        // 6. Route parameter bindings
        $route = $request->route();
        if ($route) {
            foreach ($route->parameters() as $parameter) {
                if (is_object($parameter) && $parameter instanceof \Illuminate\Database\Eloquent\Model) {
                    $model = $parameter;

                    // AcademicYear model itself
                    if ($model instanceof AcademicYear) {
                        // If we are setting is_current = true (activating it), we don't lock.
                        if ($request->has('is_current') && ($request->input('is_current') === true || $request->input('is_current') == 1)) {
                            continue;
                        }
                        return (int) $model->id;
                    }

                    // Direct attribute check
                    if (isset($model->academic_year_id)) {
                        return (int) $model->academic_year_id;
                    }
                    if (isset($model->from_academic_year_id)) {
                        return (int) $model->from_academic_year_id;
                    }

                    // Check academicYear relation method
                    if (method_exists($model, 'academicYear')) {
                        $rel = $model->academicYear;
                        if ($rel) {
                            return (int) $rel->id;
                        }
                    }

                    // Check class relation method (for Section, StudentAcademicRecord, etc.)
                    if (method_exists($model, 'class')) {
                        $cls = $model->class;
                        if ($cls && isset($cls->academic_year_id)) {
                            return (int) $cls->academic_year_id;
                        }
                    }

                    // Check exam relation method (for ExamSchedule, ExamMark, etc.)
                    if (method_exists($model, 'exam')) {
                        $exm = $model->exam;
                        if ($exm && isset($exm->academic_year_id)) {
                            return (int) $exm->academic_year_id;
                        }
                    }
                }
            }
        }

        return null;
    }
}
