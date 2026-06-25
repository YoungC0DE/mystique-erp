<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Module\StoreModuleFieldRequest;
use App\Http\Requests\Module\UpdateModuleFieldRequest;
use App\Http\Resources\ModuleFieldResource;
use App\Models\Module;
use App\Models\ModuleField;
use App\Services\Module\ModuleFieldService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ModuleFieldController extends Controller
{
    public function __construct(
        private readonly ModuleFieldService $fieldService,
    ) {}

    public function index(Module $module): AnonymousResourceCollection
    {
        $this->authorize('view', $module);

        return ModuleFieldResource::collection($this->fieldService->listForModule($module));
    }

    public function store(StoreModuleFieldRequest $request, Module $module): ModuleFieldResource
    {
        $this->authorize('update', $module);
        $this->ensureLegacyModule($module);

        return new ModuleFieldResource($this->fieldService->create($module, $request->validated()));
    }

    public function show(ModuleField $field): ModuleFieldResource
    {
        $this->authorize('view', $field->module);

        return new ModuleFieldResource($field);
    }

    public function update(UpdateModuleFieldRequest $request, ModuleField $field): ModuleFieldResource
    {
        $this->authorize('update', $field->module);
        $this->ensureLegacyModule($field->module);

        return new ModuleFieldResource($this->fieldService->update($field, $request->validated()));
    }

    public function destroy(ModuleField $field): JsonResponse
    {
        $this->authorize('update', $field->module);
        $this->ensureLegacyModule($field->module);

        $this->fieldService->delete($field);

        return response()->json(['message' => 'Campo removido com sucesso.']);
    }

    private function ensureLegacyModule(Module $module): void
    {
        if ($module->isIntegrated()) {
            throw new HttpException(403, __('modules.integrated_fields_read_only'));
        }
    }
}
