<?php

namespace App\Support\Cache;

use App\Models\Report;

class ReportStructuralCache extends StructuralCacheStore
{
    public function reportKey(string $uuid): string
    {
        return "mystique:report:{$uuid}";
    }

    public function rememberReport(string $uuid, callable $resolver): ?Report
    {
        return $this->remember($this->reportKey($uuid), $resolver);
    }

    public function store(Report $report): void
    {
        $this->put($this->reportKey($report->uuid), $report);
    }

    public function forgetReport(Report $report): void
    {
        $this->forget($this->reportKey($report->uuid));
    }
}
