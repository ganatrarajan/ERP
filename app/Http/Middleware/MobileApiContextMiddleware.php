<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class MobileApiContextMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $token = $user->currentAccessToken();
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $schoolId = null;

        // Extract context from token abilities
        if (is_array($token->abilities)) {
            foreach ($token->abilities as $ability) {
                if (str_starts_with($ability, 'school:')) {
                    $schoolId = explode(':', $ability)[1] ?? null;
                }
            }
        }

        // Fallback to student's school_id if not present in token
        if (!$schoolId && $user->school_id) {
            $schoolId = $user->school_id;
        }

        if (!$schoolId) {
            return response()->json(['success' => false, 'message' => 'Invalid or missing session context.'], 403);
        }

        // Fetch mobile academic year dynamically from the school settings
        $school = \App\Models\School::find($schoolId);
        $academicYearId = $school ? $school->mobile_academic_year_id : null;

        // Fallback to school's default active academic year if not configured yet
        if (!$academicYearId) {
            $academicYearId = \App\Models\AcademicYear::where('school_id', $schoolId)
                ->where('is_current', true)
                ->where('is_delete', 0)
                ->value('id') ?? \App\Models\AcademicYear::where('school_id', $schoolId)
                ->where('is_delete', 0)
                ->value('id');
        }

        if (!$academicYearId) {
            return response()->json(['success' => false, 'message' => 'Active academic year not found for this school.'], 403);
        }

        // Store in request for easy access if needed
        $request->attributes->add([
            'school_id' => $schoolId,
            'academic_year_id' => $academicYearId,
        ]);

        // Apply Global Scopes to relevant models
        $models = [
            \App\Models\Notice::class => ['school_id', 'status', 'is_delete'],
            \App\Models\Homework::class => ['school_id', 'academic_year_id', 'status', 'is_delete'],
            \App\Models\Attendance::class => ['school_id', 'academic_year_id', 'is_delete'],
            \App\Models\StudentAcademicRecord::class => ['school_id', 'academic_year_id', 'status'],
            \App\Models\Exam::class => ['school_id', 'academic_year_id', 'is_delete'],
            \App\Models\FeeReceipt::class => ['school_id'],
            \App\Models\FeeCollection::class => ['school_id', 'academic_year_id'],
        ];

        foreach ($models as $model => $columns) {
            if (class_exists($model)) {
                $model::addGlobalScope('mobile_filter', function ($builder) use ($schoolId, $academicYearId, $columns, $model) {
                    $table = (new $model)->getTable();
                    
                    if (in_array('school_id', $columns)) {
                        $builder->where("$table.school_id", $schoolId);
                    }
                    if (in_array('academic_year_id', $columns)) {
                        $builder->where("$table.academic_year_id", $academicYearId);
                    }
                    if (in_array('status', $columns)) {
                        $builder->where(function ($q) use ($table) {
                            $q->where("$table.status", 'active')
                              ->orWhere("$table.status", 1)
                              ->orWhere("$table.status", '1');
                        });
                    }
                    if (in_array('is_delete', $columns) || in_array('is_deleted', $columns)) {
                        $builder->where(function ($q) use ($table) {
                            $q->where("$table.is_delete", 0)
                              ->orWhere("$table.is_delete", false);
                        });
                    }
                });
            }
        }

        return $next($request);
    }
}
