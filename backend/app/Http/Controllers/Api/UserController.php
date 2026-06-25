<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaginatedIndexRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {
        $this->authorizeResource(User::class, 'user');
    }

    public function index(PaginatedIndexRequest $request): AnonymousResourceCollection
    {
        return UserResource::collection(
            $this->userService->list($request->perPage())
        );
    }

    public function store(StoreUserRequest $request): UserResource
    {
        $user = $this->userService->create($request->user(), $request->validated());

        return new UserResource($user);
    }

    public function show(User $user): UserResource
    {
        $user = $this->userService->findByUuid($user->uuid)
            ?? $user->load(['roles.permissions', 'permissions']);

        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $user = $this->userService->update($user, $request->validated());

        return new UserResource($user);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->userService->delete($user);

        return response()->json(['message' => 'Usuário removido com sucesso.']);
    }
}
