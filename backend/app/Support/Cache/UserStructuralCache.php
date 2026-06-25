<?php

namespace App\Support\Cache;

use App\Models\Role;
use App\Models\User;

class UserStructuralCache extends StructuralCacheStore
{
    public function userKey(string $uuid): string
    {
        return "mystique:user:{$uuid}";
    }

    public function rememberUser(string $uuid, callable $resolver): ?User
    {
        return $this->remember($this->userKey($uuid), $resolver);
    }

    public function store(User $user): void
    {
        $this->put($this->userKey($user->uuid), $user);
    }

    public function forgetUser(User $user): void
    {
        $this->forget($this->userKey($user->uuid));
    }

    public function forgetUsersWithRole(Role $role): void
    {
        $role->loadMissing('users');

        foreach ($role->users as $user) {
            $this->forgetUser($user);
        }
    }
}
