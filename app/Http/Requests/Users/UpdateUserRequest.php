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
        $currentUser = $this->user();
        $schoolId = $currentUser->isSuperAdmin() 
            ? ($this->input('school_id') ?: $this->route('user')->school_id) 
            : $currentUser->school_id;

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $userId,
            'mobile' => [
                'nullable',
                'string',
                'max:20',
                \Illuminate\Validation\Rule::unique('users')->where('school_id', $schoolId)->ignore($userId),
            ],
            'password' => 'nullable|string|min:8',
            'status' => 'required|in:active,inactive',
            'role' => 'required|exists:roles,id',

            // Common additional fields
            'employee_id' => [
                'nullable',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('users')->where('school_id', $schoolId)->ignore($userId),
            ],
            'gender' => 'nullable|string|in:male,female,other',
            'dob' => 'nullable|date',
            'aadhaar_no' => [
                'nullable',
                'string',
                'size:12',
                \Illuminate\Validation\Rule::unique('users')->where('school_id', $schoolId)->ignore($userId),
            ],
            'pan_no' => [
                'nullable',
                'string',
                'size:10',
                \Illuminate\Validation\Rule::unique('users')->where('school_id', $schoolId)->ignore($userId),
            ],
            'address' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_mobile' => 'nullable|string|max:20',
            'profile_photo' => 'nullable|string',
        ];

        // Dynamically add teacher-specific rules if role is Teacher
        $roleId = $this->input('role');
        $role = \App\Models\Role::find($roleId);
        if ($role && $role->name === 'Teacher') {
            $rules['teacher_code'] = [
                'required',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('users')->where('school_id', $schoolId)->ignore($userId),
            ];
            $rules['qualification'] = 'required|string|max:255';
            $rules['experience'] = 'nullable|string|max:255';
            $rules['joining_date'] = 'required|date';
            $rules['department'] = 'required|string|max:255';
            $rules['designation'] = 'required|string|max:255';
            $rules['employment_type'] = 'required|string|in:Full-time,Part-time,Contract,Temporary,Substitute';
        } else {
            $rules['teacher_code'] = [
                'nullable',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('users')->where('school_id', $schoolId)->ignore($userId),
            ];
            $rules['qualification'] = 'nullable|string|max:255';
            $rules['experience'] = 'nullable|string|max:255';
            $rules['joining_date'] = 'nullable|date';
            $rules['department'] = 'nullable|string|max:255';
            $rules['designation'] = 'nullable|string|max:255';
            $rules['employment_type'] = 'nullable|string|max:255';
        }

        return $rules;
    }
}
