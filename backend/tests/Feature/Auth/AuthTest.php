<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

class AuthTest extends TestCase
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

    private function createUser(string $password = 'senha123'): User
    {
        return User::factory()->create([
            'email' => 'user@example.com',
            'password' => $password,
        ]);
    }

    public function test_login_returns_user_and_tokens(): void
    {
        $this->createUser();

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'senha123',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.email', 'user@example.com')
            ->assertJsonStructure([
                'user' => ['id', 'email', 'is_admin', 'permissions'],
                'token' => ['access_token', 'refresh_token', 'expires_in', 'token_type'],
            ]);

        $this->assertDatabaseHas('activity_logs', ['action' => 'login']);
    }

    public function test_login_with_invalid_credentials_fails(): void
    {
        $this->createUser();

        $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'senha-errada',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->postJson('/api/auth/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_refresh_token_issues_new_tokens(): void
    {
        $this->createUser();

        $tokens = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'senha123',
        ])->json('token');

        $this->postJson('/api/auth/refresh', [
            'refresh_token' => $tokens['refresh_token'],
        ])->assertOk()->assertJsonStructure(['token' => ['access_token', 'refresh_token']]);

        $this->assertDatabaseHas('activity_logs', ['action' => 'token.refresh']);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_authenticated_user_can_fetch_profile_with_token(): void
    {
        $this->createUser();

        $token = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'senha123',
        ])->json('token.access_token');

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'user@example.com');
    }

    public function test_logout_revokes_the_access_token(): void
    {
        $user = $this->createUser();

        $token = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'senha123',
        ])->json('token.access_token');

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();

        $this->assertDatabaseHas('activity_logs', ['action' => 'logout']);

        $this->assertTrue(
            $user->tokens()->where('revoked', true)->exists(),
            'O access token deveria estar revogado após o logout.'
        );
    }
}
