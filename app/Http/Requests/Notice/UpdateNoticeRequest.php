<?php

namespace App\Http\Requests\Notice;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNoticeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'notice_date' => 'required|date_format:Y-m-d',
            'target_type' => 'required|in:Entire School,Class Wise,Section Wise',
            'class_id' => 'required_if:target_type,Class Wise,Section Wise|nullable|integer|exists:classes,id',
            'section_id' => 'required_if:target_type,Section Wise|nullable|integer|exists:sections,id',
            'attachment' => 'nullable', // Can be a new file upload or the existing file string
            'status' => 'required|in:active,inactive',
        ];
    }
}
