<?php

namespace App\Http\Requests\Module;

use App\Enums\FieldType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateModuleFieldRequest extends FormRequest
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
        $field = $this->route('field');

        return [
            'label' => ['sometimes', 'required', 'string', 'max:255'],
            'key' => [
                'sometimes', 'required', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('module_fields', 'key')
                    ->where('module_id', $field?->module_id)
                    ->ignore($field?->id),
            ],
            'type' => ['sometimes', 'required', new Enum(FieldType::class)],
            'required' => ['boolean'],
            'default_value' => ['nullable', 'string'],
            'options' => ['nullable', 'array'],
            'options.*' => ['string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
            'show_in_card' => ['boolean'],
            'show_in_list' => ['boolean'],
            'visible' => ['boolean'],
        ];
    }
}
