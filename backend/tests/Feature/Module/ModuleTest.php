<?php

namespace Tests\Feature\Module;

use App\Models\DatabaseConnection;
use App\Models\Module;
use App\Services\Connection\ConnectionTester;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function modulePayload(string $name = 'Vendas', ?DatabaseConnection $connection = null): array
    {
        $connection ??= DatabaseConnection::factory()->create();

        return [
            'name' => $name,
            'connection_id' => $connection->uuid,
            'status_column' => 'status',
            'columns' => [
                ['name' => 'cliente', 'label' => 'Cliente'],
                ['name' => 'status', 'label' => 'Status'],
            ],
        ];
    }

    private function mockColumns(): void
    {
        $this->mock(ConnectionTester::class, function ($mock) {
            $mock->shouldReceive('listColumns')->andReturn([
                ['name' => 'id', 'type' => 'int'],
                ['name' => 'cliente', 'type' => 'varchar'],
                ['name' => 'status', 'type' => 'varchar'],
            ]);
        });
    }

    public function test_user_can_list_modules(): void
    {
        $user = $this->userWithPermissions(['read']);
        Module::factory()->count(3)->create();

        $this->actingAsApi($user)
            ->getJson('/api/modules')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_listing_modules_requires_read_permission(): void
    {
        $user = $this->userWithPermissions([]);

        $this->actingAsApi($user)->getJson('/api/modules')->assertForbidden();
    }

    public function test_user_can_create_module(): void
    {
        $this->mockColumns();
        $user = $this->userWithPermissions(['create']);

        $this->actingAsApi($user)
            ->postJson('/api/modules', $this->modulePayload())
            ->assertCreated()
            ->assertJsonPath('data.name', 'Vendas');

        $this->assertDatabaseHas('modules', ['name' => 'Vendas']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'module.created']);
    }

    public function test_creating_module_requires_create_permission(): void
    {
        $user = $this->userWithPermissions(['read']);

        $this->actingAsApi($user)
            ->postJson('/api/modules', $this->modulePayload())
            ->assertForbidden();
    }

    public function test_create_module_validates_name(): void
    {
        $user = $this->userWithPermissions(['create']);

        $this->actingAsApi($user)
            ->postJson('/api/modules', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_user_can_update_module(): void
    {
        $user = $this->userWithPermissions(['update']);
        $module = Module::factory()->create(['name' => 'Antigo']);

        $this->actingAsApi($user)
            ->putJson("/api/modules/{$module->uuid}", ['name' => 'Novo'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Novo');

        $this->assertDatabaseHas('modules', ['id' => $module->id, 'name' => 'Novo']);
    }

    public function test_user_can_delete_module(): void
    {
        $user = $this->userWithPermissions(['delete']);
        $module = Module::factory()->create();

        $this->actingAsApi($user)
            ->deleteJson("/api/modules/{$module->uuid}")
            ->assertOk();

        $this->assertDatabaseMissing('modules', ['id' => $module->id]);
    }

    public function test_admin_can_create_module_without_extra_fields(): void
    {
        $this->mockColumns();
        $admin = $this->admin();

        $this->actingAsApi($admin)
            ->postJson('/api/modules', $this->modulePayload('Financeiro'))
            ->assertCreated();

        $this->assertDatabaseHas('modules', ['name' => 'Financeiro']);
    }

    public function test_allowed_modules_returns_only_active_modules(): void
    {
        $user = $this->userWithPermissions(['read']);
        Module::factory()->create(['name' => 'Ativo']);
        Module::factory()->inactive()->create(['name' => 'Inativo']);

        $response = $this->actingAsApi($user)->getJson('/api/me/modules')->assertOk();

        $names = collect($response->json('data'))->pluck('name');
        $this->assertContains('Ativo', $names);
        $this->assertNotContains('Inativo', $names);
    }
}
