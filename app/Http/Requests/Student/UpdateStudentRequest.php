<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Student Master
            'admission_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique('students')->where(function ($query) {
                    $schoolId = $this->input('school_id') ?? $this->user()->school_id;
                    $query->where('school_id', $schoolId)
                        ->where('is_delete', 0);
                    
                    $student = $this->route('student');
                    $studentId = $student instanceof \App\Models\Student ? $student->id : $student;
                    if ($studentId) {
                        $query->where('id', '!=', $studentId);
                    }
                }),
            ],
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|string|in:Male,Female,Other',
            'date_of_birth' => 'required|date',
            'blood_group' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'photo' => 'nullable|string|max:2048',
            'admission_date' => 'required|date',
            'status' => 'nullable|in:active,inactive',

            // Parent Info
            'father_name' => 'nullable|string|max:255',
            'father_mobile' => 'nullable|string|max:20',
            'father_email' => 'nullable|email|max:255',
            'mother_name' => 'nullable|string|max:255',
            'mother_mobile' => 'nullable|string|max:20',
            'mother_email' => 'nullable|email|max:255',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_mobile' => 'nullable|string|max:20',

            // Academic Record
            'academic_year_id' => 'required|integer',
            'class_id' => 'required|integer',
            'section_id' => 'required|integer',
            'roll_no' => [
                'nullable',
                'string',
                'max:100',
                function ($attribute, $value, $fail) {
                    if (empty($value)) return;
                    $schoolId = $this->input('school_id') ?? $this->user()->school_id;
                    
                    $student = $this->route('student');
                    $studentId = $student instanceof \App\Models\Student ? $student->id : $student;

                    $exists = \App\Models\StudentAcademicRecord::where('student_academic_records.school_id', $schoolId)
                        ->join('students', 'student_academic_records.student_id', '=', 'students.id')
                        ->where('students.is_delete', 0)
                        ->where('student_academic_records.academic_year_id', $this->input('academic_year_id'))
                        ->where('student_academic_records.class_id', $this->input('class_id'))
                        ->where('student_academic_records.section_id', $this->input('section_id'))
                        ->where('student_academic_records.roll_no', $value)
                        ->when($studentId, function ($q) use ($studentId) {
                            $q->where('student_academic_records.student_id', '!=', $studentId);
                        })
                        ->exists();

                    if ($exists) {
                        $fail("The roll no has already been taken in this class/section.");
                    }
                }
            ],
        ];
    }
}
