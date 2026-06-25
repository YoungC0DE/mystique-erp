<?php

namespace App\Services\Module;

use App\Enums\FieldType;
use App\Models\Module;
use App\Models\ModuleField;
use App\Models\ModuleKanbanStatus;
use App\Support\KanbanStatusAccents;
use App\Services\Connection\ConnectionTester;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use stdClass;

class ExternalBoardReader
{
    public function __construct(
        private readonly ConnectionTester $connectionTester,
        private readonly FieldFilterApplier $filterApplier,
    ) {}

    /**
     * Monta o board lendo a tabela externa do módulo, com paginação por status.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function board(Module $module, array $filters = [], int $perPage = 15): array
    {
        $module->loadMissing(['connection', 'fields', 'kanbanStatuses']);

        $connection = $module->connection;

        if ($connection === null) {
            throw ValidationException::withMessages([
                'connection' => [__('modules.connection_required_for_board')],
            ]);
        }

        if ($module->status_column === null || $module->status_column === '') {
            throw ValidationException::withMessages([
                'status_column' => [__('modules.status_column_required_for_board')],
            ]);
        }

        $this->assertSafeIdentifier($module->status_column, 'status_column');
        $this->assertSafeIdentifier($connection->table_name, 'table_name');

        $fieldKeys = $module->fields->pluck('key')->all();

        foreach ($fieldKeys as $key) {
            $this->assertSafeIdentifier($key, 'columns');
        }

        $config = [
            'host' => $connection->host,
            'port' => $connection->port,
            'database' => $connection->database,
            'username' => $connection->username,
            'password' => $connection->password,
            'table_name' => $connection->table_name,
        ];

        return $this->connectionTester->withConnection($config, function (string $connectionName) use (
            $module,
            $connection,
            $fieldKeys,
            $filters,
            $perPage,
        ) {
            $columns = [];

            foreach ($module->kanbanStatuses as $status) {
                $paginator = $this->paginateStatus(
                    $connectionName,
                    $connection->table_name,
                    $module,
                    $status,
                    $fieldKeys,
                    $filters,
                    $perPage,
                );

                $columns[] = [
                    'key' => $status->slug,
                    'label' => $status->label,
                    'color' => KanbanStatusAccents::for($status->slug),
                    'records' => collect($paginator->items())
                        ->map(fn (stdClass $row) => $this->mapRecord($row, $status->slug, $module->fields))
                        ->values()
                        ->all(),
                    'meta' => [
                        'current_page' => $paginator->currentPage(),
                        'last_page' => $paginator->lastPage(),
                        'per_page' => $paginator->perPage(),
                        'total' => $paginator->total(),
                    ],
                ];
            }

            return $columns;
        });
    }

    /**
     * Busca um registro externo pelo id (PK) e retorna no formato do board.
     *
     * @return array<string, mixed>|null
     */
    public function findRecord(Module $module, string $externalId): ?array
    {
        $module->loadMissing(['connection', 'fields', 'kanbanStatuses']);

        $connection = $module->connection;

        if ($connection === null || $module->status_column === null || $module->status_column === '') {
            return null;
        }

        $this->assertSafeIdentifier($module->status_column, 'status_column');
        $this->assertSafeIdentifier($connection->table_name, 'table_name');

        $fieldKeys = $module->fields->pluck('key')->all();

        foreach ($fieldKeys as $key) {
            $this->assertSafeIdentifier($key, 'columns');
        }

        $selectColumns = array_values(array_unique(array_merge(['id', $module->status_column], $fieldKeys)));

        $config = [
            'host' => $connection->host,
            'port' => $connection->port,
            'database' => $connection->database,
            'username' => $connection->username,
            'password' => $connection->password,
            'table_name' => $connection->table_name,
        ];

        return $this->connectionTester->withConnection($config, function (string $connectionName) use (
            $module,
            $connection,
            $selectColumns,
            $externalId,
        ) {
            $row = DB::connection($connectionName)
                ->table($connection->table_name)
                ->select($selectColumns)
                ->where('id', $externalId)
                ->first();

            if ($row === null) {
                return null;
            }

            $statusSlug = $this->resolveStatusSlug($module, (string) ($row->{$module->status_column} ?? ''));

            return $this->mapRecord($row, $statusSlug, $module->fields);
        });
    }

    private function resolveStatusSlug(Module $module, string $externalValue): string
    {
        $status = $module->kanbanStatuses->firstWhere('external_value', $externalValue);

        return $status?->slug ?? $externalValue;
    }

    /**
     * @param  list<string>  $fieldKeys
     * @param  array<string, mixed>  $filters
     */
    private function paginateStatus(
        string $connectionName,
        string $table,
        Module $module,
        ModuleKanbanStatus $status,
        array $fieldKeys,
        array $filters,
        int $perPage,
    ): LengthAwarePaginator {
        $selectColumns = array_values(array_unique(array_merge(['id'], $fieldKeys)));

        $query = DB::connection($connectionName)
            ->table($table)
            ->select($selectColumns)
            ->where($module->status_column, $status->external_value);

        if (! empty($filters['q'])) {
            $search = (string) $filters['q'];

            $query->where(function ($builder) use ($fieldKeys, $search) {
                foreach ($fieldKeys as $key) {
                    $builder->orWhere($key, 'like', '%'.$search.'%');
                }
            });
        }

        if (! empty($filters['field_filters']) && is_array($filters['field_filters'])) {
            $this->filterApplier->applyToExternalQuery($query, $module, $filters['field_filters']);
        }

        $pageName = $status->slug.'_page';
        $page = max(1, (int) ($filters[$pageName] ?? 1));

        return $query
            ->orderByDesc('id')
            ->paginate($perPage, $selectColumns, $pageName, $page);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, ModuleField>  $fields
     * @return array<string, mixed>
     */
    private function mapRecord(stdClass $row, string $statusSlug, $fields): array
    {
        $values = [];

        foreach ($fields as $field) {
            $raw = $row->{$field->key} ?? null;
            $values[$field->key] = $this->castValue($field->type, $raw);
        }

        return [
            'id' => (string) $row->id,
            'status' => $statusSlug,
            'values' => $values,
        ];
    }

    private function castValue(FieldType $type, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            FieldType::BOOLEAN => (bool) $value,
            FieldType::NUMBER => (int) $value,
            FieldType::DECIMAL => (float) $value,
            FieldType::MULTISELECT => is_string($value) ? (json_decode($value, true) ?? []) : $value,
            default => is_scalar($value) ? (string) $value : $value,
        };
    }

    private function assertSafeIdentifier(string $name, string $field): void
    {
        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw ValidationException::withMessages([
                $field => [__('modules.invalid_identifier', ['name' => $name])],
            ]);
        }
    }
}
