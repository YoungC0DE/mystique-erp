<?php

namespace App\Services\Rbac;

use App\Enums\ActivityAction;
use App\Models\Role;
use App\Repositories\RoleRepository;
use App\Services\ActivityLog\ActivityLogger;
use App\Support\Cache\RbacStructuralCache;
use App\Support\Cache\UserStructuralCache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoleService
{
    public function __construct(
        private readonly RoleRepository $roles,
        private readonly ActivityLogger $logger,
        private readonly RbacStructuralCache $cache,
        private readonly UserStructuralCache $userCache,
    ) {}

    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return $this->roles->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?Role
    {
        return $this->roles->findByUuid($uuid);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = $this->roles->create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Str::slug($data['name']),
            ]);

            if (array_key_exists('permissions', $data)) {
                $this->syncPermissions($role, $data['permissions'] ?? [], log: false);
            }

            $this->logger->log(
                ActivityAction::ROLE_CREATED,
                "Role '{$role->name}' criada.",
                subject: $role,
            );

            $role = $role->load('permissions');
            $this->cache->storeRole($role);

            return $role;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data) {
            $role = $this->roles->update($role, [
                'name' => $data['name'] ?? $role->name,
                'slug' => $data['slug'] ?? $role->slug,
            ]);

            if (array_key_exists('permissions', $data)) {
                $this->syncPermissions($role, $data['permissions'] ?? [], log: false);
            }

            $this->logger->log(
                ActivityAction::ROLE_UPDATED,
                "Role '{$role->name}' atualizada.",
                subject: $role,
            );

            $role = $role->load('permissions');
            $this->cache->storeRole($role);
            $this->userCache->forgetUsersWithRole($role);

            return $role;
        });
    }

    public function delete(Role $role): void
    {
        $name = $role->name;

        $this->userCache->forgetUsersWithRole($role);
        $this->cache->forgetRole($role);
        $this->roles->delete($role);

        $this->logger->log(
            ActivityAction::ROLE_DELETED,
            "Role '{$name}' removida.",
        );
    }

    /**
     * @param  array<int, string>  $permissionUuids
     */
    private function syncPermissions(Role $role, array $permissionUuids, bool $log = true): Role
    {
        $ids = $this->roles->permissionIdsFromUuids($permissionUuids);

        $role->permissions()->sync($ids);

        if ($log) {
            $this->logger->log(
                ActivityAction::ROLE_PERMISSIONS_SYNCED,
                "Permissões da role '{$role->name}' atualizadas.",
                properties: ['permissions' => $permissionUuids],
                subject: $role,
            );
        }

        $role = $role->load('permissions');
        $this->cache->storeRole($role);
        $this->userCache->forgetUsersWithRole($role);

        return $role;
    }
}
