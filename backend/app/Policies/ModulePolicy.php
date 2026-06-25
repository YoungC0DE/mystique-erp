<?php

namespace App\Policies;

use App\Models\Module;
use App\Models\User;

class ModulePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->is_admin ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('read');
    }

    public function view(User $user, Module $module): bool
    {
        return $user->hasPermissionTo('read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create');
    }

    public function update(User $user, Module $module): bool
    {
        return $user->hasPermissionTo('update');
    }

    public function delete(User $user, Module $module): bool
    {
        return $user->hasPermissionTo('delete');
    }
}
