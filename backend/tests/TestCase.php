<?php

namespace Tests;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Passport\Passport;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        if ($this->usesRefreshDatabase() && ! extension_loaded('pdo_mysql')) {
            $this->markTestSkipped(
                'Extensão PHP pdo_mysql não disponível neste ambiente. '.
                'Execute: docker compose exec backend php artisan test'
            );
        }

        parent::setUp();
    }

    protected function usesRefreshDatabase(): bool
    {
        return in_array(RefreshDatabase::class, class_uses_recursive(static::class), true);
    }

    /**
     * Garante a existência das permissões CRUD globais e as retorna por slug.
     *
     * @return array<string, Permission>
     */
    protected function seedCrudPermissions(): array
    {
        $permissions = [];

        foreach (['create' => 'Create', 'read' => 'Read', 'update' => 'Update', 'delete' => 'Delete'] as $slug => $name) {
            $permissions[$slug] = Permission::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }

        return $permissions;
    }

    /**
     * Cria um usuário comum com as permissões informadas.
     *
     * @param  array<int, string>  $permissions
     */
    protected function userWithPermissions(array $permissions = []): User
    {
        $user = User::factory()->create();

        if ($permissions !== []) {
            $all = $this->seedCrudPermissions();
            $user->permissions()->attach(
                collect($permissions)->map(fn (string $slug) => $all[$slug]->id)->all()
            );
        }

        return $user->fresh();
    }

    /**
     * Cria um Admin da instalação (acesso irrestrito).
     */
    protected function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /**
     * Autentica o usuário no guard de API (Passport) sem precisar emitir tokens reais.
     */
    protected function actingAsApi(User $user): static
    {
        Passport::actingAs($user, [], 'api');

        return $this;
    }
}
