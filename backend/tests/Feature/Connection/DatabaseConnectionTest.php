<?php

namespace Tests\Feature\Connection;

use App\Models\DatabaseConnection;
use App\Services\Connection\ConnectionTester;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseConnectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'Pedidos ERP',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'erp',
            'username' => 'root',
            'password' => 'secret',
            'table_name' => 'pedidos',
        ];
    }

    public function test_admin_can_list_connections(): void
    {
        DatabaseConnection::factory()->count(2)->create();

        $this->actingAsApi($this->admin())
            ->getJson('/api/connections')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_non_admin_cannot_list_connections(): void
    {
        $this->actingAsApi($this->userWithPermissions(['read']))
            ->getJson('/api/connections')
            ->assertForbidden();
    }

    public function test_admin_can_create_connection(): void
    {
        $this->mock(ConnectionTester::class, function ($mock) {
            $mock->shouldReceive('test')->once();
        });

        $this->actingAsApi($this->admin())
            ->postJson('/api/connections', $this->validPayload())
            ->assertCreated()
            ->assertJsonPath('data.name', 'Pedidos ERP')
            ->assertJsonPath('data.has_password', true)
            ->assertJsonMissingPath('data.password');

        $this->assertDatabaseHas('database_connections', [
            'name' => 'Pedidos ERP',
            'host' => '127.0.0.1',
            'table_name' => 'pedidos',
        ]);

        $this->assertDatabaseHas('activity_logs', ['action' => 'connection.created']);
    }

    public function test_create_connection_validates_fields(): void
    {
        $this->actingAsApi($this->admin())
            ->postJson('/api/connections', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'host', 'port', 'database', 'username', 'password', 'table_name']);
    }

    public function test_admin_can_update_connection(): void
    {
        $connection = DatabaseConnection::factory()->create(['name' => 'Antiga']);

        $this->mock(ConnectionTester::class, function ($mock) {
            $mock->shouldReceive('test')->once();
        });

        $this->actingAsApi($this->admin())
            ->putJson("/api/connections/{$connection->uuid}", ['name' => 'Nova'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nova');

        $this->assertDatabaseHas('activity_logs', ['action' => 'connection.updated']);
    }

    public function test_admin_can_delete_connection(): void
    {
        $connection = DatabaseConnection::factory()->create();

        $this->actingAsApi($this->admin())
            ->deleteJson("/api/connections/{$connection->uuid}")
            ->assertOk();

        $this->assertDatabaseMissing('database_connections', ['id' => $connection->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'connection.deleted']);
    }

    public function test_admin_can_test_connection(): void
    {
        $connection = DatabaseConnection::factory()->create();

        $this->mock(ConnectionTester::class, function ($mock) {
            $mock->shouldReceive('test')->once();
        });

        $this->actingAsApi($this->admin())
            ->postJson("/api/connections/{$connection->uuid}/test")
            ->assertOk()
            ->assertJsonPath('message', __('connections.test_success'));

        $this->assertDatabaseHas('activity_logs', ['action' => 'connection.tested']);
    }

    public function test_admin_can_list_columns(): void
    {
        $connection = DatabaseConnection::factory()->create();

        $this->mock(ConnectionTester::class, function ($mock) {
            $mock->shouldReceive('listColumns')
                ->once()
                ->andReturn([
                    ['name' => 'id', 'type' => 'int'],
                    ['name' => 'status', 'type' => 'varchar'],
                ]);
        });

        $this->actingAsApi($this->admin())
            ->getJson("/api/connections/{$connection->uuid}/columns")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'id');
    }

    public function test_non_admin_cannot_create_connection(): void
    {
        $this->actingAsApi($this->userWithPermissions(['create']))
            ->postJson('/api/connections', $this->validPayload())
            ->assertForbidden();
    }

    public function test_admin_can_validate_connection_without_persisting(): void
    {
        $this->mock(ConnectionTester::class, function ($mock) {
            $mock->shouldReceive('test')->once();
        });

        $this->actingAsApi($this->admin())
            ->postJson('/api/connections/validate', $this->validPayload())
            ->assertOk()
            ->assertJsonPath('message', __('connections.test_success'));

        $this->assertDatabaseCount('database_connections', 0);
    }
}
