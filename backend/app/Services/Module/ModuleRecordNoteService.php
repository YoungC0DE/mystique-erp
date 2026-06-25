<?php

namespace App\Services\Module;

use App\Enums\ActivityAction;
use App\Models\Module;
use App\Models\ModuleRecordNote;
use App\Models\User;
use App\Services\ActivityLog\ActivityLogger;

class ModuleRecordNoteService
{
    public function __construct(
        private readonly ActivityLogger $logger,
    ) {}

    /**
     * @return array{body: string|null, updated_at: string|null, updated_by: array{id: string|null, name: string|null}|null}
     */
    public function get(Module $module, string $recordKey): array
    {
        $note = ModuleRecordNote::query()
            ->where('module_id', $module->id)
            ->where('record_key', $recordKey)
            ->with('editor')
            ->first();

        if ($note === null) {
            return [
                'body' => null,
                'updated_at' => null,
                'updated_by' => null,
            ];
        }

        return [
            'body' => $note->body,
            'updated_at' => $note->updated_at?->toIso8601String(),
            'updated_by' => [
                'id' => $note->editor?->uuid,
                'name' => $note->editor?->name,
            ],
        ];
    }

    public function upsert(Module $module, string $recordKey, User $actor, ?string $body): ModuleRecordNote
    {
        $note = ModuleRecordNote::query()->updateOrCreate(
            [
                'module_id' => $module->id,
                'record_key' => $recordKey,
            ],
            [
                'body' => $body !== '' ? $body : null,
                'updated_by' => $actor->getKey(),
            ],
        );

        $this->logger->log(
            ActivityAction::RECORD_NOTE_UPDATED,
            'Observação interna do card atualizada.',
            subject: $module,
            properties: ['record_key' => $recordKey],
        );

        return $note->load('editor');
    }
}
