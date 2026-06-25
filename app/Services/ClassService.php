<?php

namespace App\Services;

use App\Models\ClassModel;

class ClassService
{
    /**
     * Create a new Class.
     */
    public function createClass(array $data, int $schoolId): ClassModel
    {
        return ClassModel::create([
            'school_id' => $schoolId,
            'academic_year_id' => $data['academic_year_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);
    }

    /**
     * Update a Class.
     */
    public function updateClass(ClassModel $class, array $data): ClassModel
    {
        $class->update([
            'academic_year_id' => $data['academic_year_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? $class->description,
            'status' => $data['status'] ?? $class->status,
        ]);

        return $class;
    }
}
