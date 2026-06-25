<?php

namespace App\Services\Module;

use App\Models\Module;
use App\Models\ModuleField;
use App\Services\Connection\ConnectionTester;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use stdClass;

class ReportRunner
{
    public function __construct(
        private readonly ConnectionTester $connectionTester,
        private readonly FieldFilterApplier $filterApplier,
    ) {}

    /**
     * @param  list<string>  $fieldKeys
     * @param  list<array<string, mixed>>  $filters
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     */
    public function run(
        Module $module,
        array $fieldKeys,
        array $filters = [],
        int $perPage = 25,
        int $page = 1,
    ): array {
        $module->loadMissing(['connection', 'fields', 'kanbanStatuses']);

        $fields = $this->resolveFields($module, $fieldKeys);
        $normalizedFilters = $this->filterApplier->normalize($filters, $module);

        if ($module->isIntegrated()) {
            return $this->runIntegrated($module, $fields, $normalizedFilters, $perPage, $page);
        }

        return $this->runEav($module, $fields, $normalizedFilters, $perPage, $page);
    }

    /**
     * @param  list<string>  $fieldKeys
     * @return \Illuminate\Support\Collection<int, ModuleField>
     */
    private function resolveFields(Module $module, array $fieldKeys): \Illuminate\Support\Collection
    {
        if ($fieldKeys === []) {
            throw ValidationException::withMessages([
                'field_keys' => [__('modules.report_fields_required')],
            ]);
        }

        $fields = $module->fields->whereIn('key', $fieldKeys)->values();

        if ($fields->count() !== count($fieldKeys)) {
            throw ValidationException::withMessages([
                'field_keys' => [__('modules.report_invalid_fields')],
            ]);
        }

        return $fields->sortBy(fn (ModuleField $field) => array_search($field->key, $fieldKeys, true));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ModuleField>  $fields
     * @param  list<array<string, mixed>>  $filters
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     */
    private function runIntegrated(
        Module $module,
        \Illuminate\Support\Collection $fields,
        array $filters,
        int $perPage,
        int $page,
    ): array {
        $connection = $module->connection;

        if ($connection === null) {
            throw ValidationException::withMessages([
                'module' => [__('modules.connection_required_for_board')],
            ]);
        }

        $this->assertSafeIdentifier($connection->table_name, 'table_name');

        $fieldKeys = $fields->pluck('key')->all();

        foreach ($fieldKeys as $key) {
            $this->assertSafeIdentifier($key, 'columns');
        }

        $selectColumns = array_values(array_unique(array_merge(['id'], $fieldKeys)));

        if ($module->status_column) {
            $this->assertSafeIdentifier($module->status_column, 'status_column');
            $selectColumns[] = $module->status_column;
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
            $connection,
            $module,
            $fields,
            $fieldKeys,
            $selectColumns,
            $filters,
            $perPage,
            $page,
        ) {
            $query = DB::connection($connectionName)
                ->table($connection->table_name)
                ->select($selectColumns);

            if (! empty($filters)) {
                $this->filterApplier->applyToExternalQuery($query, $module, $filters);
            }

            /** @var LengthAwarePaginator $paginator */
            $paginator = $query->orderByDesc('id')->paginate($perPage, $selectColumns, 'page', $page);

            $data = collect($paginator->items())
                ->map(fn (stdClass $row) => $this->mapRow($row, $fields, $module))
                ->values()
                ->all();

            return [
                'data' => $data,
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ];
        });
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ModuleField>  $fields
     * @param  list<array<string, mixed>>  $filters
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     */
    private function runEav(
        Module $module,
        \Illuminate\Support\Collection $fields,
        array $filters,
        int $perPage,
        int $page,
    ): array {
        $query = $module->records()->with('values.field');

        if (! empty($filters)) {
            $this->filterApplier->applyToEavQuery($query, $module, $filters);
        }

        $paginator = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        $data = collect($paginator->items())
            ->map(function ($record) use ($fields) {
                $row = ['id' => $record->uuid, 'status' => $record->status];

                foreach ($fields as $field) {
                    $value = $record->values->firstWhere('field_id', $field->getKey());
                    $row[$field->key] = $value?->value;
                }

                return $row;
            })
            ->values()
            ->all();

        return [
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ModuleField>  $fields
     * @return array<string, mixed>
     */
    private function mapRow(stdClass $row, \Illuminate\Support\Collection $fields, Module $module): array
    {
        $mapped = ['id' => (string) $row->id];

        if ($module->status_column && isset($row->{$module->status_column})) {
            $mapped['status'] = (string) $row->{$module->status_column};
        }

        foreach ($fields as $field) {
            $raw = $row->{$field->key} ?? null;
            $mapped[$field->key] = $raw;
        }

        return $mapped;
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
