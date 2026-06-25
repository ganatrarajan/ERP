<?php

namespace App\Http\Requests\Schools;

use Illuminate\Foundation\Http\FormRequest;

class StoreSchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            // School fields
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:schools,email',
            'phone' => 'nullable|string|max:20',
            'logo' => 'nullable|string|max:255', // If it's a URL or file path placeholder
            'address' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'default_report_card_template' => 'nullable|string|in:basic,detailed,cbse',
            'mobile_academic_year_id' => 'nullable|integer|exists:academic_years,id',

            // Admin fields
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255|unique:users,email',
            'admin_mobile' => 'nullable|string|max:20',
            'admin_password' => 'required|string|min:8',

            // Modules
            'module_ids' => 'nullable|array',
            'module_ids.*' => 'integer|exists:modules,id',
        ];
    }
}
