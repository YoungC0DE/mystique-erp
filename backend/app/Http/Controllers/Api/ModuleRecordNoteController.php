<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Module\UpsertRecordNoteRequest;
use App\Models\Module;
use App\Models\ModuleRecord;
use App\Services\Module\ModuleRecordNoteService;
use Illuminate\Http\JsonResponse;

class ModuleRecordNoteController extends Controller
{
    public function __construct(
        private readonly ModuleRecordNoteService $noteService,
    ) {}

    public function show(Module $module, string $recordKey): JsonResponse
    {
        $this->authorize('viewAny', ModuleRecord::class);

        return response()->json([
            'data' => $this->noteService->get($module, $recordKey),
        ]);
    }

    public function update(UpsertRecordNoteRequest $request, Module $module, string $recordKey): JsonResponse
    {
        $this->authorize('upsertNote', ModuleRecord::class);

        $note = $this->noteService->upsert(
            $module,
            $recordKey,
            $request->user(),
            $request->validated('body'),
        );

        return response()->json([
            'data' => [
                'body' => $note->body,
                'updated_at' => $note->updated_at?->toIso8601String(),
                'updated_by' => [
                    'id' => $note->editor?->uuid,
                    'name' => $note->editor?->name,
                ],
            ],
        ]);
    }
}
