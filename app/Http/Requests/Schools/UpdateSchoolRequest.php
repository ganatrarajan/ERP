<?php

namespace App\Http\Requests\Schools;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user->can('school.edit')) {
            return true;
        }

        $school = $this->route('school');
        if ($user->can('settings.edit') && $user->school_id === $school->id) {
            return true;
        }

        return false;
    }

    public function rules(): array
    {
        $school = $this->route('school');
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:schools,email,' . $school->id,
            'phone' => 'nullable|string|max:20',
            'logo' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'status' => 'sometimes|required|in:active,inactive',
            'default_report_card_template' => 'nullable|string|in:basic,detailed,cbse',
            'mobile_academic_year_id' => 'nullable|integer|exists:academic_years,id',
        ];
    }
}
