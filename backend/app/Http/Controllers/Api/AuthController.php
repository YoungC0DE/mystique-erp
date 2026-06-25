<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthService;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly UserService $userService,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
        );

        return response()->json([
            'user' => new UserResource($result['user']->load(['roles.permissions', 'permissions'])),
            'token' => $result['tokens'],
        ]);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register(
            $request->string('name')->toString(),
            $request->string('email')->toString(),
            $request->string('password')->toString(),
        );

        return response()->json([
            'user' => new UserResource($result['user']),
            'token' => $result['tokens'],
        ], 201);
    }

    public function refresh(RefreshRequest $request): JsonResponse
    {
        $tokens = $this->authService->refresh(
            $request->string('refresh_token')->toString(),
        );

        return response()->json(['token' => $tokens]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }

    public function me(Request $request): UserResource
    {
        $user = $this->userService->findByUuid($request->user()->uuid)
            ?? $request->user()->load(['roles.permissions', 'permissions']);

        return new UserResource($user);
    }
}
