<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth middleware handles authentication
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:group,student,period'],
            'format' => ['required', 'string', 'in:csv,json,xlsx,pdf'],
            'period_id' => ['required', 'integer', 'exists:periods,id'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'template_id' => ['nullable', 'integer', 'exists:export_templates,id'],
            'filters' => ['nullable', 'array'],
            'delivery_method' => ['sometimes', 'in:download,email'],
            'recipient_email' => ['required_if:delivery_method,email', 'nullable', 'email', 'max:255'],
        ];
    }

    /**
     * Additional validation beyond basic rules.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type = $this->input('type');

            if ($type === 'group' && ! $this->filled('group_id')) {
                $validator->errors()->add('group_id', 'group_id is required when exporting a group.');
            }

            if ($type === 'student') {
                if (! $this->filled('group_id')) {
                    $validator->errors()->add('group_id', 'group_id is required when exporting a student.');
                }
                if (! $this->filled('student_id')) {
                    $validator->errors()->add('student_id', 'student_id is required when exporting a student.');
                }
            }
        });
    }
}
