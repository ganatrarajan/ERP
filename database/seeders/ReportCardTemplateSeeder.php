<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ReportCardTemplate;

class ReportCardTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'school_id' => null,
                'name' => 'Basic Report Card',
                'template_key' => 'basic',
                'description' => 'Standard layout showing subject wise marks, totals, percentage, grade and result.',
                'status' => 'active',
            ],
            [
                'school_id' => null,
                'name' => 'Detailed Report Card',
                'template_key' => 'detailed',
                'description' => 'Comprehensive layout showing scholastic and co-scholastic subjects, attendance, and remarks.',
                'status' => 'active',
            ],
            [
                'school_id' => null,
                'name' => 'CBSE Style Report Card',
                'template_key' => 'cbse',
                'description' => 'CBSE format featuring scholastic and co-scholastic grades, remarks, and signatures.',
                'status' => 'active',
            ],
        ];

        foreach ($templates as $tmpl) {
            ReportCardTemplate::updateOrCreate(
                ['template_key' => $tmpl['template_key'], 'school_id' => $tmpl['school_id']],
                $tmpl
            );
        }
    }
}
