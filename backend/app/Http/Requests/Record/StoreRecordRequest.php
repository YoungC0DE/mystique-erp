<?php

namespace App\Http\Requests\Record;

use App\Models\Module;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecordRequest extends FormRequest
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
        /** @var Module $module */
        $module = $this->route('module');
        $module->loadMissing('kanbanStatuses');

        return [
            'status' => [
                'nullable',
                Rule::in($module->kanbanStatusSlugs()),
            ],
            'values' => ['sometimes', 'array'],
        ];
    }
}
