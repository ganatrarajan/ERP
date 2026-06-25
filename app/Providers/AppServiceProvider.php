<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Implicitly grant "Super Admin" role or impersonated Super Admin session all permissions
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('Super Admin') || $user->isImpersonated()) {
                return true;
            }

            $schoolId = $user->school_id;
            if (!$schoolId) {
                return null;
            }

            $prefix = explode('.', $ability)[0];

            $modulePermissionMap = [
                'student' => 'students',
                'promotion' => 'students',
                'academic_year' => 'academics',
                'class' => 'academics',
                'section' => 'academics',
                'teacher' => 'teachers',
                'attendance' => 'attendance',
                'homework' => 'homework',
                'fees' => 'fees',
                'exam' => 'examinations',
                'exam_schedule' => 'examinations',
                'marks' => 'examinations',
                'result' => 'examinations',
                'report_card' => 'examinations',
                'subject' => 'subjects',
                'notice' => 'notices',
            ];

            if (isset($modulePermissionMap[$prefix])) {
                $requiredModule = $modulePermissionMap[$prefix];
                
                static $schoolModules = [];
                
                // Clear cache if we are running unit tests to ensure isolation
                if (app()->runningUnitTests()) {
                    $schoolModules = [];
                }

                if (!isset($schoolModules[$schoolId])) {
                    $schoolModules[$schoolId] = \Illuminate\Support\Facades\DB::table('school_modules')
                        ->join('modules', 'modules.id', '=', 'school_modules.module_id')
                        ->where('school_modules.school_id', $schoolId)
                        ->where('school_modules.is_active', true)
                        ->where('modules.status', 'active')
                        ->pluck('modules.slug')
                        ->toArray();
                }

                if (!in_array($requiredModule, $schoolModules[$schoolId])) {
                    return false;
                }
            }

            return null;
        });
    }
}
