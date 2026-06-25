<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'module_id' => ['sometimes', 'string', 'exists:modules,uuid'],
            'field_keys' => ['sometimes', 'array', 'min:1'],
            'field_keys.*' => ['required', 'string', 'max:255'],
            'filters' => ['sometimes', 'array'],
            'filters.*.field' => ['required', 'string'],
            'filters.*.operator' => ['required', 'string'],
            'filters.*.value' => ['nullable'],
            'filters.*.value_to' => ['nullable'],
        ];
    }
}
