<?php

namespace App\Services\Module;

use App\Enums\ActivityAction;
use App\Enums\FieldType;
use App\Models\Module;
use App\Models\ModuleField;
use App\Models\ModuleRecord;
use App\Models\RecordAudit;
use App\Models\User;
use App\Repositories\RecordRepository;
use App\Services\ActivityLog\ActivityLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordService
{
    public function __construct(
        private readonly RecordRepository $records,
        private readonly ActivityLogger $logger,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(Module $module, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->records->paginate($module, $filters, $perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Module $module, User $actor, array $data): ModuleRecord
    {
        return DB::transaction(function () use ($module, $actor, $data) {
            $module->loadMissing('kanbanStatuses');

            $record = $module->records()->create([
                'status' => $data['status'] ?? $module->defaultStatusSlug(),
                'created_by' => $actor->getKey(),
            ]);

            $values = $data['values'] ?? [];
            $changes = [];

            foreach ($module->fields()->get() as $field) {
                $raw = array_key_exists($field->key, $values) ? $values[$field->key] : $field->default_value;
                $stored = $this->normalize($field, $raw);

                $this->assertRequired($field, $stored);

                if ($stored !== null) {
                    $record->values()->create([
                        'field_id' => $field->id,
                        'value' => $stored,
                    ]);
                }

                $changes[$field->key] = ['old' => null, 'new' => $stored];
            }

            $this->audit($record, 'created', $changes, $actor);

            $this->logger->log(
                ActivityAction::RECORD_CREATED,
                "Registro criado no módulo '{$module->name}'.",
                subject: $record,
            );

            return $record->load('values.field');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ModuleRecord $record, User $actor, array $data): ModuleRecord
    {
        return DB::transaction(function () use ($record, $actor, $data) {
            $module = $record->module;
            $record->loadMissing('values');

            $existing = $record->values->keyBy('field_id');
            $values = $data['values'] ?? [];
            $changes = [];

            if (array_key_exists('status', $data) && $data['status'] !== $record->status) {
                $changes['status'] = ['old' => $record->status, 'new' => $data['status']];
                $record->update(['status' => $data['status']]);
            }

            foreach ($module->fields()->get() as $field) {
                if (! array_key_exists($field->key, $values)) {
                    continue;
                }

                $stored = $this->normalize($field, $values[$field->key]);
                $this->assertRequired($field, $stored);

                $current = $existing->get($field->id)?->value;

                if ($current === $stored) {
                    continue;
                }

                if ($stored === null) {
                    $existing->get($field->id)?->delete();
                } else {
                    $record->values()->updateOrCreate(
                        ['field_id' => $field->id],
                        ['value' => $stored],
                    );
                }

                $changes[$field->key] = ['old' => $current, 'new' => $stored];
            }

            if (! empty($changes)) {
                $this->audit($record, 'updated', $changes, $actor);
            }

            $this->logger->log(
                ActivityAction::RECORD_UPDATED,
                "Registro atualizado no módulo '{$module->name}'.",
                subject: $record,
            );

            return $record->load('values.field');
        });
    }

    public function delete(ModuleRecord $record): void
    {
        $record->delete();

        $this->logger->log(
            ActivityAction::RECORD_DELETED,
            'Registro removido.',
        );
    }

    /**
     * @return Collection<int, RecordAudit>
     */
    public function audits(ModuleRecord $record): Collection
    {
        return $record->audits()->with('user')->get();
    }

    /**
     * Normaliza o valor de entrada para armazenamento textual (EAV).
     */
    private function normalize(ModuleField $field, mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        return match ($field->type) {
            FieldType::BOOLEAN => filter_var($raw, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
            FieldType::MULTISELECT => json_encode(is_array($raw) ? array_values($raw) : [$raw]),
            default => is_array($raw) ? json_encode($raw) : (string) $raw,
        };
    }

    private function assertRequired(ModuleField $field, ?string $stored): void
    {
        if ($field->required && ($stored === null || $stored === '')) {
            throw ValidationException::withMessages([
                "values.{$field->key}" => ["O campo '{$field->label}' é obrigatório."],
            ]);
        }
    }

    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    private function audit(ModuleRecord $record, string $action, array $changes, User $actor): void
    {
        RecordAudit::create([
            'record_id' => $record->id,
            'user_id' => $actor->getKey(),
            'action' => $action,
            'changes' => $changes,
        ]);
    }
}
