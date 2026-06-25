<?php

namespace App\Http\Requests\Module;

use App\Enums\ModuleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateModuleRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', new Enum(ModuleStatus::class)],
            'connection_id' => ['sometimes', 'required', 'string', 'exists:database_connections,uuid'],
            'callback_url' => ['nullable', 'string', 'url', 'max:2048'],
            'callback_method' => ['nullable', 'string', 'in:POST,PUT,PATCH'],
            'status_column' => ['sometimes', 'required', 'string', 'max:255'],
            'columns' => ['sometimes', 'required', 'array', 'min:1'],
            'columns.*.name' => ['required_with:columns', 'string', 'max:255'],
            'columns.*.label' => ['nullable', 'string', 'max:255'],
            'columns.*.type' => ['nullable', 'string', 'max:255'],
            'statuses' => ['nullable', 'array'],
            'statuses.*.slug' => ['required_with:statuses', 'string', 'max:255'],
            'statuses.*.label' => ['required_with:statuses', 'string', 'max:255'],
            'statuses.*.order' => ['required_with:statuses', 'integer', 'min:0'],
            'statuses.*.external_value' => ['required_with:statuses', 'string', 'max:255'],
        ];
    }
}
