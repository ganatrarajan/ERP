<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WeekendSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WeekendSettingController extends Controller
{
    /**
     * Display a listing of weekend settings.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('settings.view');

        $schoolId = $request->user()->school_id;
        $query = WeekendSetting::with(['class', 'section'])
            ->where('school_id', $schoolId);

        if ($request->filled('academic_year_id')) {
            $academicYearId = $request->input('academic_year_id');
            $query->where(function ($q) use ($academicYearId) {
                $q->where('target_type', 'all')
                  ->orWhere(function ($q2) use ($academicYearId) {
                      $q2->where('target_type', 'class_section')
                         ->whereHas('class', function ($q3) use ($academicYearId) {
                             $q3->where('academic_year_id', $academicYearId);
                         });
                  });
            });
        }

        $settings = $query->get();

        return response()->json(['settings' => $settings]);
    }

    /**
     * Store a newly created weekend setting.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('settings.edit');

        $request->validate([
            'target_type' => 'required|in:all,class_section',
            'class_id' => 'required_if:target_type,class_section|nullable|integer|exists:classes,id',
            'section_id' => 'nullable|integer|exists:sections,id',
        ]);

        $schoolId = $request->user()->school_id;
        $targetType = $request->input('target_type');
        $classId = $request->input('class_id');
        $sectionId = $request->input('section_id');

        if ($targetType === 'all') {
            DB::transaction(function () use ($schoolId) {
                // Remove existing Saturday settings to avoid conflicts
                WeekendSetting::where('school_id', $schoolId)
                    ->where('day_name', 'Saturday')
                    ->delete();

                // Create school-wide holiday setting for Saturday
                WeekendSetting::create([
                    'school_id' => $schoolId,
                    'day_name' => 'Saturday',
                    'is_holiday' => true,
                    'target_type' => 'all',
                ]);
            });
        } else {
            // target_type === 'class_section'
            // Ensure Saturday holiday isn't already set for this class and section
            $exists = WeekendSetting::where('school_id', $schoolId)
                ->where('day_name', 'Saturday')
                ->where('target_type', 'class_section')
                ->where('class_id', $classId)
                ->where('section_id', $sectionId)
                ->exists();

            if (!$exists) {
                WeekendSetting::create([
                    'school_id' => $schoolId,
                    'day_name' => 'Saturday',
                    'is_holiday' => true,
                    'target_type' => 'class_section',
                    'class_id' => $classId,
                    'section_id' => $sectionId,
                ]);
            }
        }

        $settings = WeekendSetting::with(['class', 'section'])
            ->where('school_id', $schoolId)
            ->get();

        return response()->json([
            'message' => 'Weekend holiday settings saved successfully.',
            'settings' => $settings
        ]);
    }

    /**
     * Remove the specified weekend setting.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $this->authorize('settings.edit');

        $schoolId = $request->user()->school_id;
        $setting = WeekendSetting::where('id', $id)
            ->where('school_id', $schoolId)
            ->firstOrFail();

        $setting->delete();

        $settings = WeekendSetting::with(['class', 'section'])
            ->where('school_id', $schoolId)
            ->get();

        return response()->json([
            'message' => 'Weekend setting removed successfully.',
            'settings' => $settings
        ]);
    }
}
