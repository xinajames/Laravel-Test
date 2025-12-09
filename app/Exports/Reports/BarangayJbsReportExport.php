<?php

namespace App\Exports\Reports;

use App\Enums\StoreGroupEnum;
use App\Enums\StoreStatusEnum;
use App\Models\Store;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BarangayJbsReportExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
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

        $this->data = Store::query()
            ->where('is_draft', false)
            ->where('deleted_at', null)
            ->whereNotNull('store_barangay')
            ->whereNotNull('store_city')
            ->whereNotNull('store_province')
            ->when(
                isset($filters['region']),
                fn ($q) => $q->where('region', $filters['region'])
            )
            ->whereIn('store_status', [StoreStatusEnum::Open()->value, StoreStatusEnum::TemporaryClosed()->value])
            ->when(! empty($storeGroupValues), fn ($q) => $q->whereIn('store_group', $storeGroupValues))
            ->orderBy('updated_at', 'desc')
            ->orderBy('store_province')
            ->orderBy('store_city')
            ->orderBy('store_barangay')
            ->orderBy('jbs_name')
            ->get()
            ->unique(function ($store) {
                return $store->store_province.'|'.$store->store_city.'|'.$store->store_barangay;
            })
            ->map(fn ($store) => [
                'Barangay' => $store->store_barangay,
                'Municipality/City' => $store->store_city,
                'Province' => $store->store_province,
                'Store Status' => $store->store_status ? StoreStatusEnum::getDescription($store->store_status) : null,
            ])
            ->values()
            ->toArray();
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            ['Report: Barangay With JBS Report'],
            ['Store Group:', $this->filters['store_group'] ?? 'All'],
            ['Region:', $this->filters['region'] ?? 'All'],
            [''], // empty row before actual table headings
            ['Barangay', 'Municipality/City', 'Province', 'Store Status'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A5:D5')->getFont()->setBold(true);
    }
}
