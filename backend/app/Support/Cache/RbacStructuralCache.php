<?php

namespace App\Support\Cache;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

class RbacStructuralCache extends StructuralCacheStore
{
    private const PERMISSIONS_KEY = 'mystique:permissions:all';

    public function roleKey(string $uuid): string
    {
        return "mystique:role:{$uuid}";
    }

    /**
     * @param  callable(): Collection<int, \App\Models\Permission>  $resolver
     * @return Collection<int, \App\Models\Permission>
     */
    public function rememberPermissions(callable $resolver): Collection
    {
        return $this->remember(self::PERMISSIONS_KEY, $resolver);
    }

    public function rememberRole(string $uuid, callable $resolver): ?Role
    {
        return $this->remember($this->roleKey($uuid), $resolver);
    }

    public function storeRole(Role $role): void
    {
        $this->put($this->roleKey($role->uuid), $role);
    }

    public function forgetRole(Role $role): void
    {
        $this->forget($this->roleKey($role->uuid));
    }
}
