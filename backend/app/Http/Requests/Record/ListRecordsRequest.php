<?php

namespace App\Http\Requests\Record;

use App\Http\Requests\Concerns\Paginated;
use Illuminate\Foundation\Http\FormRequest;

class ListRecordsRequest extends FormRequest
{
    use Paginated;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge($this->paginatedRules(), [
            'status' => ['sometimes', 'nullable', 'string'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return array_filter([
            'status' => $this->query('status'),
        ], fn ($value) => $value !== null && $value !== '');
    }
}
