<?php

namespace App\Services\Module;

use App\Enums\ActivityAction;
use App\Models\Module;
use App\Models\Report;
use App\Models\User;
use App\Services\ActivityLog\ActivityLogger;
use App\Support\Cache\ReportStructuralCache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function __construct(
        private readonly ActivityLogger $logger,
        private readonly ReportRunner $runner,
        private readonly ReportStructuralCache $cache,
    ) {}

    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return Report::query()
            ->with(['module', 'creator'])
            ->latest()
            ->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?Report
    {
        return $this->cache->rememberReport($uuid, fn () => Report::query()
            ->with(['module', 'creator'])
            ->where('uuid', $uuid)
            ->first());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): Report
    {
        return DB::transaction(function () use ($actor, $data) {
            $module = Module::query()->where('uuid', $data['module_id'])->firstOrFail();

            $report = Report::create([
                'name' => $data['name'],
                'module_id' => $module->id,
                'field_keys' => $data['field_keys'],
                'filters' => $data['filters'] ?? [],
                'created_by' => $actor->getKey(),
            ]);

            $this->logger->log(
                ActivityAction::REPORT_CREATED,
                "Relatório '{$report->name}' criado.",
                subject: $report,
            );

            $report = $report->load(['module', 'creator']);
            $this->cache->store($report);

            return $report;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Report $report, array $data): Report
    {
        return DB::transaction(function () use ($report, $data) {
            $payload = [
                'name' => $data['name'] ?? $report->name,
                'field_keys' => $data['field_keys'] ?? $report->field_keys,
                'filters' => $data['filters'] ?? $report->filters,
            ];

            if (array_key_exists('module_id', $data)) {
                $module = Module::query()->where('uuid', $data['module_id'])->firstOrFail();
                $payload['module_id'] = $module->id;
            }

            $report->update($payload);

            $this->logger->log(
                ActivityAction::REPORT_UPDATED,
                "Relatório '{$report->name}' atualizado.",
                subject: $report,
            );

            $report = $report->load(['module', 'creator']);
            $this->cache->store($report);

            return $report;
        });
    }

    public function delete(Report $report): void
    {
        $name = $report->name;

        $this->cache->forgetReport($report);
        $report->delete();

        $this->logger->log(
            ActivityAction::REPORT_DELETED,
            "Relatório '{$name}' removido.",
        );
    }

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     */
    public function run(Report $report, int $perPage = 25, int $page = 1): array
    {
        $report->loadMissing('module');

        return $this->runner->run(
            $report->module,
            $report->field_keys,
            $report->filters ?? [],
            $perPage,
            $page,
        );
    }
}
