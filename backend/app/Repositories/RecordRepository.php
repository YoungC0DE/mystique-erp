<?php

namespace App\Repositories;

use App\Models\Module;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RecordRepository
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(Module $module, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $module->records()
            ->with('values.field')
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->latest()
            ->paginate($perPage);
    }
}
