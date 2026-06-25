<?php

if (!function_exists('schoolHasModule')) {
    /**
     * Check whether the logged-in user's school has the requested module enabled.
     * Super Admin bypasses module restrictions.
     *
     * @param string $moduleSlug
     * @return bool
     */
    function schoolHasModule(string $moduleSlug): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // Super Admin bypasses all module restrictions
        if ($user->isSuperAdmin()) {
            return true;
        }

        $schoolId = $user->school_id;
        if (!$schoolId) {
            return false;
        }

        // Request-level cache of active modules per school to avoid redundant DB queries
        static $schoolModules = [];

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

        return in_array(strtolower($moduleSlug), $schoolModules[$schoolId]);
    }
}
