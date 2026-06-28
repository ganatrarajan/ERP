<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\School;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions
        $permissions = [
            // School Management
            'school.view',
            'school.edit',

            // User Management
            'user.view',
            'user.create',
            'user.edit',
            'user.delete',

            // Role Management
            'role.view',
            'role.edit',

            // Permission Management
            'permission.view',
            'permission.edit',

            // Dashboard
            'dashboard.view',

            // Settings
            'settings.view',
            'settings.edit',

            // Academic Years
            'academic_year.view',
            'academic_year.switch',
            'academic_year.create',
            'academic_year.edit',
            'academic_year.delete',

            // Classes
            'class.view',
            'class.create',
            'class.edit',
            'class.delete',

            // Sections
            'section.view',
            'section.create',
            'section.edit',
            'section.delete',

            // Students
            'student.view',
            'student.create',
            'student.edit',
            'student.delete',

            // Promotions
            'promotion.view',
            'promotion.create',

            // Subjects
            'subject.view',
            'subject.create',
            'subject.edit',
            'subject.delete',

            // Attendance
            'attendance.view',
            'attendance.create',
            'attendance.edit',
            'attendance.delete',

            // Homework
            'homework.view',
            'homework.create',
            'homework.edit',
            'homework.delete',

            // Notices
            'notice.view',
            'notice.create',
            'notice.edit',
            'notice.delete',

            // Examination Module Permissions
            'exam.view',
            'exam.create',
            'exam.edit',
            'exam.delete',
            'exam_schedule.view',
            'exam_schedule.create',
            'exam_schedule.edit',
            'exam_schedule.delete',
            'marks.view',
            'marks.create',
            'marks.edit',
            'result.view',
            'report_card.view',
            'report_card_setup.manage',

            // Fees Module Permissions
            'fee_type.view',
            'fee_type.create',
            'fee_type.edit',
            'fee_type.delete',
            'fee_structure.view',
            'fee_structure.create',
            'fee_structure.edit',
            'fee_structure.delete',
            'fee_collection.view',
            'fee_collection.create',
            'fee_collection.edit',
            'receipt.view',
            'ledger.view',
            'report.view',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Clean up permissions not present in the active list
        Permission::whereNotIn('name', $permissions)->delete();

        // Create roles and assign permissions
        $superAdminRole = Role::findOrCreate('Super Admin', 'web');
        $superAdminRole->syncPermissions(Permission::all());

        $schoolAdminGlobalRole = Role::firstOrCreate([
            'name' => 'School Admin',
            'guard_name' => 'web',
            'school_id' => null,
        ]);
        $schoolAdminGlobalRole->update(['status' => 'active']);

        $teacherGlobalRole = Role::firstOrCreate([
            'name' => 'Teacher',
            'guard_name' => 'web',
            'school_id' => null,
        ]);
        $teacherGlobalRole->update(['status' => 'active']);
        // 1. Create Super Admin User
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@erp.com'],
            [
                'school_id' => null,
                'name' => 'Super Admin',
                'mobile' => '1112223333',
                'password' => Hash::make('Password123'),
                'status' => 'active',
            ]
        );
        $superAdmin->assignRole($superAdminRole);

        // 2. Create a Dummy School
        $school = School::firstOrCreate(
            ['email' => 'greenwood@erp.com'],
            [
                'name' => 'Greenwood High School',
                'phone' => '1234567890',
                'address' => '123 Education Lane, Greenwood City',
                'logo' => null,
                'status' => 'active',
            ]
        );

        // 3. Create school-scoped School Admin role
        $schoolAdminRole = Role::firstOrCreate([
            'name' => 'School Admin',
            'guard_name' => 'web',
            'school_id' => $school->id,
        ]);
        $schoolAdminRole->syncPermissions([
            'dashboard.view',
            'user.view',
            'user.create',
            'user.edit',
            'user.delete',
            'role.view',
            'role.edit',
            'permission.view',
            'permission.edit',
            'settings.view',
            'settings.edit',
            // Academic and student
            'academic_year.view',
            'academic_year.switch',
            'academic_year.create',
            'academic_year.edit',
            'academic_year.delete',
            'class.view',
            'class.create',
            'class.edit',
            'class.delete',
            'section.view',
            'section.create',
            'section.edit',
            'section.delete',
            'student.view',
            'student.create',
            'student.edit',
            'student.delete',
            'promotion.view',
            'promotion.create',
            // New modules
            'subject.view',
            'subject.create',
            'subject.edit',
            'subject.delete',
            'attendance.view',
            'attendance.create',
            'attendance.edit',
            'attendance.delete',
            'homework.view',
            'homework.create',
            'homework.edit',
            'homework.delete',
            'notice.view',
            'notice.create',
            'notice.edit',
            'notice.delete',
            // Examinations module
            'exam.view',
            'exam.create',
            'exam.edit',
            'exam.delete',
            'exam_schedule.view',
            'exam_schedule.create',
            'exam_schedule.edit',
            'exam_schedule.delete',
            'marks.view',
            'marks.create',
            'marks.edit',
            'result.view',
            'report_card.view',
            'report_card_setup.manage',
            // Fees module
            'fee_type.view',
            'fee_type.create',
            'fee_type.edit',
            'fee_type.delete',
            'fee_structure.view',
            'fee_structure.create',
            'fee_structure.edit',
            'fee_structure.delete',
            'fee_collection.view',
            'fee_collection.create',
            'fee_collection.edit',
            'receipt.view',
            'ledger.view',
            'report.view',
        ]);

        // 4. Create school-scoped Teacher role
        $teacherRole = Role::firstOrCreate([
            'name' => 'Teacher',
            'guard_name' => 'web',
            'school_id' => $school->id,
        ]);
        $teacherRole->syncPermissions([
            'dashboard.view',
            'academic_year.view',
            'academic_year.switch',
            'class.view',
            'section.view',
            'student.view',
            // Examinations module
            'exam.view',
            'exam_schedule.view',
            'marks.view',
            'marks.create',
            'marks.edit',
            'report_card.view',
        ]);

        // 5. Create School Admin User for Dummy School
        $schoolAdmin = User::updateOrCreate(
            ['email' => 'admin@greenwood.com'],
            [
                'school_id' => $school->id,
                'name' => 'John Doe',
                'mobile' => '9876543210',
                'password' => Hash::make('Password123'),
                'status' => 'active',
            ]
        );
        $schoolAdmin->assignRole($schoolAdminRole);

        // 6. Create Teacher User for Dummy School
        $teacher = User::updateOrCreate(
            ['email' => 'teacher@greenwood.com'],
            [
                'school_id' => $school->id,
                'name' => 'Jane Smith',
                'mobile' => '9876543211',
                'password' => Hash::make('Password123'),
                'status' => 'active',
            ]
        );
        $teacher->assignRole($teacherRole);
    }
}
