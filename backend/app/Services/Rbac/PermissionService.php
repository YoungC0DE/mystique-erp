<?php

namespace App\Services\Rbac;

use App\Models\Permission;
use App\Repositories\PermissionRepository;
use Illuminate\Database\Eloquent\Collection;

class PermissionService
{
    public function __construct(
        private readonly PermissionRepository $permissions,
    ) {}

    /**
     * @return Collection<int, Permission>
     */
    public function list(): Collection
    {
        return $this->permissions->allOrderedByName();
    }
}
