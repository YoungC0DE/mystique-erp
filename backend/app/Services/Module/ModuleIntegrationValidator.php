<?php

namespace App\Services\Module;

use App\Models\DatabaseConnection;
use App\Services\Connection\ConnectionTester;
use Illuminate\Validation\ValidationException;

class ModuleIntegrationValidator
{
    public function __construct(
        private readonly ConnectionTester $tester,
    ) {}

    /**
     * Valida conexão, colunas selecionadas e coluna de status na tabela externa.
     *
     * @param  list<string>  $columnNames
     * @return array<string, string> mapa nome => tipo do banco
     */
    public function validate(DatabaseConnection $connection, string $statusColumn, array $columnNames): array
    {
        $config = [
            'host' => $connection->host,
            'port' => $connection->port,
            'database' => $connection->database,
            'username' => $connection->username,
            'password' => $connection->password,
            'table_name' => $connection->table_name,
        ];

        $available = collect($this->tester->listColumns($config));
        $availableNames = $available->pluck('name')->all();
        $typeMap = $available->mapWithKeys(fn (array $col) => [$col['name'] => $col['type']])->all();

        if (! in_array($statusColumn, $availableNames, true)) {
            throw ValidationException::withMessages([
                'status_column' => [__('modules.status_column_missing', ['column' => $statusColumn])],
            ]);
        }

        $missing = array_values(array_diff($columnNames, $availableNames));

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'columns' => [__('modules.columns_missing', ['columns' => implode(', ', $missing)])],
            ]);
        }

        if (! in_array($statusColumn, $columnNames, true)) {
            throw ValidationException::withMessages([
                'status_column' => [__('modules.status_column_not_selected')],
            ]);
        }

        return $typeMap;
    }
}
