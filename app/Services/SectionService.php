<?php

namespace App\Services;

use App\Models\Section;

class SectionService
{
    /**
     * Create a new Section.
     */
    public function createSection(array $data, int $schoolId): Section
    {
        return Section::create([
            'school_id' => $schoolId,
            'class_id' => $data['class_id'],
            'name' => $data['name'],
            'status' => $data['status'] ?? 'active',
        ]);
    }

    /**
     * Update a Section.
     */
    public function updateSection(Section $section, array $data): Section
    {
        $section->update([
            'class_id' => $data['class_id'],
            'name' => $data['name'],
            'status' => $data['status'] ?? $section->status,
        ]);

        return $section;
    }
}
