<?php

namespace App\Exports\Reports;

use App\Enums\StoreGroupEnum;
use App\Models\Store;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InsuranceReportExport implements FromArray, ShouldAutoSize, WithStyles
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

        $year = $filters['year'] ?? null;

        $this->data = Store::query()
            ->with('franchisee')
            ->where('is_draft', false)
            ->where('deleted_at', null)
            ->when($filters['region'] ?? null, fn ($q) => $q->where('region', $filters['region']))
            ->when(! empty($storeGroupValues), fn ($q) => $q->whereIn('store_group', $storeGroupValues))
            ->when($year !== null, function ($query) {
                $query->where(function ($q) {
                    $q->whereNotNull('cgl_expiry_date')
                        ->orWhereNotNull('fire_expiry_date');
                });
            })
            ->when($year !== null, function ($query) use ($year) {
                $query->where(function ($q) use ($year) {
                    $q->whereYear('cgl_expiry_date', $year)
                        ->orWhereYear('fire_expiry_date', $year);
                });
            })
            ->orderBy('jbs_name')
            ->get()
            ->map(function ($store) {
                $franchisee = $store->franchisee;

                return [
                    $store->store_status,
                    $store->store_code,
                    $store->store_type,
                    $store->jbs_name,
                    $store->store_street,
                    $store->store_barangay,
                    $store->store_city,
                    $store->store_province,
                    $store->store_postal_code,
                    $store->region,
                    $franchisee?->corporation_name ?? 'N/A',
                    $franchisee?->last_name ?? 'N/A',
                    $franchisee?->first_name ?? 'N/A',
                    $store->cgl_insurance_policy_number,
                    $store->cgl_expiry_date ? Carbon::parse($store->cgl_expiry_date)->format('Y-m-d') : 'N/A',
                    $store->fire_insurance_policy_number,
                    $store->fire_expiry_date ? Carbon::parse($store->fire_expiry_date)->format('Y-m-d') : 'N/A',
                ];
            })
            ->toArray();
    }

    public function array(): array
    {
        return array_merge([
            ['Report: Insurance Report'],
            ['Region:', $this->filters['region'] ?? 'All'],
            ['Store Group:', $this->filters['store_group'] ?? 'All'],
            ['Year:', $this->filters['year'] ?? 'All'],
            [''], // empty row before actual table headings
            [ // column headings
                'Status',
                'Branch Code',
                'Store Type',
                'JBS Name',
                'Street',
                'Barangay',
                'City/ Municipality',
                'Province',
                'Postal Code',
                'Region',
                'Corporation Name',
                'Last Name',
                'First Name',
                'CGL Insurance Policy Number',
                'CGL Expiry Date',
                'Fire Insurance Policy Number',
                'Fire Expiry Date',
            ],
        ], $this->data);
    }

    public function styles(Worksheet $sheet)
    {
        // Style the column headings
        $sheet->getStyle('A6:Q6')->getFont()->setBold(true);
    }
}
