<?php

namespace App\Repositories;

use App\Models\Module;
use App\Support\Cache\ModuleStructuralCache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ModuleRepository
{
    public function __construct(
        private readonly ModuleStructuralCache $cache,
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Module::query()
            ->with(['connection', 'kanbanStatuses'])
            ->withCount('fields')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Lista módulos ativos (para carregamento dinâmico na navbar).
     *
     * @return Collection<int, Module>
     */
    public function activeModules(): Collection
    {
        return $this->cache->rememberActive(fn () => Module::query()
            ->with(['connection', 'kanbanStatuses'])
            ->withCount('fields')
            ->where('status', 'active')
            ->orderBy('name')
            ->get());
    }

    public function findByUuid(string $uuid): ?Module
    {
        return $this->cache->rememberModule($uuid, fn () => Module::query()
            ->with(['connection', 'kanbanStatuses', 'fields'])
            ->withCount('fields')
            ->where('uuid', $uuid)
            ->first());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Module
    {
        return Module::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Module $module, array $data): Module
    {
        $module->update($data);

        return $module;
    }

    public function delete(Module $module): void
    {
        $module->delete();
    }
}
