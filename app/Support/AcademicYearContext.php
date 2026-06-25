<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\AcademicYear;

class AcademicYearContext
{
    public static function setWebAcademicYearId(?int $id): void
    {
        request()->attributes->set('web_academic_year_id', $id);
        // Clear active academic year cache to force re-evaluation
        request()->attributes->remove('active_academic_year_id');
    }

    public static function getWebAcademicYearId(): ?int
    {
        if (request()->attributes->has('web_academic_year_id')) {
            return request()->attributes->get('web_academic_year_id');
        }

        $academicYearId = null;

        // Try getting from session first
        if (request()->hasSession() && request()->session()->has('selected_academic_year_id')) {
            $academicYearId = (int) request()->session()->get('selected_academic_year_id');
        } else {
            // Fall back to database
            $user = Auth::user();
            if ($user) {
                $selection = DB::table('user_academic_selections')->where('user_id', $user->id)->first();
                if ($selection) {
                    $academicYearId = (int) $selection->academic_year_id;
                    // Cache in session
                    if (request()->hasSession()) {
                        request()->session()->put('selected_academic_year_id', $academicYearId);
                    }
                }
            }
        }

        request()->attributes->set('web_academic_year_id', $academicYearId);
        return $academicYearId;
    }

    public static function getActiveAcademicYearId(int $schoolId): ?int
    {
        if (request()->attributes->has('active_academic_year_id')) {
            return request()->attributes->get('active_academic_year_id');
        }

        $selectedId = self::getWebAcademicYearId();
        if ($selectedId) {
            $exists = AcademicYear::withoutGlobalScope('web_erp_filter')
                ->where('id', $selectedId)
                ->where('school_id', $schoolId)
                ->where('is_delete', 0)
                ->where('status', 'active')
                ->exists();
            if ($exists) {
                request()->attributes->set('active_academic_year_id', $selectedId);
                return $selectedId;
            }
        }

        $activeYearId = AcademicYear::withoutGlobalScope('web_erp_filter')
            ->where('school_id', $schoolId)
            ->where('is_current', true)
            ->where('is_delete', 0)
            ->where('status', 'active')
            ->value('id') 
            ?? AcademicYear::withoutGlobalScope('web_erp_filter')
            ->where('school_id', $schoolId)
            ->where('is_delete', 0)
            ->where('status', 'active')
            ->value('id');

        request()->attributes->set('active_academic_year_id', $activeYearId);
        return $activeYearId;
    }
}
