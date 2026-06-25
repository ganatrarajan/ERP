<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('user.edit');
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $userId,
            'mobile' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8',
            'status' => 'required|in:active,inactive',
            'role' => 'required|exists:roles,id',
        ];

        return $rules;
    }
}
