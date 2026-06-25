<?php

namespace App\Http\Requests\Record;

use App\Models\Module;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveRecordRequest extends FormRequest
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
                'required',
                Rule::in($record->module->kanbanStatusSlugs()),
            ],
        ];
    }
}
