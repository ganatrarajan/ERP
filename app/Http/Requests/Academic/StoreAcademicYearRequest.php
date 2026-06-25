<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class StoreAcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_current' => 'nullable|boolean',
            'status' => 'nullable|in:active,inactive',
            'clone_source_id' => 'nullable|integer|exists:academic_years,id',
            'clone_elements' => 'nullable|array',
            'clone_elements.*' => 'string|in:classes_sections,subjects,fee_structures',
        ];
    }
}
