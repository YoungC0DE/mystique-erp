<?php

namespace App\Services\Module;

use App\Enums\ActivityAction;
use App\Events\RecordMoved;
use App\Models\Module;
use App\Models\ModuleRecord;
use App\Models\RecordAudit;
use App\Models\User;
use App\Services\ActivityLog\ActivityLogger;
use App\Support\KanbanStatusAccents;

class KanbanService
{
    public function __construct(
        private readonly ActivityLogger $logger,
        private readonly ExternalBoardReader $externalBoardReader,
        private readonly FieldFilterApplier $filterApplier,
    ) {}

    /**
     * Monta o board do Kanban com status configuráveis do módulo, cada um paginado.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function board(Module $module, array $filters = [], int $perPage = 15): array
    {
        if ($module->isIntegrated()) {
            return $this->externalBoardReader->board($module, $filters, $perPage);
        }

        $module->loadMissing('kanbanStatuses');
        $columns = [];

        foreach ($module->kanbanStatuses->sortBy('order') as $status) {
            $paginator = $module->records()
                ->with('values.field')
                ->where('status', $status->slug)
                ->when(
                    ! empty($filters['created_by']),
                    fn ($query) => $query->whereHas('creator', fn ($c) => $c->where('uuid', $filters['created_by']))
                )
                ->when(
                    ! empty($filters['q']),
                    fn ($query) => $query->whereHas('values', fn ($v) => $v->where('value', 'like', '%'.$filters['q'].'%'))
                )
                ->when(
                    ! empty($filters['field_filters']) && is_array($filters['field_filters']),
                    fn ($query) => $this->filterApplier->applyToEavQuery($query, $module, $filters['field_filters'])
                )
                ->latest()
                ->paginate($perPage, ['*'], $status->slug.'_page');

            $columns[] = [
                'key' => $status->slug,
                'label' => $status->label,
                'color' => KanbanStatusAccents::for($status->slug),
                'records' => $paginator->getCollection(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ];
        }

        return $columns;
    }

    /**
     * Move um card para outra coluna, registra auditoria e dispara broadcast.
     */
    public function move(ModuleRecord $record, User $actor, string $toStatus): ModuleRecord
    {
        $from = $record->status;

        if ($from === $toStatus) {
            return $record;
        }

        $record->update(['status' => $toStatus]);

        RecordAudit::create([
            'record_id' => $record->id,
            'user_id' => $actor->getKey(),
            'action' => 'moved',
            'changes' => ['status' => ['old' => $from, 'new' => $toStatus]],
        ]);

        $this->logger->log(
            ActivityAction::RECORD_MOVED,
            "Card movido de '{$from}' para '{$toStatus}'.",
            properties: ['from' => $from, 'to' => $toStatus],
            subject: $record,
        );

        $record->load('module');

        broadcast(new RecordMoved(
            $record->module,
            $record->uuid,
            $from,
            $toStatus,
        ))->toOthers();

        return $record->load('values.field');
    }
}
