<?php

namespace App\Http\Requests\Roles;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('role.edit');
    }

    public function rules(): array
    {
        $role = $this->route('role');
        $schoolId = $role->school_id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('roles', 'name')
                    ->ignore($role->id)
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
