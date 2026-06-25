<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;
use App\Models\School;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultModules = [
            [
                'name' => 'Students',
                'slug' => 'students',
                'icon' => 'users',
                'description' => 'Manage student admissions, profiles, and directories.',
            ],
            [
                'name' => 'Academics',
                'slug' => 'academics',
                'icon' => 'academic-cap',
                'description' => 'Manage academic years, classes, and sections.',
            ],
            [
                'name' => 'Teachers',
                'slug' => 'teachers',
                'icon' => 'briefcase',
                'description' => 'Manage teachers, staff profiles, and details.',
            ],
            [
                'name' => 'Attendance',
                'slug' => 'attendance',
                'icon' => 'calendar',
                'description' => 'Track student and staff daily attendance.',
            ],
            [
                'name' => 'Homework',
                'slug' => 'homework',
                'icon' => 'document-text',
                'description' => 'Assign, track, and evaluate homework and assignments.',
            ],
            [
                'name' => 'Fees',
                'slug' => 'fees',
                'icon' => 'currency-dollar',
                'description' => 'Manage fee structures, collection, invoices, and payments.',
            ],
            [
                'name' => 'Examinations',
                'slug' => 'examinations',
                'icon' => 'academic-cap',
                'description' => 'Comprehensive examination management module including schedules, marks entry, and report cards.',
            ],
            [
                'name' => 'Notices',
                'slug' => 'notices',
                'icon' => 'bell',
                'description' => 'Manage and publish school announcements and notices.',
            ],
            [
                'name' => 'Subjects',
                'slug' => 'subjects',
                'icon' => 'book-open',
                'description' => 'Manage school academic subjects and curriculum.',
            ],
        ];

        // Delete any modules that are no longer in the default list
        $defaultSlugs = array_column($defaultModules, 'slug');
        Module::whereNotIn('slug', $defaultSlugs)->delete();

        foreach ($defaultModules as $mod) {
            Module::updateOrCreate(
                ['slug' => $mod['slug']],
                [
                    'name' => $mod['name'],
                    'icon' => $mod['icon'],
                    'description' => $mod['description'],
                    'status' => 'active',
                ]
            );
        }

        // Enable all modules for the existing dummy schools by default
        $schools = School::all();
        $modules = Module::all();

        foreach ($schools as $school) {
            foreach ($modules as $module) {
                $school->modules()->syncWithoutDetaching([
                    $module->id => ['is_active' => true]
                ]);
            }
        }
    }
}
