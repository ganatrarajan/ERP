<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Module;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchoolModuleController extends Controller
{
    /**
     * Display a listing of modules with active status for a specific school.
     */
    public function index(School $school): JsonResponse
    {
        // Only Super Admin can view/manage school modules
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $query = Module::query()->where('status', 'active');
        if (\Illuminate\Support\Facades\Schema::hasColumn('modules', 'is_delete')) {
            $query->where('is_delete', 0);
        }
        $allModules = $query->get();
        $schoolModules = $school->modules()->get()->keyBy('id');

        $modulesData = $allModules->map(function ($module) use ($schoolModules) {
            $pivot = $schoolModules->get($module->id);
            return [
                'id' => $module->id,
                'name' => $module->name,
                'slug' => $module->slug,
                'icon' => $module->icon,
                'description' => $module->description,
                'is_active' => $pivot ? (bool) $pivot->pivot->is_active : false,
            ];
        });

        return response()->json([
            'modules' => $modulesData
        ]);
    }

    /**
     * Update the school's enabled modules.
     */
    public function update(Request $request, School $school): JsonResponse
    {
        // Only Super Admin can manage school modules
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'module_ids' => 'nullable|array',
            'module_ids.*' => 'integer|exists:modules,id',
        ]);

        $moduleIds = $request->input('module_ids', []);

        $query = Module::query()->where('status', 'active');
        if (\Illuminate\Support\Facades\Schema::hasColumn('modules', 'is_delete')) {
            $query->where('is_delete', 0);
        }
        $allModules = $query->get();
        $syncData = [];
        
        foreach ($allModules as $module) {
            $isActive = in_array($module->id, $moduleIds);
            $syncData[$module->id] = ['is_active' => $isActive];
        }

        $school->modules()->sync($syncData);

        // Sync School Admin permissions based on active modules!
        $activeModuleSlugs = $school->modules()
            ->wherePivot('is_active', true)
            ->pluck('slug')
            ->toArray();

        $schoolAdminRole = \App\Models\Role::where('school_id', $school->id)
            ->where('name', 'School Admin')
            ->first();

        if ($schoolAdminRole) {
            $basePermissions = [
                'dashboard.view',
                'user.view', 'user.create', 'user.edit', 'user.delete',
                'role.view', 'role.edit',
                'permission.view', 'permission.edit',
                'settings.view', 'settings.edit'
            ];

            $modulePermissionsMap = [
                'academics' => [
                    'academic_year.view', 'academic_year.create', 'academic_year.edit', 'academic_year.delete',
                    'class.view', 'class.create', 'class.edit', 'class.delete',
                    'section.view', 'section.create', 'section.edit', 'section.delete'
                ],
                'students' => [
                    'student.view', 'student.create', 'student.edit', 'student.delete',
                    'promotion.view', 'promotion.create'
                ],
                'subjects' => [
                    'subject.view', 'subject.create', 'subject.edit', 'subject.delete'
                ],
                'attendance' => [
                    'attendance.view', 'attendance.create', 'attendance.edit', 'attendance.delete'
                ],
                'homework' => [
                    'homework.view', 'homework.create', 'homework.edit', 'homework.delete'
                ],
                'notices' => [
                    'notice.view', 'notice.create', 'notice.edit', 'notice.delete'
                ],
                'examinations' => [
                    'exam.view', 'exam.create', 'exam.edit', 'exam.delete',
                    'exam_schedule.view', 'exam_schedule.create', 'exam_schedule.edit', 'exam_schedule.delete',
                    'marks.view', 'marks.create', 'marks.edit',
                    'result.view', 'report_card.view'
                ],
                'fees' => [
                    'fee_type.view', 'fee_type.create', 'fee_type.edit', 'fee_type.delete',
                    'fee_structure.view', 'fee_structure.create', 'fee_structure.edit', 'fee_structure.delete',
                    'fee_collection.view', 'fee_collection.create', 'fee_collection.edit',
                    'receipt.view', 'ledger.view', 'report.view'
                ]
            ];

            $permissionsToSync = $basePermissions;
            foreach ($activeModuleSlugs as $slug) {
                if (isset($modulePermissionsMap[$slug])) {
                    $permissionsToSync = array_merge($permissionsToSync, $modulePermissionsMap[$slug]);
                }
            }

            // Clean up list (ensure permissions exist)
            $permissionsToSync = array_intersect(
                $permissionsToSync,
                \Spatie\Permission\Models\Permission::pluck('name')->toArray()
            );

            $schoolAdminRole->syncPermissions($permissionsToSync);
        }

        // Reset permission cache
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'message' => 'School modules updated successfully.',
            'modules' => $this->index($school)->getData()->modules
        ]);
    }

    /**
     * Get all modules in the system.
     */
    public function systemModules(): JsonResponse
    {
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json([
            'modules' => Module::query()
                ->where('status', 'active')
                ->when(\Illuminate\Support\Facades\Schema::hasColumn('modules', 'is_delete'), function ($q) {
                    $q->where('is_delete', 0);
                })
                ->get()
        ]);
    }
}
