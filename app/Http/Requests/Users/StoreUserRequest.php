<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('user.create');
    }

    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'mobile' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
            'status' => 'required|in:active,inactive',
            'role' => 'required|exists:roles,id',
        ];

        // If Super Admin, school_id can be passed in request
        if ($this->user()->isSuperAdmin()) {
            $rules['school_id'] = 'nullable|exists:schools,id';
        }

        return $rules;
    }
}
