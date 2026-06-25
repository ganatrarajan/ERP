<?php

namespace App\Services;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;

class SchoolService
{
    /**
     * Create a school along with its School Admin.
     */
    public function createSchool(array $data): School
    {
        return DB::transaction(function () use ($data) {
            // Create the school record
            $school = School::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'logo' => $data['logo'] ?? null,
                'address' => $data['address'] ?? null,
                'status' => $data['status'] ?? 'active',
                'default_report_card_template' => $data['default_report_card_template'] ?? 'basic',
            ]);

            // Sync selected modules
            $moduleIds = $data['module_ids'] ?? [];
            $allModules = \App\Models\Module::all();
            $syncData = [];
            foreach ($allModules as $module) {
                $syncData[$module->id] = ['is_active' => in_array($module->id, $moduleIds)];
            }
            $school->modules()->sync($syncData);

            // Create the School Admin user
            $schoolAdmin = User::create([
                'school_id' => $school->id,
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'mobile' => $data['admin_mobile'] ?? null,
                'password' => Hash::make($data['admin_password']),
                'status' => 'active',
            ]);

            // Create default roles for the new school
            $schoolAdminRole = \App\Models\Role::firstOrCreate([
                'name' => 'School Admin',
                'guard_name' => 'web',
                'school_id' => $school->id,
            ]);

            // Sync School Admin permissions based on selected modules
            $activeModuleSlugs = \App\Models\Module::whereIn('id', $moduleIds)
                ->where('status', 'active')
                ->pluck('slug')
                ->toArray();

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

            $permissionsToSync = array_intersect(
                $permissionsToSync,
                \Spatie\Permission\Models\Permission::pluck('name')->toArray()
            );

            $schoolAdminRole->syncPermissions($permissionsToSync);

            \App\Models\Role::firstOrCreate([
                'name' => 'Teacher',
                'guard_name' => 'web',
                'school_id' => $school->id,
            ])->syncPermissions([
                'dashboard.view',
                'academic_year.view',
                'class.view',
                'section.view',
                'student.view',
                // examinations
                'exam.view',
                'exam_schedule.view',
                'marks.view',
                'marks.create',
                'marks.edit',
                'report_card.view',
            ]);

            // Assign the School Admin role
            $schoolAdmin->assignRole($schoolAdminRole);

            return $school;
        });
    }

    /**
     * Update school profile or details.
     */
    public function updateSchool(School $school, array $data): School
    {
        $school->update($data);
        return $school;
    }

    /**
     * Delete a school.
     */
    public function deleteSchool(School $school): void
    {
        $school->delete();
    }
}
