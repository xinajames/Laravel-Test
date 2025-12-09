<?php

namespace App\Import\DataMigration;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;

class JFMFranchiseeProfileDataMigrationSheet implements SkipsEmptyRows, ToCollection
{
    private $data;

    private $columns;

    public function __construct()
    {
        $this->columns = [
            'profile_photo',
            'franchisee_code',
            'corporation_name',
            'last_name',
            'first_name',
            'middle_name',
            'suffix',
            'status',
            'tin',
            'birth_date',
            'gender',
            'nationality',
            'religion',
            'marital_status',
            'spouse_name',
            'spouse_birth_date',
            'wedding_date',
            'no_of_kids',
            'province',
            'city_municipality',
            'barangay',
            'street',
            'postal_code',
            'residential_address',
            'contact_number',
            'email_address',
            'fmc_point_person',
            'fmc_contact_number',
            'fmc_email_address',
            'fmc_district_manager',
            'fmc_region',
            'bms_seminar_start_date',
            'bms_seminar_end_date',
            'bbc_start_date',
            'bbc_end_date',
            'om_number',
            'om_release_date',
            'application_date',
            'approved_date',
            'background',
            'education',
            'course',
            'occupation',
            'source_of_information',
            'legacy',
            'generation',
            'remarks',
            'separation_date',
        ];
    }

    public function collection(Collection $rows)
    {
        $return = [];

        foreach ($rows as $rowIndex => $column) {
            // skip the header row
            if ($rowIndex == 0) {
                continue;
            }

            $rowData = [];
            for ($i = 0; $i < count($this->columns); $i++) {
                $rowData[$this->columns[$i]] = $column[$i] ?? null;
            }

            $return[] = $rowData;
        }

        $this->data = $return;
    }

    public function getData()
    {
        return $this->data;
    }
}
