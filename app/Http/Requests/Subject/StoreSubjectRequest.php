<?php

namespace App\Http\Requests\Subject;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => 'required|exists:academic_years,id',
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
            'is_optional' => 'nullable|boolean',
            'evaluation_type' => 'nullable|in:marks,grades',
            'subject_category' => 'nullable|in:scholastic,co_scholastic',
            'maximum_marks' => 'nullable|integer|min:1',
            'passing_marks' => 'nullable|integer|min:1',
            'grade_scale_id' => 'nullable|array',
            'grade_scale_id.*' => 'exists:grade_scales,id',
        ];
    }
}
