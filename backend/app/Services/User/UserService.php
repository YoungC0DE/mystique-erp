<?php

namespace App\Services\User;

use App\Enums\ActivityAction;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\ActivityLog\ActivityLogger;
use App\Support\Cache\UserStructuralCache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly ActivityLogger $logger,
        private readonly UserStructuralCache $cache,
    ) {}

    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return $this->users->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?User
    {
        return $this->users->findByUuid($uuid);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): User
    {
        return DB::transaction(function () use ($actor, $data) {
            $user = $this->users->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_admin' => $actor->is_admin ? (bool) ($data['is_admin'] ?? false) : false,
            ]);

            if (array_key_exists('roles', $data)) {
                $user->roles()->sync(Role::whereIn('uuid', $data['roles'] ?? [])->pluck('id'));
            }

            if (array_key_exists('permissions', $data)) {
                $user->permissions()->sync(Permission::whereIn('uuid', $data['permissions'] ?? [])->pluck('id'));
            }

            $this->logger->log(
                ActivityAction::USER_CREATED,
                "Usuário '{$user->email}' criado.",
                subject: $user,
            );

            $user = $user->load(['roles.permissions', 'permissions']);
            $this->cache->store($user);

            return $user;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $payload = [
                'name' => $data['name'] ?? $user->name,
                'email' => $data['email'] ?? $user->email,
            ];

            if (! empty($data['password'])) {
                $payload['password'] = Hash::make($data['password']);
            }

            if (array_key_exists('is_admin', $data) && request()->user()?->is_admin) {
                $payload['is_admin'] = (bool) $data['is_admin'];
            }

            $user = $this->users->update($user, $payload);

            if (array_key_exists('roles', $data)) {
                $user->roles()->sync(Role::whereIn('uuid', $data['roles'] ?? [])->pluck('id'));
            }

            if (array_key_exists('permissions', $data)) {
                $user->permissions()->sync(Permission::whereIn('uuid', $data['permissions'] ?? [])->pluck('id'));
            }

            $this->logger->log(
                ActivityAction::USER_UPDATED,
                "Usuário '{$user->email}' atualizado.",
                subject: $user,
            );

            $user = $user->load(['roles.permissions', 'permissions']);
            $this->cache->store($user);

            return $user;
        });
    }

    public function delete(User $user): void
    {
        $email = $user->email;

        $this->cache->forgetUser($user);
        $this->users->delete($user);

        $this->logger->log(
            ActivityAction::USER_DELETED,
            "Usuário '{$email}' removido.",
        );
    }
}
