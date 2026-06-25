<?php

namespace Tests\Feature\Module;

use App\Models\DatabaseConnection;
use App\Models\Module;
use App\Services\Connection\ConnectionTester;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function modulePayload(DatabaseConnection $connection): array
    {
        return [
            'name' => 'Vendas',
            'connection_id' => $connection->uuid,
            'status_column' => 'status',
            'callback_url' => 'https://example.com/callback',
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

    public function test_create_module_requires_connection(): void
    {
        $user = $this->userWithPermissions(['create']);

        $this->actingAsApi($user)
            ->postJson('/api/modules', ['name' => 'Vendas'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['connection_id', 'status_column', 'columns']);
    }

    public function test_admin_can_create_integrated_module(): void
    {
        $connection = DatabaseConnection::factory()->create();
        $this->mockColumns();

        $admin = $this->admin();

        $this->actingAsApi($admin)
            ->postJson('/api/modules', $this->modulePayload($connection))
            ->assertCreated()
            ->assertJsonPath('data.name', 'Vendas')
            ->assertJsonPath('data.is_integrated', true)
            ->assertJsonPath('data.status_column', 'status')
            ->assertJsonCount(4, 'data.statuses')
            ->assertJsonCount(2, 'data.fields');

        $this->assertDatabaseHas('modules', [
            'name' => 'Vendas',
            'status_column' => 'status',
        ]);
    }

    public function test_create_module_rejects_missing_columns_in_table(): void
    {
        $connection = DatabaseConnection::factory()->create();

        $this->mock(ConnectionTester::class, function ($mock) {
            $mock->shouldReceive('listColumns')->andReturn([
                ['name' => 'id', 'type' => 'int'],
                ['name' => 'status', 'type' => 'varchar'],
            ]);
        });

        $user = $this->userWithPermissions(['create']);

        $this->actingAsApi($user)
            ->postJson('/api/modules', $this->modulePayload($connection))
            ->assertStatus(422)
            ->assertJsonValidationErrors('columns');
    }

    public function test_integrated_module_rejects_record_creation(): void
    {
        $connection = DatabaseConnection::factory()->create();
        $this->mockColumns();

        $user = $this->userWithPermissions(['create']);

        $response = $this->actingAsApi($user)
            ->postJson('/api/modules', $this->modulePayload($connection))
            ->assertCreated();

        $module = Module::where('uuid', $response->json('data.id'))->firstOrFail();

        $this->actingAsApi($user)
            ->postJson("/api/modules/{$module->uuid}/records", [])
            ->assertForbidden()
            ->assertJsonPath('message', __('modules.integrated_read_only'));
    }

    public function test_integrated_module_rejects_field_creation(): void
    {
        $connection = DatabaseConnection::factory()->create();
        $this->mockColumns();

        $user = $this->userWithPermissions(['create', 'update']);

        $response = $this->actingAsApi($user)
            ->postJson('/api/modules', $this->modulePayload($connection))
            ->assertCreated();

        $this->actingAsApi($user)
            ->postJson("/api/modules/{$response->json('data.id')}/fields", [
                'label' => 'Extra',
                'type' => 'texto',
            ])
            ->assertForbidden()
            ->assertJsonPath('message', __('modules.integrated_fields_read_only'));
    }
}
