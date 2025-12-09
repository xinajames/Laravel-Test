<?php

namespace App\Exports\Reports;

use App\Enums\FranchiseeStatusEnum;
use App\Models\Franchisee;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FranchiseeReportExport implements FromArray, ShouldAutoSize, WithStyles
{
    protected array $filters;

    protected array $data;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;

        $this->data = Franchisee::query()
            ->when(
                isset($filters['region']), function ($query) use ($filters) {
                    $query->where('fm_region', $filters['region']);
                }
            )
            ->where('is_draft', false)
            ->where('deleted_at', null)
            ->orderBy('franchisee_code')
            ->get()
            ->map(function ($franchisee) {
                return [
                    $franchisee->franchisee_code,
                    $franchisee->status ? FranchiseeStatusEnum::getDescription($franchisee->status) : null,
                    $franchisee->corporation_name,
                    $franchisee->last_name,
                    $franchisee->first_name,
                    $franchisee->middle_name,
                    $franchisee->name_suffix,
                    $franchisee->tin,
                    $franchisee->residential_address_street,
                    $franchisee->residential_address_barangay,
                    $franchisee->residential_address_city,
                    $franchisee->residential_address_province,
                    $franchisee->residential_address_postal,
                    $franchisee->contact_number,
                    $franchisee->contact_number_2,
                    $franchisee->contact_number_3,
                    $franchisee->email,
                    $franchisee->fm_region,
                    $franchisee->birthdate ? Carbon::parse($franchisee->birthdate)->format('Y-m-d') : null,
                    $franchisee->birthdate ? Carbon::parse($franchisee->birthdate)->age : null,
                    $franchisee->marital_status,
                    $franchisee->spouse_name,
                    $franchisee->spouse_birthdate ? Carbon::parse($franchisee->spouse_birthdate)->format('Y-m-d') : null,
                    $franchisee->wedding_date ? Carbon::parse($franchisee->wedding_date)->format('Y-m-d') : null,
                    $franchisee->background,
                    $franchisee->education,
                    $franchisee->course,
                    $franchisee->occupation,
                    $franchisee->source_of_information,
                    $franchisee->legacy,
                    $franchisee->generation,
                ];
            })
            ->toArray();
    }

    public function array(): array
    {
        return array_merge([
            ['Report: Franchisee Report'],
            ['Region:', $this->filters['region'] ?? 'All'],
            [''], // empty row before actual table headings
            [ // column headings
                'Franchisee Code',
                'Status',
                'Name of Corporation',
                'Last Name',
                'First Name',
                'Middle Name',
                'Name Suffix',
                'TIN',
                'Street',
                'Barangay',
                'City/ Municipality',
                'Province',
                'Postal Code',
                'Contact Number',
                'Contact Number 2',
                'Contact Number 3',
                'Email',
                'FMC - Region',
                'Birthdate',
                'Age',
                'Marital Status',
                'Spouse Name',
                'Spouse Birthdate',
                'Wedding Date',
                'Background',
                'Education',
                'Course',
                'Occupation',
                'Source of Information',
                'Legacy',
                'Generation',
            ],
        ], $this->data);
    }

    public function styles(Worksheet $sheet)
    {
        // Bold the column headings (row 4)
        $sheet->getStyle('A4:AE4')->getFont()->setBold(true);
    }
}
