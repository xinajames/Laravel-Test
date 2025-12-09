<?php

namespace App\Import\DataMigration;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;

class JFMStoreProfileDataMigrationSheet implements SkipsEmptyRows, ToCollection
{
    private $data;

    private $columns;

    public function __construct()
    {
        $this->columns = [
            'profile_photo',                           // Store Photo File Path
            'branch_code',                             // Branch Code
            'jbmis_code',                              // JBMIS Code
            'jbs_name',                                // JBS Name
            'store_type',                              // Store Type
            'store_group',                             // Store Group
            'cluster_code',                            // Cluster Code
            'franchisee_code',                         // Franchisee Code
            'sales_point_code',                        // Sales Point Code
            'status',                                  // Status
            'region',                                  // Region
            'area',                                    // Area
            'district',                                // District
            'district_code',                           // District Code
            'district_name',                           // District Name
            'district_manager',                        // District Manager
            'cost_center_code',                        // Cost Center Code
            'warehouse',                               // Warehouse
            'old_continuing_license_fee',              // Old Continuing License Fee %
            'current_continuing_license_fee',          // Current Continuing License Fee %
            'continuing_license_fee_in_effect',        // Continuing License Fee % in Effect
            'brf_in_effect',                           // BRF in Effect
            'report_percent',                          // Report Percent
            'date_opened',                             // Date Opened
            'franchise_date',                          // Franchise Date
            'original_franchise_date',                 // Original Franchise Date
            'renewal_date',                            // Renewal Date
            'last_renewal_date',                       // Last Renewal Date
            'effectivity_date',                        // Effectivity Date
            'target_opening_date',                     // Target Opening
            'soft_opening_date',                       // Soft Opening
            'grand_opening_date',                      // Grand Opening
            'perm_closure_date',                       // Perm Closure Date
            'temp_closure_date',                       // Temp Closure Date
            'reopening_date',                          // Re-Opening Date
            'projected_peso_sales_bread',              // Projected Peso Sales Bread
            'projected_peso_sales_nonbread',           // Projected Peso Sales NonBread
            'bir_2303',                                // BIR 2303
            'cgl_insurance_policy_number',             // CGL Insurance Policy Number
            'cgl_expiry_date',                         // Expiry Date (CGL)
            'fire_insurance_policy_number',            // Fire Insurance Policy Number
            'fire_expiry_date',                        // Expiry Date (Fire)
            'area_population',                         // Area Population
            'catchment',                               // Catchment
            'foot_traffic',                            // Foot Traffic
            'manpower',                                // Manpower
            'rental',                                  // Rental
            'square_meter',                            // Square Meter
            'sales_per_capita',                        // Sales Per Capital
            'total_projected_cost',                    // Total Projected Cost
            'contract_of_lease_start_date',            // Contract of Lease Start Date
            'contract_of_lease_end_date',              // Contract of Lease End Date (Expiry Date)
            'escalation',                              // Escalation
            'lessors_name',                            // Lessor's Name
            'payment_date',                            // Payment Date
            'notarized_stamp_payment_receipt_number',  // Notarized Stamp Payment (Receipt Number)
            'notarization_of_col_date',                // Date of Notarization of COL
            'col_notarized_by',                        // Name of the Attorney who Notarized
            'last_repaint_date',                       // Last Repaint
            'last_renovation_date',                    // Last Renovation
            'temp_closure_date_reason',                // Temp Closure Date (*** duplicate)
            'temp_closure_reason',                     // Reason (for temp closure)
            'permanent_closure_date',                  // Permanent Closure Date (*** duplicate)
            'permanent_closure_reason',                // Reason (for permanent closure)
            'upgrade_date',                            // Upgrade Date
            'downgrade_date',                          // Downgrade Date
            'maintenance_remarks',                     // Remarks
            'store_acquisition_date',                  // Store Acquisition Date
            'store_transfer_date',                     // Store Transfer Date
            'old_franchise_code',                      // Old Franchisee Code
            'old_branch_code',                         // Old Branch Code
            'province',                                // Province
            'city_municipality',                       // City or Municipality
            'barangay',                                // Barangay
            'street',                                  // Street
            'postal_code',                             // Postal Code
            'telephone_number',                        // Telephone Number
            'cellphone_number',                        // Cellphone Number
            'email_address',                           // Email Address
            'with_cctv',                               // With CCTV
            'installation_date',                       // Installation Date
            'with_internet',                           // With Internet
            'with_pos',                                // With POS
            'warehouse_remarks',                       // Remarks (warehouse)
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
