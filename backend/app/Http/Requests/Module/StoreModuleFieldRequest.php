<?php

namespace App\Http\Requests\Module;

use App\Enums\FieldType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreModuleFieldRequest extends FormRequest
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
        $moduleId = $this->route('module')?->id;
        $optionTypes = implode(',', array_map(fn ($t) => $t->value, FieldType::withOptions()));

        return [
            'label' => ['required', 'string', 'max:255'],
            'key' => [
                'nullable', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('module_fields', 'key')->where('module_id', $moduleId),
            ],
            'type' => ['required', new Enum(FieldType::class)],
            'required' => ['boolean'],
            'default_value' => ['nullable', 'string'],
            'options' => ['nullable', 'array', "required_if:type,{$optionTypes}"],
            'options.*' => ['string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
            'show_in_card' => ['boolean'],
            'show_in_list' => ['boolean'],
            'visible' => ['boolean'],
        ];
    }
}
