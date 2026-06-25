<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\Paginated;
use Illuminate\Foundation\Http\FormRequest;

class PaginatedIndexRequest extends FormRequest
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
        return $this->paginatedRules();
    }
}
