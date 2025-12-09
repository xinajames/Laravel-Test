<?php

namespace App\Exports\Reports;

use App\Models\SalesPerformance;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SalesPerformanceReportExport implements WithMultipleSheets
{
    use Exportable;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        $latestSalesPerformance = SalesPerformance::whereNull('deleted_at')
            ->whereNotNull('cached_path')
            ->whereNotNull('by_store_cached_path')
            ->orderByDesc('recorded_at')
            ->first();

        return [
            new SalesPerformanceSummarySheet($this->filters, $latestSalesPerformance->id),
            new SalesPerformanceDetailSheet($this->filters, $latestSalesPerformance->id),
        ];
    }
}
