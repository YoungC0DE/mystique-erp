<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ApiErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_error_returns_422_with_errors(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonValidationErrors(['email', 'password']);

        $this->assertStringContainsString('obrigatória', $response->json('message'));
        $this->assertStringContainsString(
            'obrigatória',
            $response->json('errors.email.0'),
        );
        $this->assertStringContainsString(
            'obrigatória',
            $response->json('errors.password.0'),
        );
        $this->assertStringNotContainsString('required', strtolower($response->json('message')));
    }

    public function test_unauthenticated_request_returns_401_json(): void
    {
        $response = $this->getJson('/api/modules');

        $response->assertStatus(401)
            ->assertJson(['message' => 'Não autenticado.']);

        $this->assertNull($response->json('trace'));
        $this->assertNull($response->json('exception'));
    }

    public function test_missing_permission_returns_403_json(): void
    {
        $user = $this->userWithPermissions(['read']);

        $this->actingAsApi($user)
            ->postJson('/api/modules', ['name' => 'Bloqueado'])
            ->assertStatus(403)
            ->assertJsonStructure(['message']);
    }

    public function test_unknown_resource_returns_404_json(): void
    {
        $user = $this->userWithPermissions(['read']);

        $response = $this->actingAsApi($user)->getJson('/api/modules/inexistente');

        $response->assertStatus(404)
            ->assertJson(['message' => 'Recurso não encontrado.']);
    }

    public function test_unknown_route_returns_404_json(): void
    {
        $response = $this->getJson('/api/rota-que-nao-existe');

        $response->assertStatus(404)
            ->assertJsonStructure(['message']);
    }

    public function test_unexpected_error_returns_500_without_stack_trace(): void
    {
        Route::get('/api/_test/boom', function () {
            throw new RuntimeException('Segredo interno não deve vazar.');
        });

        $response = $this->getJson('/api/_test/boom');

        $response->assertStatus(500)
            ->assertJson(['message' => 'Ocorreu um erro inesperado.']);

        $this->assertNull($response->json('trace'));
        $this->assertStringNotContainsString('Segredo interno', $response->json('message'));
    }
}
