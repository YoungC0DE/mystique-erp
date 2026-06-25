<?php

namespace App\Repositories;

use App\Models\Permission;
use App\Support\Cache\RbacStructuralCache;
use Illuminate\Database\Eloquent\Collection;

class PermissionRepository
{
    public function __construct(
        private readonly RbacStructuralCache $cache,
    ) {}

    /**
     * @return Collection<int, Permission>
     */
    public function allOrderedByName(): Collection
    {
        return $this->cache->rememberPermissions(
            fn () => Permission::query()->orderBy('name')->get()
        );
    }
}
