<?php

namespace App\Http\Requests\Roles;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('role.edit');
    }

    public function rules(): array
    {
        $schoolId = $this->user()->school_id;
        if ($this->user()->isSuperAdmin() && $this->has('school_id')) {
            $schoolId = $this->input('school_id');
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('roles', 'name')
                    ->where(function ($query) use ($schoolId) {
                        return $query->where('school_id', $schoolId);
                    })
            ],
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
            'status' => 'nullable|string|in:active,inactive',
        ];
    }
}
