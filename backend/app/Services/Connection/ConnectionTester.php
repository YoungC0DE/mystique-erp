<?php

namespace App\Services\Connection;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ConnectionTester
{
    /**
     * @param  array{host: string, port: int|string, database: string, username: string, password: string, table_name?: string}  $config
     */
    public function test(array $config): void
    {
        $connectionName = $this->registerConnection($config);

        try {
            DB::connection($connectionName)->getPdo();

            if (! empty($config['table_name']) && ! Schema::connection($connectionName)->hasTable($config['table_name'])) {
                throw ValidationException::withMessages([
                    'table_name' => [__('connections.table_not_found', ['table' => $config['table_name']])],
                ]);
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'connection' => [__('connections.test_failed', ['message' => $e->getMessage()])],
            ]);
        } finally {
            DB::purge($connectionName);
        }
    }

    /**
     * @param  array{host: string, port: int|string, database: string, username: string, password: string, table_name: string}  $config
     * @return list<array{name: string, type: string}>
     */
    public function listColumns(array $config): array
    {
        $this->test($config);

        $connectionName = $this->registerConnection($config);

        try {
            $columns = Schema::connection($connectionName)->getColumns($config['table_name']);

            return collect($columns)
                ->map(fn (array $column) => [
                    'name' => $column['name'],
                    'type' => $column['type_name'] ?? $column['type'] ?? 'string',
                ])
                ->values()
                ->all();
        } finally {
            DB::purge($connectionName);
        }
    }

    /**
     * Executa callback com conexão dinâmica registrada e removida ao final.
     *
     * @template TReturn
     *
     * @param  array{host: string, port: int|string, database: string, username: string, password: string, table_name?: string}  $config
     * @param  callable(string): TReturn  $callback
     * @return TReturn
     */
    public function withConnection(array $config, callable $callback): mixed
    {
        $connectionName = $this->registerConnection($config);

        try {
            return $callback($connectionName);
        } finally {
            DB::purge($connectionName);
        }
    }

    /**
     * @param  array{host: string, port: int|string, database: string, username: string, password: string, table_name?: string}  $config
     */
    private function registerConnection(array $config): string
    {
        $connectionName = 'external_'.Str::random(12);

        config(['database.connections.'.$connectionName => [
            'driver' => 'mysql',
            'host' => $config['host'],
            'port' => (int) ($config['port'] ?? 3306),
            'database' => $config['database'],
            'username' => $config['username'],
            'password' => $config['password'],
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]]);

        return $connectionName;
    }
}
