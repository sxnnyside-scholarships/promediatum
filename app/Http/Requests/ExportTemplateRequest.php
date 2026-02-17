<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth middleware handles authentication
    }

    public function rules(): array
    {
        return [
            'name'                                  => ['required', 'string', 'max:255'],
            'type'                                  => ['required', 'string', 'in:group,student,period'],
            'is_default'                            => ['boolean'],
            'config'                                => ['required', 'array'],
            'config.included_columns'               => ['nullable', 'array'],
            'config.included_columns.*'             => ['string', 'max:100'],
            'config.column_order'                   => ['nullable', 'array'],
            'config.column_order.*'                 => ['string', 'max:100'],
            'config.include_logo'                   => ['boolean'],
            'config.include_header_text'            => ['nullable', 'string', 'max:500'],
            'config.include_footer_text'            => ['nullable', 'string', 'max:500'],
            'config.include_signature_line'         => ['boolean'],
            'config.date_format'                    => ['nullable', 'string', 'max:50'],
            'config.numeric_precision'              => ['integer', 'min:0', 'max:6'],
            'config.orientation'                    => ['string', 'in:portrait,landscape'],
            'config.include_attendance_summary'     => ['boolean'],
            'config.include_observations_summary'   => ['boolean'],
            'config.include_category_breakdown'     => ['boolean'],
        ];
    }
}
