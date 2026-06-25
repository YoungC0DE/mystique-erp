<?php

namespace App\Services\Module;

use App\Enums\ActivityAction;
use App\Enums\FieldType;
use App\Models\DatabaseConnection;
use App\Models\Module;
use App\Models\User;
use App\Repositories\ModuleRepository;
use App\Services\ActivityLog\ActivityLogger;
use App\Support\Cache\ModuleStructuralCache;
use App\Support\DefaultKanbanStatuses;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ModuleService
{
    public function __construct(
        private readonly ModuleRepository $modules,
        private readonly ModuleIntegrationValidator $integrationValidator,
        private readonly ActivityLogger $logger,
        private readonly ModuleStructuralCache $cache,
    ) {}

    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return $this->modules->paginate($perPage);
    }

    /**
     * @return Collection<int, Module>
     */
    public function allowedModules(): Collection
    {
        return $this->modules->activeModules();
    }

    public function findByUuid(string $uuid): ?Module
    {
        return $this->modules->findByUuid($uuid);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): Module
    {
        return DB::transaction(function () use ($data) {
            $connection = $this->resolveConnection($data['connection_id']);
            $columnNames = $this->extractColumnNames($data['columns']);
            $typeMap = $this->integrationValidator->validate($connection, $data['status_column'], $columnNames);

            $module = $this->modules->create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Str::slug($data['name']),
                'icon' => $data['icon'] ?? null,
                'status' => $data['status'] ?? 'active',
                'connection_id' => $connection->id,
                'callback_url' => $data['callback_url'] ?? null,
                'callback_method' => $data['callback_method'] ?? 'POST',
                'status_column' => $data['status_column'],
            ]);

            $this->syncStatuses($module, $data['statuses'] ?? DefaultKanbanStatuses::definitions());
            $this->syncFields($module, $data['columns'], $typeMap);

            $this->logger->log(
                ActivityAction::MODULE_CREATED,
                "Módulo '{$module->name}' criado.",
                subject: $module,
            );

            $module = $this->loadIntegrationRelations($module);
            $this->cache->store($module);

            return $module;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Module $module, array $data): Module
    {
        return DB::transaction(function () use ($module, $data) {
            $integrationPayload = $this->integrationPayload($module, $data);

            if ($integrationPayload !== null) {
                $connection = $this->resolveConnection($integrationPayload['connection_id']);
                $columnNames = $this->extractColumnNames($integrationPayload['columns']);
                $typeMap = $this->integrationValidator->validate(
                    $connection,
                    $integrationPayload['status_column'],
                    $columnNames,
                );

                $module = $this->modules->update($module, [
                    'name' => $data['name'] ?? $module->name,
                    'slug' => $data['slug'] ?? $module->slug,
                    'icon' => array_key_exists('icon', $data) ? $data['icon'] : $module->icon,
                    'status' => $data['status'] ?? $module->status->value,
                    'connection_id' => $connection->id,
                    'callback_url' => $integrationPayload['callback_url'],
                    'callback_method' => $integrationPayload['callback_method'],
                    'status_column' => $integrationPayload['status_column'],
                ]);

                if (array_key_exists('statuses', $data)) {
                    $this->syncStatuses($module, $data['statuses']);
                }

                $this->syncFields($module, $integrationPayload['columns'], $typeMap);
            } else {
                $module = $this->modules->update($module, [
                    'name' => $data['name'] ?? $module->name,
                    'slug' => $data['slug'] ?? $module->slug,
                    'icon' => array_key_exists('icon', $data) ? $data['icon'] : $module->icon,
                    'status' => $data['status'] ?? $module->status->value,
                ]);
            }

            $this->logger->log(
                ActivityAction::MODULE_UPDATED,
                "Módulo '{$module->name}' atualizado.",
                subject: $module,
            );

            $module = $this->loadIntegrationRelations($module);
            $this->cache->store($module);

            return $module;
        });
    }

    public function delete(Module $module): void
    {
        $name = $module->name;

        $this->cache->forgetModule($module);
        $this->modules->delete($module);

        $this->logger->log(
            ActivityAction::MODULE_DELETED,
            "Módulo '{$name}' removido.",
        );
    }

    /**
     * Atualiza a configuração de layout dos campos do módulo.
     *
     * @param  array<int, array<string, mixed>>  $fields
     * @param  array<string, mixed>|null  $detailLayout
     */
    public function updateLayout(Module $module, array $fields, ?array $detailLayout = null): Module
    {
        $module->loadMissing('fields');
        $byUuid = $module->fields->keyBy('uuid');
        $listCount = 0;

        foreach ($fields as $config) {
            $field = $byUuid->get($config['id'] ?? null);

            if (! $field) {
                continue;
            }

            $showInList = $config['show_in_list'] ?? $field->show_in_list;

            if ($showInList && ($config['visible'] ?? $field->visible)) {
                $listCount++;
            }

            $field->update([
                'order' => $config['order'] ?? $field->order,
                'show_in_card' => $config['show_in_card'] ?? $field->show_in_card,
                'show_in_list' => $showInList,
                'show_in_detail' => $config['show_in_detail'] ?? $field->show_in_detail,
                'highlighted' => $config['highlighted'] ?? $field->highlighted,
                'visible' => $config['visible'] ?? $field->visible,
            ]);
        }

        if ($listCount > 6) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'fields' => [__('modules.layout_max_list_fields', ['max' => 6])],
            ]);
        }

        if ($detailLayout !== null) {
            $this->validateDetailLayout($detailLayout);
            $module->update(['detail_layout' => $detailLayout]);
        }

        $this->logger->log(
            ActivityAction::MODULE_LAYOUT_UPDATED,
            "Layout do módulo '{$module->name}' atualizado.",
            subject: $module,
        );

        $module = $this->loadIntegrationRelations($module);
        $this->cache->store($module);

        return $module;
    }

    private function resolveConnection(string $uuid): DatabaseConnection
    {
        return DatabaseConnection::query()->where('uuid', $uuid)->firstOrFail();
    }

    /**
     * @param  array<int, array<string, mixed>|string>  $columns
     * @return list<string>
     */
    private function extractColumnNames(array $columns): array
    {
        return collect($columns)
            ->map(fn ($column) => is_array($column) ? $column['name'] : $column)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    private function integrationPayload(Module $module, array $data): ?array
    {
        $keys = ['connection_id', 'columns', 'status_column', 'callback_url', 'callback_method', 'statuses'];
        $touchesIntegration = collect($keys)->contains(fn (string $key) => array_key_exists($key, $data));

        if (! $touchesIntegration && ! $module->isIntegrated()) {
            return null;
        }

        if (! $touchesIntegration && $module->isIntegrated()) {
            return null;
        }

        return [
            'connection_id' => $data['connection_id'] ?? $module->connection?->uuid,
            'status_column' => $data['status_column'] ?? $module->status_column,
            'callback_url' => array_key_exists('callback_url', $data) ? $data['callback_url'] : $module->callback_url,
            'callback_method' => $data['callback_method'] ?? $module->callback_method ?? 'POST',
            'columns' => $data['columns'] ?? $module->fields->map(fn ($field) => [
                'name' => $field->key,
                'label' => $field->label,
            ])->all(),
        ];
    }

    /**
     * @param  list<array{slug: string, label: string, order: int, external_value: string}>  $statuses
     */
    private function syncStatuses(Module $module, array $statuses): void
    {
        $module->kanbanStatuses()->delete();

        foreach ($statuses as $status) {
            $module->kanbanStatuses()->create([
                'slug' => $status['slug'] ?? Str::slug($status['label'], '_'),
                'label' => $status['label'],
                'order' => $status['order'],
                'external_value' => $status['external_value'],
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $columns
     * @param  array<string, string>  $typeMap
     */
    private function syncFields(Module $module, array $columns, array $typeMap): void
    {
        $keys = $this->extractColumnNames($columns);

        $module->fields()->whereNotIn('key', $keys)->delete();

        foreach ($columns as $index => $column) {
            $name = is_array($column) ? $column['name'] : $column;
            $label = is_array($column) ? ($column['label'] ?? $name) : $name;
            $type = is_array($column) && ! empty($column['type'])
                ? FieldType::from($column['type'])
                : ModuleColumnTypeMapper::fromDatabaseType($typeMap[$name] ?? 'varchar');

            $module->fields()->updateOrCreate(
                ['key' => $name],
                [
                    'label' => $label,
                    'type' => $type->value,
                    'required' => false,
                    'default_value' => null,
                    'options' => null,
                    'order' => is_array($column) ? ($column['order'] ?? $index) : $index,
                    'show_in_card' => is_array($column) ? ($column['show_in_card'] ?? true) : true,
                    'show_in_list' => is_array($column) ? ($column['show_in_list'] ?? true) : true,
                    'show_in_detail' => is_array($column) ? ($column['show_in_detail'] ?? true) : true,
                    'highlighted' => is_array($column) ? ($column['highlighted'] ?? false) : false,
                    'visible' => is_array($column) ? ($column['visible'] ?? true) : true,
                ],
            );
        }
    }

    private function loadIntegrationRelations(Module $module): Module
    {
        return $module->load(['fields', 'kanbanStatuses', 'connection']);
    }

    /**
     * @param  array<string, mixed>  $detailLayout
     */
    private function validateDetailLayout(array $detailLayout): void
    {
        if (! isset($detailLayout['rows']) || ! is_array($detailLayout['rows'])) {
            return;
        }

        foreach ($detailLayout['rows'] as $row) {
            $keys = $row['field_keys'] ?? [];

            if (count($keys) > 3) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'detail_layout' => [__('modules.detail_layout_max_fields_per_row', ['max' => 3])],
                ]);
            }
        }
    }
}
