<?php

namespace App\Exports\Reports;

use App\Enums\StoreGroupEnum;
use App\Models\Store;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ContractOfLeaseReportExport implements FromArray, ShouldAutoSize, WithStyles
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
            ->with('franchisee')
            ->where('is_draft', false)
            ->where('deleted_at', null)
            ->when($filters['region'] ?? null, fn ($q) => $q->where('region', $filters['region']))
            ->when(! empty($storeGroupValues), fn ($q) => $q->whereIn('store_group', $storeGroupValues))
            ->when(isset($filters['year']), function ($query) {
                $query->whereNotNull('contract_of_lease_end_date');
            })
            ->when(isset($filters['year']), function ($query) use ($filters) {
                $query->whereYear('contract_of_lease_end_date', $filters['year']);
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
                    $store->contract_of_lease_start_date ? Carbon::parse($store->contract_of_lease_start_date)->format('Y-m-d') : null,
                    $store->contract_of_lease_end_date ? Carbon::parse($store->contract_of_lease_end_date)->format('Y-m-d') : null,
                    $store->escalation ?? null,
                    $store->lessor_name ?? null,
                    $store->lease_payment_date ? Carbon::parse($store->lease_payment_date)->format('Y-m-d') : null,
                    $store->notarized_stamp_payment_receipt_number ?? null,
                    $store->col_notarized_date ? Carbon::parse($store->col_notarized_date)->format('Y-m-d') : null,
                    $store->col_notarized_by ?? 'N/A',
                    $store->soft_opening_date ? Carbon::parse($store->soft_opening_date)->format('Y-m-d') : null,
                    $store->grand_opening_date ? Carbon::parse($store->grand_opening_date)->format('Y-m-d') : null,
                    $store->original_franchise_date ? Carbon::parse($store->original_franchise_date)->format('Y-m-d') : null,
                ];
            })
            ->toArray();
    }

    public function array(): array
    {
        return array_merge([
            ['Report: Contract of Lease Report'],
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
                'Contract of Lease Start Date',
                'Contract of Lease End Date',
                'Escalation',
                'Lessor Name',
                'Lease Payment Date',
                'Notarized Stamp Payment Receipt Number',
                'COL Notarized Date',
                'COL Notarized By',
                'Soft Opening',
                'Grand Opening',
                'Original Franchise Date',
            ],
        ], $this->data);
    }

    public function styles(Worksheet $sheet)
    {
        // Style the column headings
        $sheet->getStyle('A6:X6')->getFont()->setBold(true);
    }
}
