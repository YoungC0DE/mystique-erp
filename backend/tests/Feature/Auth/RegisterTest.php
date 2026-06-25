<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('passport:keys', ['--force' => true]);

        $client = app(ClientRepository::class)->createPasswordGrantClient(
            'Testing Password Grant',
            'users',
            confidential: true,
        );

        config([
            'auth_tokens.password_client_id' => $client->getKey(),
            'auth_tokens.password_client_secret' => $client->plainSecret,
        ]);
    }

    public function test_register_is_forbidden_when_disabled(): void
    {
        config(['mystique.registration_enabled' => false]);

        $this->postJson('/api/auth/register', [
            'name' => 'Novo Usuário',
            'email' => 'novo@example.com',
            'password' => 'senha1234',
        ])
            ->assertForbidden()
            ->assertJsonPath('message', __('auth.registration_disabled'));

        $this->assertDatabaseCount('users', 0);
    }

    public function test_register_creates_user_and_returns_tokens_when_enabled(): void
    {
        config(['mystique.registration_enabled' => true]);

        User::factory()->create(['is_admin' => true]);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Novo Usuário',
            'email' => 'novo@example.com',
            'password' => 'senha1234',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'novo@example.com')
            ->assertJsonPath('user.is_admin', false)
            ->assertJsonStructure([
                'user' => ['id', 'email', 'is_admin', 'permissions'],
                'token' => ['access_token', 'refresh_token', 'expires_in', 'token_type'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'novo@example.com',
            'is_admin' => false,
        ]);

        $this->assertDatabaseHas('activity_logs', ['action' => 'auth.register']);
    }

    public function test_first_registered_user_becomes_admin(): void
    {
        config(['mystique.registration_enabled' => true]);

        $this->postJson('/api/auth/register', [
            'name' => 'Admin Inicial',
            'email' => 'admin@example.com',
            'password' => 'senha1234',
        ])
            ->assertCreated()
            ->assertJsonPath('user.is_admin', true);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'is_admin' => true,
        ]);
    }

    public function test_subsequent_registrations_are_not_admin(): void
    {
        config(['mystique.registration_enabled' => true]);

        User::factory()->create(['email' => 'existing@example.com']);

        $this->postJson('/api/auth/register', [
            'name' => 'Segundo Usuário',
            'email' => 'segundo@example.com',
            'password' => 'senha1234',
        ])
            ->assertCreated()
            ->assertJsonPath('user.is_admin', false);
    }

    public function test_register_validates_required_fields(): void
    {
        config(['mystique.registration_enabled' => true]);

        $this->postJson('/api/auth/register', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        config(['mystique.registration_enabled' => true]);

        User::factory()->create(['email' => 'duplicado@example.com']);

        $this->postJson('/api/auth/register', [
            'name' => 'Outro',
            'email' => 'duplicado@example.com',
            'password' => 'senha1234',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }
}
