<?php

namespace App\Exports\Reports;

use App\Enums\StoreGroupEnum;
use App\Models\Store;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GanvioReportExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    protected $filters;

    protected $data;

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
            ->when($filters['region'] ?? null, fn ($q, $region) => $q->where('region', $region))
            ->when(! empty($storeGroupValues), fn ($q) => $q->whereIn('store_group', $storeGroupValues))
            ->where('is_draft', false)
            ->orderBy('jbs_name')
            ->get()
            ->map(function ($store) {
                return [
                    $store->store_status,
                    $store->store_code,
                    $store->maintenance_old_branch_code,
                    $store->store_type,
                    $store->soft_opening_date ? Carbon::parse($store->soft_opening_date)->format('Y-m-d') : null,
                    $store->grand_opening_date ? Carbon::parse($store->grand_opening_date)->format('Y-m-d') : null,
                    $store->original_franchise_date ? Carbon::parse($store->original_franchise_date)->format('Y-m-d') : null,
                    $store->maintenance_store_acquired_at,
                    $store->maintenance_permanent_closure_date,
                    $store->with_pos ? 'Yes' : 'No',
                    $store->pos_installed_at,
                    $store->catchment,
                    $store->manpower,
                    $store->square_meter,
                    $store->bir_2303,
                    $store->jbs_name,
                    $store->store_street,
                    $store->store_barangay,
                    $store->store_city,
                    $store->store_province,
                    $store->store_postal_code,
                    $store->region,
                    $store->om_cost_center_code,
                    $store->om_district_name,
                    $store->district,
                    $store->om_district_manager,
                    $store->franchisee?->franchisee_code,
                    $store->franchisee?->corporation_name,
                    $store->franchisee?->last_name,
                    $store->franchisee?->first_name,
                    $store->warehouse_remarks,
                    $store->warehouse,
                    $store->cluster_code,
                ];
            })->toArray();
    }

    public function array(): array
    {
        return array_merge($this->data);
    }

    public function headings(): array
    {
        return [
            ['Report: Ganvio Report'],
            ['Region:', $this->filters['region'] ?? 'All'],
            ['Store Group:', $this->filters['store_group'] ?? 'All'],
            [''], // empty row before actual table headings
            [ // This is row 5 (Headings row)
                'Status',
                'Branch Code',
                'Previous Code',
                'Store Type',
                'Soft Opening',
                'Grand Opening',
                'Original Franchise Date',
                'Acquisition date',
                'Closed Date',
                'POS',
                'POS Live Date',
                'Catchment',
                'Manpower',
                'Sqm',
                'TIN',
                'JBS Name',
                'Street',
                'Barangay',
                'Municipality/City',
                'Province',
                'Postal Code',
                'Region',
                'Cost Center',
                'BGC District Name',
                'District',
                'District Manager/Area Manager',
                'Franchisee Code',
                'Corporation Name',
                'Last Name',
                'First Name',
                'Remarks',
                'Warehouse',
                'Cluster',
            ],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Bold the column headings (row 5)
        $sheet->getStyle('A5:AG5')->getFont()->setBold(true);
    }
}
