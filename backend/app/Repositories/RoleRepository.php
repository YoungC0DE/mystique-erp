<?php

namespace App\Repositories;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Cache\RbacStructuralCache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RoleRepository
{
    public function __construct(
        private readonly RbacStructuralCache $cache,
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Role::query()
            ->with('permissions')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?Role
    {
        return $this->cache->rememberRole($uuid, fn () => Role::query()
            ->with('permissions')
            ->where('uuid', $uuid)
            ->first());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Role
    {
        return Role::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Role $role, array $data): Role
    {
        $role->update($data);

        return $role;
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }

    /**
     * Resolve IDs internos das permissões a partir de seus UUIDs públicos.
     *
     * @param  array<int, string>  $uuids
     * @return Collection<int, int>
     */
    public function permissionIdsFromUuids(array $uuids): Collection
    {
        return Permission::whereIn('uuid', $uuids)->pluck('id');
    }
}
