<?php

namespace App\Exports\Reports;

use App\Enums\StoreGroupEnum;
use App\Enums\StoreStatusEnum;
use App\Models\SalesPerformanceDetail;
use App\Models\Store;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesPerformanceDetailSheet implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    protected array $filters;

    protected array $data = [];

    public function __construct(array $filters, $sales_performance_id)
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

        $details = $query->orderBy('store_code')->orderBy('month')->get();

        $storeCodes = $details->pluck('store_code')->unique();
        $storeInfo = Store::with('franchisee')
            ->whereIn('store_code', $storeCodes)
            ->whereIn('store_status', $allowedStatuses)
            ->get()
            ->keyBy('store_code');

        $groupedByStore = $details->groupBy('store_code');

        $type = ! empty($filters['sales_type']) ? (int) $filters['sales_type'] : 3;

        $monthNames = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
        ];

        foreach ($groupedByStore as $storeCode => $storeDetails) {
            $firstRecord = $storeDetails->first();
            $store = $storeInfo->get($storeCode);

            $ytd = 0;
            foreach ($storeDetails as $detail) {
                if ($type === 1) {
                    $ytd += $detail->bread;
                } elseif ($type === 2) {
                    $ytd += $detail->non_bread;
                } else {
                    $ytd += $detail->combined;
                }
            }

            $franchiseeName = '';
            if ($store && $store->franchisee) {
                $franchiseeName = $store->franchisee->full_name;
            }

            $districtName = '';
            if ($store) {
                $districtName = $store->om_district_name;
            }

            $base = [
                'Cluster Code' => $firstRecord->cluster_code,
                'Store Code' => $firstRecord->store_code,
                'Cluster Name' => $store ? $store->jbs_name : '',
                'Franchisee Code' => $firstRecord->franchise_code,
                'Franchisee Name' => $franchiseeName,
                'Region' => $firstRecord->region,
                'Area' => $firstRecord->area,
                'District Code' => $firstRecord->district,
                'District Name' => $districtName,
                'YTD' => $ytd,
            ];

            $monthlyData = $storeDetails->keyBy('month');
            for ($m = 1; $m <= 12; $m++) {
                $monthDetail = $monthlyData->get($m);
                if ($monthDetail) {
                    if ($type === 1) {
                        $val = $monthDetail->bread;
                    } elseif ($type === 2) {
                        $val = $monthDetail->non_bread;
                    } else {
                        $val = $monthDetail->combined;
                    }
                } else {
                    $val = 0;
                }
                $base[$monthNames[$m]] = $val;
            }

            $this->data[] = $base;
        }
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

    public function title(): string
    {
        return (string) ($this->filters['year'] ?? 'Detail');
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        $headers = [
            'Cluster Code', 'Store Code', 'Cluster Name',
            'Franchisee Code', 'Franchisee Name',
            'Region', 'Area', 'District Code', 'District Name',
            'YTD',
        ];

        $monthNames = [
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
        ];

        return array_merge($headers, $monthNames);
    }

    public function styles(Worksheet $sheet): array
    {
        $lastCol = Coordinate::stringFromColumnIndex(count($this->headings()));

        return [
            1 => ['font' => ['bold' => true]],
            "J2:{$lastCol}1000" => [
                'numberFormat' => [
                    'formatCode' => '#,##0.00',
                ],
            ],
        ];
    }
}
