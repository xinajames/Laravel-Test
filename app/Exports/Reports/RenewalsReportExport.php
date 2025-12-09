<?php

namespace App\Exports\Reports;

use App\Enums\StoreGroupEnum;
use App\Enums\StoreStatusEnum;
use App\Models\Store;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RenewalsReportExport implements FromArray, ShouldAutoSize, WithStyles
{
    protected array $filters;

    protected array $data;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;

        $storeGroup = $filters['store_group'] ?? null;
        $storeGroupValues = match ($storeGroup) {
            'FullFranchise' => [StoreGroupEnum::FranchiseeFZE()->value],
            'CompanyOwned' => [
                StoreGroupEnum::CompanyOwnedJFC()->value,
                StoreGroupEnum::CompanyOwnedBGC()->value,
            ],
            default => [],
        };

        $this->data = Store::with('franchisee')
            ->where('is_draft', false)
            ->where('deleted_at', null)
            ->when(isset($filters['year']), function ($query) use ($filters) {
                $query->whereYear('renewal_date', $filters['year']);
            })
            ->when(isset($filters['year']), function ($query) {
                $query->whereNotNull('renewal_date');
            })
            ->when(
                isset($filters['region']),
                fn ($q) => $q->where('region', $filters['region'])
            )
            ->when(
                isset($filters['store_status']),
                fn ($q) => $q->where('store_status', $filters['store_status'])
            )
            ->when(! empty($storeGroupValues), fn ($q) => $q->whereIn('store_group', $storeGroupValues))
            ->orderBy('renewal_date')
            ->get()
            ->map(function ($store) {
                $franchisee = $store->franchisee;
                $fullName = trim("{$franchisee->first_name} {$franchisee->middle_name} {$franchisee->last_name}");

                return [
                    $store->region,
                    $store->sales_point_code,
                    $store->jbs_name,
                    $fullName,
                    $franchisee->corporation_name,
                    $store->store_type,
                    $store->franchise_date ? Carbon::parse($store->franchise_date)->format('Y-m-d') : null,
                    $store->last_renewal_date ? Carbon::parse($store->last_renewal_date)->format('Y-m-d') : null,
                    $store->renewal_date ? Carbon::parse($store->renewal_date)->format('Y-m-d') : null,
                    $store->cluster_code,
                    $store->store_status ? StoreStatusEnum::getDescription($store->store_status) : null,
                ];
            })
            ->toArray();
    }

    public function array(): array
    {
        return array_merge([
            ['Report: Renewals Report'],
            ['Region:', $this->filters['region'] ?? 'All'],
            ['Store Group:', $this->filters['store_group'] ?? 'All'],
            ['Store Status:', $this->filters['store_status'] ? StoreStatusEnum::getDescription($this->filters['store_status']) : 'All'],
            ['Year:', $this->filters['year'] ?? 'All'],
            [''], // empty row before actual table headings
            [ // column headings
                'Region',
                'Sales Point Code',
                'JBS Name',
                'Franchisee Name',
                'Franchisee Corporation Name',
                'Sales Point Type',
                'Franchise Date',
                'Last Renewal',
                'Renewal Date',
                'Cluster Code',
                'Store Status',
            ],
        ], $this->data);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A6:K6')->getFont()->setBold(true);
    }
}
