<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_update_profile(): void
    {
        $this->putJson('/api/me', ['name' => 'Novo Nome'])
            ->assertUnauthorized();

        $this->putJson('/api/me/password', [
            'current_password' => 'password',
            'password' => 'novaSenha123',
            'password_confirmation' => 'novaSenha123',
        ])->assertUnauthorized();
    }

    public function test_user_can_update_own_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Antigo Nome',
            'email' => 'user@example.com',
            'locale' => 'pt-BR',
        ]);

        $this->actingAsApi($user)
            ->putJson('/api/me', [
                'name' => 'Novo Nome',
                'email' => 'novo@example.com',
                'locale' => 'en',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Novo Nome')
            ->assertJsonPath('data.email', 'novo@example.com')
            ->assertJsonPath('data.locale', 'en')
            ->assertJsonMissingPath('data.company')
            ->assertJsonMissingPath('data.plan')
            ->assertJsonMissingPath('data.company_id');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Novo Nome',
            'email' => 'novo@example.com',
            'locale' => 'en',
        ]);
    }

    public function test_profile_update_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existente@example.com']);
        $user = User::factory()->create(['email' => 'user@example.com']);

        $this->actingAsApi($user)
            ->putJson('/api/me', ['email' => 'existente@example.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_profile_update_logs_activity_with_changes(): void
    {
        $user = User::factory()->create([
            'name' => 'Antigo',
            'email' => 'user@example.com',
        ]);

        $this->actingAsApi($user)
            ->putJson('/api/me', ['name' => 'Atualizado'])
            ->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'profile.updated',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_change_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('senhaAtual123'),
        ]);

        $this->actingAsApi($user)
            ->putJson('/api/me/password', [
                'current_password' => 'senhaAtual123',
                'password' => 'novaSenha123',
                'password_confirmation' => 'novaSenha123',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Senha alterada com sucesso.');

        $user->refresh();

        $this->assertTrue(Hash::check('novaSenha123', $user->password));
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'profile.password.updated',
            'user_id' => $user->id,
        ]);
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('senhaAtual123'),
        ]);

        $this->actingAsApi($user)
            ->putJson('/api/me/password', [
                'current_password' => 'errada',
                'password' => 'novaSenha123',
                'password_confirmation' => 'novaSenha123',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_me_response_exposes_only_profile_fields(): void
    {
        $user = User::factory()->create([
            'name' => 'Usuário',
            'email' => 'user@example.com',
            'locale' => 'pt-BR',
        ]);

        $this->actingAsApi($user)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'is_admin', 'locale', 'permissions', 'created_at'],
            ])
            ->assertJsonMissingPath('data.company')
            ->assertJsonMissingPath('data.plan')
            ->assertJsonMissingPath('data.company_id')
            ->assertJsonMissingPath('data.is_super_admin');
    }
}
