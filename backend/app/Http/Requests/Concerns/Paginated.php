<?php

namespace App\Http\Requests\Concerns;

trait Paginated
{
    /**
     * @return array<string, mixed>
     */
    protected function paginatedRules(int $maxPerPage = 100): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.$maxPerPage],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function perPage(int $default = 15): int
    {
        return $this->integer('per_page', $default);
    }

    public function pageNumber(int $default = 1): int
    {
        return $this->integer('page', $default);
    }
}
