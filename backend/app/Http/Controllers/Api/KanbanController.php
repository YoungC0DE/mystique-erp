<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Module\KanbanBoardRequest;
use App\Http\Requests\Record\MoveIntegratedRecordRequest;
use App\Http\Requests\Record\MoveRecordRequest;
use App\Http\Resources\RecordResource;
use App\Models\Module;
use App\Models\ModuleRecord;
use App\Services\Module\KanbanService;
use App\Services\Module\StageCallbackService;
use Illuminate\Http\JsonResponse;

class KanbanController extends Controller
{
    public function __construct(
        private readonly KanbanService $kanbanService,
        private readonly StageCallbackService $stageCallbackService,
    ) {}

    public function board(KanbanBoardRequest $request, Module $module): JsonResponse
    {
        $this->authorize('viewAny', ModuleRecord::class);

        $module->loadMissing(['kanbanStatuses', 'fields']);

        $columns = $this->kanbanService->board(
            $module,
            $request->boardFilters($module),
            $request->perPage(),
        );

        if (! $module->isIntegrated()) {
            $columns = array_map(function (array $column) {
                $column['records'] = RecordResource::collection($column['records'])->resolve();

                return $column;
            }, $columns);
        }

        return response()->json([
            'module' => $module->uuid,
            'columns' => $columns,
        ]);
    }

    public function move(MoveRecordRequest $request, ModuleRecord $record): RecordResource
    {
        $this->authorize('update', $record);

        $record = $this->kanbanService->move($record, $request->user(), $request->validated()['status']);

        return new RecordResource($record);
    }

    public function moveIntegrated(
        MoveIntegratedRecordRequest $request,
        Module $module,
        string $externalId,
    ): JsonResponse {
        $this->authorize('moveIntegrated', ModuleRecord::class);

        $record = $this->stageCallbackService->move(
            $module,
            $externalId,
            $request->user(),
            $request->validated()['status'],
        );

        return response()->json(['data' => $record]);
    }
}
