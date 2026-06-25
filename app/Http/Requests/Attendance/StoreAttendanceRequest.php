<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'class_id' => 'required|integer|exists:classes,id',
            'section_id' => 'required|integer|exists:sections,id',
            'attendance_date' => 'required|date_format:Y-m-d',
            'students' => 'required|array|min:1',
            'students.*.student_id' => 'required|integer|exists:students,id',
            'students.*.status' => 'required|string|in:Present,Absent,Late,Half Day,Leave,Holiday',
            'students.*.remarks' => 'nullable|string|max:500',
        ];
    }
}
