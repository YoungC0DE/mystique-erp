<?php

namespace App\Http\Requests\Module;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateLayoutRequest extends FormRequest
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
            'fields' => ['present', 'array'],
            'fields.*.id' => ['required', 'string', 'exists:module_fields,uuid'],
            'fields.*.order' => ['sometimes', 'integer', 'min:0'],
            'fields.*.show_in_card' => ['sometimes', 'boolean'],
            'fields.*.show_in_list' => ['sometimes', 'boolean'],
            'fields.*.show_in_detail' => ['sometimes', 'boolean'],
            'fields.*.highlighted' => ['sometimes', 'boolean'],
            'fields.*.visible' => ['sometimes', 'boolean'],
            'detail_layout' => ['sometimes', 'nullable', 'array'],
            'detail_layout.rows' => ['sometimes', 'array'],
            'detail_layout.rows.*.field_keys' => ['required', 'array', 'min:1', 'max:3'],
            'detail_layout.rows.*.field_keys.*' => ['required', 'string'],
            'detail_layout.columns' => ['sometimes', 'integer', 'in:1,2'],
            'detail_layout.groups' => ['sometimes', 'array'],
            'detail_layout.groups.*.label' => ['nullable', 'string', 'max:255'],
            'detail_layout.groups.*.field_keys' => ['required', 'array'],
            'detail_layout.groups.*.field_keys.*' => ['required', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $rows = $this->input('detail_layout.rows');

            if (! is_array($rows)) {
                return;
            }

            $seen = [];

            foreach ($rows as $rowIndex => $row) {
                if (! is_array($row['field_keys'] ?? null)) {
                    continue;
                }

                foreach ($row['field_keys'] as $key) {
                    if (isset($seen[$key])) {
                        $validator->errors()->add(
                            'detail_layout.rows',
                            __('modules.detail_layout_duplicate_field', ['field' => $key]),
                        );

                        return;
                    }

                    $seen[$key] = $rowIndex;
                }
            }
        });
    }
}
