<?php

namespace Tests\Support;

use App\Models\DatabaseConnection;
use App\Services\Connection\ConnectionTester;

trait CreatesModulePayload
{
    /**
     * @return array<string, mixed>
     */
    protected function modulePayload(string $name = 'Vendas', ?DatabaseConnection $connection = null): array
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

    protected function mockModuleTableColumns(): void
    {
        $this->mock(ConnectionTester::class, function ($mock) {
            $mock->shouldReceive('listColumns')->andReturn([
                ['name' => 'id', 'type' => 'int'],
                ['name' => 'cliente', 'type' => 'varchar'],
                ['name' => 'status', 'type' => 'varchar'],
            ]);
        });
    }
}
