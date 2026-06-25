<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaginatedIndexRequest;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Services\Rbac\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleService $roleService,
    ) {
        $this->authorizeResource(Role::class, 'role');
    }

    public function index(PaginatedIndexRequest $request): AnonymousResourceCollection
    {
        return RoleResource::collection(
            $this->roleService->list($request->perPage())
        );
    }

    public function store(StoreRoleRequest $request): RoleResource
    {
        $role = $this->roleService->create($request->validated());

        return new RoleResource($role);
    }

    public function show(Role $role): RoleResource
    {
        $role = $this->roleService->findByUuid($role->uuid)
            ?? $role->load('permissions');

        return new RoleResource($role);
    }

    public function update(UpdateRoleRequest $request, Role $role): RoleResource
    {
        $role = $this->roleService->update($role, $request->validated());

        return new RoleResource($role);
    }

    public function destroy(Role $role): JsonResponse
    {
        $this->roleService->delete($role);

        return response()->json(['message' => 'Role removida com sucesso.']);
    }
}
