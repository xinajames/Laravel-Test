<?php

namespace App\Exports\Reports;

use App\Enums\StoreGroupEnum;
use App\Enums\StoreStatusEnum;
use App\Models\SalesPerformanceDetail;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesPerformanceSummarySheet implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    protected array $filters;

    protected array $data;

    public function __construct(array $filters, int $sales_performance_id)
    {
        $this->filters = $filters;

        $query = SalesPerformanceDetail::query()
            ->where('sales_performance_id', $sales_performance_id)
            ->when(! empty($filters['year']) && $filters['year'] !== null, fn ($q) => $q->where('year', $filters['year']))
            ->when(! empty($filters['region']), fn ($q) => $q->where('region', $filters['region']));

        $allowedStatuses = [StoreStatusEnum::Open()->value, StoreStatusEnum::TemporaryClosed()->value, 'Temp Cls', 'TemporaryClosed', StoreStatusEnum::Future()->value, StoreStatusEnum::Closed()->value, StoreStatusEnum::Deactivated()->value];

        if (! empty($filters['store_group'])) {
            $storeGroupValues = $this->getStoreGroupValues($filters['store_group']);
            if (! empty($storeGroupValues)) {
                $query->whereIn('store_code', function ($subQuery) use ($storeGroupValues, $allowedStatuses) {
                    $subQuery->select('store_code')
                        ->from('stores')
                        ->whereIn('store_group', $storeGroupValues)
                        ->whereIn('store_status', $allowedStatuses);
                });
            }
        } else {
            $query->whereIn('store_code', function ($subQuery) use ($allowedStatuses) {
                $subQuery->select('store_code')
                    ->from('stores')
                    ->whereIn('store_status', $allowedStatuses);
            });
        }

        $type = ! empty($filters['sales_type']) ? (int) $filters['sales_type'] : null;

        if ($type === 1) {
            $query->select('month', DB::raw('SUM(bread) as value'))
                ->groupBy('month');
        } elseif ($type === 2) {
            $query->select('month', DB::raw('SUM(non_bread) as value'))
                ->groupBy('month');
        } else {
            $query->select(
                'month',
                DB::raw('SUM(bread) as bread'),
                DB::raw('SUM(non_bread) as non_bread'),
                DB::raw('SUM(combined) as combined')
            )
                ->groupBy('month');
        }

        $rows = $query->orderBy('month')->get();

        $monthNames = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];

        $this->data = $rows->map(fn ($row) => [
            'Month' => $monthNames[$row->month] ?? $row->month,
        ] + (
            $type === 1
                ? ['Bread' => $row->value]
                : ($type === 2
                ? ['Non Bread' => $row->value]
                : [
                    'Bread' => $row->bread,
                    'Non Bread' => $row->non_bread,
                    'Combined' => $row->combined,
                ])
        ))->toArray();
    }

    protected function getStoreGroupValues(string $storeGroup): array
    {
        switch ($storeGroup) {
            case 'FullFranchise':
                return [StoreGroupEnum::FranchiseeFZE()->value];
            case 'CompanyOwned':
                return [StoreGroupEnum::CompanyOwnedJFC()->value, StoreGroupEnum::CompanyOwnedBGC()->value];
            default:
                return [StoreGroupEnum::FranchiseeFZE()->value, StoreGroupEnum::CompanyOwnedJFC()->value, StoreGroupEnum::CompanyOwnedBGC()->value, null];
        }
    }

    public function array(): array
    {
        return $this->data;
    }

    public function title(): string
    {
        return 'Summary';
    }

    public function headings(): array
    {
        $rows = [
            ['Report: JBS Sales Performance'],
            ['Year:', $this->filters['year']],
            ['Region:', $this->filters['region'] ?? 'All'],
            ['Store Group:', $this->getStoreGroupLabel()],
            [],
        ];

        $titles = ['Month'];
        if (isset($this->filters['sales_type'])) {
            $titles[] = $this->filters['sales_type'] === 1
                ? 'Bread'
                : 'Non Bread';
        } else {
            $titles = array_merge($titles, ['Bread', 'Non Bread', 'Combined']);
        }

        $rows[] = $titles;

        return $rows;
    }

    protected function getStoreGroupLabel(): string
    {
        $storeGroup = $this->filters['store_group'] ?? null;
        switch ($storeGroup) {
            case 'FullFranchise':
                return 'Franchisee';
            case 'CompanyOwned':
                return 'Company Owned';
            default:
                return 'All';
        }
    }

    public function styles(Worksheet $sheet)
    {
        $headerRow = 5;
        $lastCol = Coordinate::stringFromColumnIndex(count($this->headings()[4]));

        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")
            ->getFont()->setBold(true);

        $sheet->getStyle("B6:{$lastCol}1000")
            ->getNumberFormat()->setFormatCode('#,##0.00');

        $sheet->getStyle('B2:B4')
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
}
