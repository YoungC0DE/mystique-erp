<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Connection\StoreConnectionRequest;
use App\Http\Requests\Connection\TestConnectionRequest;
use App\Http\Requests\Connection\UpdateConnectionRequest;
use App\Http\Resources\DatabaseConnectionResource;
use App\Models\DatabaseConnection;
use App\Services\Connection\ConnectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DatabaseConnectionController extends Controller
{
    public function __construct(
        private readonly ConnectionService $connectionService,
    ) {
        $this->authorizeResource(DatabaseConnection::class, 'connection');
    }

    public function index(): AnonymousResourceCollection
    {
        return DatabaseConnectionResource::collection($this->connectionService->list());
    }

    public function store(StoreConnectionRequest $request): DatabaseConnectionResource
    {
        $connection = $this->connectionService->create($request->validated());

        return new DatabaseConnectionResource($connection);
    }

    public function show(DatabaseConnection $connection): DatabaseConnectionResource
    {
        return new DatabaseConnectionResource($connection);
    }

    public function update(UpdateConnectionRequest $request, DatabaseConnection $connection): DatabaseConnectionResource
    {
        $connection = $this->connectionService->update($connection, $request->validated());

        return new DatabaseConnectionResource($connection);
    }

    public function destroy(DatabaseConnection $connection): JsonResponse
    {
        $this->connectionService->delete($connection);

        return response()->json(['message' => 'Conexão removida com sucesso.']);
    }

    public function test(DatabaseConnection $connection): JsonResponse
    {
        $this->authorize('view', $connection);

        $this->connectionService->test($connection);

        return response()->json(['message' => __('connections.test_success')]);
    }

    public function validateConnection(TestConnectionRequest $request): JsonResponse
    {
        $this->authorize('create', DatabaseConnection::class);

        $this->connectionService->validatePayload($request->validated());

        return response()->json(['message' => __('connections.test_success')]);
    }

    public function columns(DatabaseConnection $connection): JsonResponse
    {
        $this->authorize('view', $connection);

        return response()->json([
            'data' => $this->connectionService->columns($connection),
        ]);
    }
}
