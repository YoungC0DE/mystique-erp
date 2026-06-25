<?php

namespace App\Http\Requests\Record;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRecordRequest extends FormRequest
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
        $record = $this->route('record');
        $record->loadMissing('module.kanbanStatuses');

        return [
            'status' => [
                'sometimes',
                Rule::in($record->module->kanbanStatusSlugs()),
            ],
            'values' => ['sometimes', 'array'],
        ];
    }
}
