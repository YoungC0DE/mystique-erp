<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\Profile\ProfileService;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileService $profileService,
    ) {}

    public function update(UpdateProfileRequest $request): UserResource
    {
        $user = $this->profileService->update($request->user(), $request->validated());

        return new UserResource($user->load('roles'));
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $this->profileService->updatePassword(
            $request->user(),
            $request->string('password')->toString(),
        );

        return response()->json(['message' => 'Senha alterada com sucesso.']);
    }
}
