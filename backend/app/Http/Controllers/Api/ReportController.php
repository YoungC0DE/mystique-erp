<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaginatedIndexRequest;
use App\Http\Requests\Report\RunReportRequest;
use App\Http\Requests\Report\StoreReportRequest;
use App\Http\Requests\Report\UpdateReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Services\Module\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
    ) {
        $this->authorizeResource(Report::class, 'report');
    }

    public function index(PaginatedIndexRequest $request): AnonymousResourceCollection
    {
        return ReportResource::collection(
            $this->reportService->list($request->perPage())
        );
    }

    public function store(StoreReportRequest $request): ReportResource
    {
        $report = $this->reportService->create($request->user(), $request->validated());

        return new ReportResource($report);
    }

    public function show(Report $report): ReportResource
    {
        $report = $this->reportService->findByUuid($report->uuid)
            ?? $report->load(['module', 'creator']);

        return new ReportResource($report);
    }

    public function update(UpdateReportRequest $request, Report $report): ReportResource
    {
        $report = $this->reportService->update($report, $request->validated());

        return new ReportResource($report);
    }

    public function destroy(Report $report): JsonResponse
    {
        $this->reportService->delete($report);

        return response()->json(['message' => 'Relatório removido com sucesso.']);
    }

    public function run(RunReportRequest $request, Report $report): JsonResponse
    {
        $this->authorize('view', $report);

        $result = $this->reportService->run(
            $report,
            $request->perPage(25),
            $request->pageNumber(),
        );

        return response()->json($result);
    }
}
