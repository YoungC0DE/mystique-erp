<?php

namespace App\Repositories;

use App\Models\User;
use App\Support\Cache\UserStructuralCache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserRepository
{
    public function __construct(
        private readonly UserStructuralCache $cache,
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return User::query()
            ->with(['roles.permissions', 'permissions'])
            ->latest()
            ->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?User
    {
        return $this->cache->rememberUser($uuid, fn () => User::query()
            ->with(['roles.permissions', 'permissions'])
            ->where('uuid', $uuid)
            ->first());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user;
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
