<?php

namespace App\Services\Connection;

use App\Enums\ActivityAction;
use App\Models\DatabaseConnection;
use App\Repositories\DatabaseConnectionRepository;
use App\Services\ActivityLog\ActivityLogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ConnectionService
{
    public function __construct(
        private readonly DatabaseConnectionRepository $connections,
        private readonly ConnectionTester $tester,
        private readonly ActivityLogger $logger,
    ) {}

    public function list(): Collection
    {
        return $this->connections->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): DatabaseConnection
    {
        $this->tester->test($this->connectionConfig($data));

        return DB::transaction(function () use ($data) {
            $connection = $this->connections->create([
                'name' => $data['name'],
                'host' => $data['host'],
                'port' => $data['port'] ?? 3306,
                'database' => $data['database'],
                'username' => $data['username'],
                'password' => $data['password'],
                'table_name' => $data['table_name'],
            ]);

            $this->logger->log(
                ActivityAction::CONNECTION_CREATED,
                "Conexão '{$connection->name}' criada.",
                subject: $connection,
            );

            return $connection;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(DatabaseConnection $connection, array $data): DatabaseConnection
    {
        $config = $this->connectionConfig([
            'host' => $data['host'] ?? $connection->host,
            'port' => $data['port'] ?? $connection->port,
            'database' => $data['database'] ?? $connection->database,
            'username' => $data['username'] ?? $connection->username,
            'password' => $data['password'] ?? $connection->password,
            'table_name' => $data['table_name'] ?? $connection->table_name,
        ]);

        $this->tester->test($config);

        return DB::transaction(function () use ($connection, $data) {
            $payload = [
                'name' => $data['name'] ?? $connection->name,
                'host' => $data['host'] ?? $connection->host,
                'port' => $data['port'] ?? $connection->port,
                'database' => $data['database'] ?? $connection->database,
                'username' => $data['username'] ?? $connection->username,
                'table_name' => $data['table_name'] ?? $connection->table_name,
            ];

            if (! empty($data['password'])) {
                $payload['password'] = $data['password'];
            }

            $connection = $this->connections->update($connection, $payload);

            $this->logger->log(
                ActivityAction::CONNECTION_UPDATED,
                "Conexão '{$connection->name}' atualizada.",
                subject: $connection,
            );

            return $connection;
        });
    }

    public function delete(DatabaseConnection $connection): void
    {
        $name = $connection->name;

        $this->connections->delete($connection);

        $this->logger->log(
            ActivityAction::CONNECTION_DELETED,
            "Conexão '{$name}' removida.",
        );
    }

    public function test(DatabaseConnection $connection): void
    {
        $this->tester->test($this->configFromModel($connection));

        $this->logger->log(
            ActivityAction::CONNECTION_TESTED,
            "Conexão '{$connection->name}' testada com sucesso.",
            subject: $connection,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function validatePayload(array $data): void
    {
        $this->tester->test($this->connectionConfig($data));
    }

    /**
     * @return list<array{name: string, type: string}>
     */
    public function columns(DatabaseConnection $connection): array
    {
        return $this->tester->listColumns($this->configFromModel($connection));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{host: string, port: int, database: string, username: string, password: string, table_name: string}
     */
    private function connectionConfig(array $data): array
    {
        return [
            'host' => (string) $data['host'],
            'port' => (int) ($data['port'] ?? 3306),
            'database' => (string) $data['database'],
            'username' => (string) $data['username'],
            'password' => (string) $data['password'],
            'table_name' => (string) $data['table_name'],
        ];
    }

    /**
     * @return array{host: string, port: int, database: string, username: string, password: string, table_name: string}
     */
    private function configFromModel(DatabaseConnection $connection): array
    {
        return [
            'host' => $connection->host,
            'port' => $connection->port,
            'database' => $connection->database,
            'username' => $connection->username,
            'password' => $connection->password,
            'table_name' => $connection->table_name,
        ];
    }
}
