<?php

namespace App\Services\Profile;

use App\Enums\ActivityAction;
use App\Models\User;
use App\Services\ActivityLog\ActivityLogger;
use App\Support\Cache\UserStructuralCache;
use Illuminate\Support\Facades\Hash;

class ProfileService
{
    public function __construct(
        private readonly ActivityLogger $logger,
        private readonly UserStructuralCache $cache,
    ) {}

    /**
     * Atualiza os dados do próprio perfil (nome, e-mail, idioma).
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        $attributes = array_intersect_key($data, array_flip(['name', 'email', 'locale']));

        $changes = $this->diff($user, $attributes);

        $user->fill($attributes)->save();

        if ($changes !== []) {
            $this->logger->log(
                ActivityAction::PROFILE_UPDATED,
                'Perfil atualizado.',
                properties: ['changes' => $changes],
                subject: $user,
                user: $user,
            );
        }

        $user = $user->refresh()->load(['roles.permissions', 'permissions']);
        $this->cache->store($user);

        return $user;
    }

    /**
     * Troca a senha do próprio usuário.
     */
    public function updatePassword(User $user, string $password): void
    {
        $user->password = Hash::make($password);
        $user->save();

        $this->logger->log(
            ActivityAction::PROFILE_PASSWORD_UPDATED,
            'Senha alterada.',
            subject: $user,
            user: $user,
        );
    }

    /**
     * Calcula o diff (valor antigo/novo) dos campos alterados para auditoria.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, array{old: mixed, new: mixed}>
     */
    private function diff(User $user, array $attributes): array
    {
        $changes = [];

        foreach ($attributes as $key => $value) {
            $old = $user->getOriginal($key);

            if ($old !== $value) {
                $changes[$key] = ['old' => $old, 'new' => $value];
            }
        }

        return $changes;
    }
}
