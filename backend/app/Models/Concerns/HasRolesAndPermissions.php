<?php

namespace App\Models\Concerns;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

trait HasRolesAndPermissions
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    /**
     * Todas as permissões efetivas: diretas + via roles.
     */
    public function allPermissions(): Collection
    {
        $this->loadMissing('permissions', 'roles.permissions');

        return $this->permissions
            ->merge($this->roles->flatMap->permissions)
            ->unique('id')
            ->values();
    }

    public function hasPermissionTo(string $slug): bool
    {
        if ($this->is_admin) {
            return true;
        }

        return $this->allPermissions()->contains('slug', $slug);
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles->contains('slug', $slug);
    }
}
