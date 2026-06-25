<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Module\StoreModuleRequest;
use App\Http\Requests\Module\UpdateLayoutRequest;
use App\Http\Requests\Module\UpdateModuleRequest;
use App\Http\Requests\PaginatedIndexRequest;
use App\Http\Resources\ModuleResource;
use App\Models\Module;
use App\Services\Module\ModuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ModuleController extends Controller
{
    public function __construct(
        private readonly ModuleService $moduleService,
    ) {
        $this->authorizeResource(Module::class, 'module');
    }

    public function index(PaginatedIndexRequest $request): AnonymousResourceCollection
    {
        return ModuleResource::collection(
            $this->moduleService->list($request->perPage())
        );
    }

    /**
     * Módulos ativos permitidos ao usuário autenticado (navbar dinâmica).
     */
    public function allowed(): AnonymousResourceCollection
    {
        return ModuleResource::collection($this->moduleService->allowedModules());
    }

    public function store(StoreModuleRequest $request): ModuleResource
    {
        $module = $this->moduleService->create($request->user(), $request->validated());

        return new ModuleResource($module->load(['fields', 'kanbanStatuses', 'connection']));
    }

    public function show(Module $module): ModuleResource
    {
        $module = $this->moduleService->findByUuid($module->uuid)
            ?? $module->load(['fields', 'kanbanStatuses', 'connection']);

        return new ModuleResource($module);
    }

    public function update(UpdateModuleRequest $request, Module $module): ModuleResource
    {
        $module = $this->moduleService->update($module, $request->validated());

        return new ModuleResource($module->load(['fields', 'kanbanStatuses', 'connection']));
    }

    public function destroy(Module $module): JsonResponse
    {
        $this->moduleService->delete($module);

        return response()->json(['message' => 'Módulo removido com sucesso.']);
    }

    public function updateLayout(UpdateLayoutRequest $request, Module $module): ModuleResource
    {
        $this->authorize('update', $module);

        $validated = $request->validated();
        $detailLayout = array_key_exists('detail_layout', $validated) ? $validated['detail_layout'] : null;

        $module = $this->moduleService->updateLayout(
            $module,
            $validated['fields'],
            $detailLayout,
        );

        return new ModuleResource($module->load(['fields', 'kanbanStatuses', 'connection']));
    }
}
