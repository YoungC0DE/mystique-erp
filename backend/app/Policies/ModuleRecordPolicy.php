<?php

namespace App\Policies;

use App\Models\ModuleRecord;
use App\Models\User;

class ModuleRecordPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->is_admin ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('read');
    }

    public function view(User $user, ModuleRecord $record): bool
    {
        return $user->hasPermissionTo('read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create');
    }

    public function update(User $user, ModuleRecord $record): bool
    {
        return $user->hasPermissionTo('update');
    }

    public function delete(User $user, ModuleRecord $record): bool
    {
        return $user->hasPermissionTo('delete');
    }

    public function moveIntegrated(User $user): bool
    {
        return $user->hasPermissionTo('update');
    }

    public function upsertNote(User $user): bool
    {
        return $user->hasPermissionTo('update');
    }
}
