<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Record\ListRecordsRequest;
use App\Http\Requests\Record\StoreRecordRequest;
use App\Http\Requests\Record\UpdateRecordRequest;
use App\Http\Resources\RecordAuditResource;
use App\Http\Resources\RecordResource;
use App\Models\Module;
use App\Models\ModuleRecord;
use App\Services\Module\RecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RecordController extends Controller
{
    public function __construct(
        private readonly RecordService $recordService,
    ) {}

    public function index(ListRecordsRequest $request, Module $module): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ModuleRecord::class);

        $records = $this->recordService->list(
            $module,
            $request->filters(),
            $request->perPage(),
        );

        return RecordResource::collection($records);
    }

    public function store(StoreRecordRequest $request, Module $module): RecordResource
    {
        $this->authorize('create', ModuleRecord::class);
        $this->ensureNotIntegrated($module);

        $record = $this->recordService->create($module, $request->user(), $request->validated());

        return new RecordResource($record);
    }

    public function show(ModuleRecord $record): RecordResource
    {
        $this->authorize('view', $record);

        return new RecordResource($record->load('values.field'));
    }

    public function update(UpdateRecordRequest $request, ModuleRecord $record): RecordResource
    {
        $this->authorize('update', $record);
        $this->ensureNotIntegrated($record->module);

        $record = $this->recordService->update($record, $request->user(), $request->validated());

        return new RecordResource($record);
    }

    public function destroy(ModuleRecord $record): JsonResponse
    {
        $this->authorize('delete', $record);
        $this->ensureNotIntegrated($record->module);

        $this->recordService->delete($record);

        return response()->json(['message' => 'Registro removido com sucesso.']);
    }

    private function ensureNotIntegrated(Module $module): void
    {
        if ($module->isIntegrated()) {
            throw new HttpException(403, __('modules.integrated_read_only'));
        }
    }

    public function audits(ModuleRecord $record): AnonymousResourceCollection
    {
        $this->authorize('view', $record);

        return RecordAuditResource::collection($this->recordService->audits($record));
    }
}
