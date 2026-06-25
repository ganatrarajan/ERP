<?php

namespace App\Http\Requests\Promotion;

use Illuminate\Foundation\Http\FormRequest;

class StorePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|integer',
            'from_academic_year_id' => 'required|integer',
            'from_class_id' => 'required|integer',
            'from_section_id' => 'required|integer',
            'to_academic_year_id' => 'required|integer',
            'to_class_id' => 'required|integer',
            'to_section_id' => 'required|integer',
        ];
    }
}
