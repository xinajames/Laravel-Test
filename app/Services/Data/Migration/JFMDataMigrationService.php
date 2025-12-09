<?php

namespace App\Services\Data\Migration;

use App\Enums\StoreGroupEnum;
use App\Enums\StoreStatusEnum;
use App\Enums\StoreWarehouseEnum;
use App\Import\DataMigration\JFMDataMigrationImport;
use App\Models\Franchisee;
use App\Models\Store;
use App\Services\ReminderService;
use App\Traits\HandleTransactions;
use App\Traits\ManageFilesystems;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class JFMDataMigrationService
{
    use HandleTransactions;
    use ManageFilesystems;

    public function __construct(
        private ReminderService $reminderService
    ) {}

    /**
     * Import franchisee and store data from Excel file
     *
     * Logic:
     * - Franchisees: Overwrite if same franchisee_code, create new if different franchisee_code
     * - Stores: Overwrite if same store_code + franchisee_id + cluster_code + sales_point_code combination exists, create new if different combination
     */
    public function importData(string $filePath)
    {
        return $this->transact(function () use ($filePath) {
            // Handle S3 private files by downloading to temporary location
            $tempFilePath = null;
            $actualFilePath = $filePath;

            // Check if file is stored in cloud storage (not a local path)
            if (! file_exists($filePath) && $this->fileExists($filePath)) {
                // Create temporary file
                $tempFileName = 'temp_import_'.Str::random(10).'.xlsx';
                $tempFilePath = storage_path('app/temp/'.$tempFileName);

                // Ensure temp directory exists
                if (! is_dir(dirname($tempFilePath))) {
                    mkdir(dirname($tempFilePath), 0755, true);
                }

                // Download file from cloud storage to temporary location
                $fileContents = $this->readFile($filePath);
                file_put_contents($tempFilePath, $fileContents);
                $actualFilePath = $tempFilePath;
            }

            try {
                // Load configuration data for dropdowns and mappings
                $franchisee_statuses = ['Active' => 1, 'Inactive' => 2, 'Separated' => 3];
                $backgroundList = config('dropdown.background');
                $sourceOfInfoList = config('dropdown.source_of_information');
                $generationList = config('dropdown.generation');

                $warehouseEnums = [];
                foreach (StoreWarehouseEnum::cases() as $case) {
                    $warehouseEnums[] = $case->value;
                }

                // Import Excel data
                $import = new JFMDataMigrationImport;
                Excel::import($import, $actualFilePath, null, \Maatwebsite\Excel\Excel::XLSX);

                $sheets = $import->getSheetDatas();
                $franchiseeEntries = $sheets['Updated Franchisee Profile'];
                $storeEntries = $sheets['Updated Store Profile'];

                $franchisees = [];
                $stores = [];
                $errors = [];

                // Process franchisee data
                foreach ($franchiseeEntries as $index => $entry) {
                    $rowNumber = $index + 2; // +2 because arrays are 0-indexed and Excel has header row

                    // Skip rows without franchisee code
                    if (empty($entry['franchisee_code'])) {
                        $errors[] = "Row {$rowNumber} - Franchisee '{$entry['franchisee_code']}': Franchisee code is empty";

                        continue;
                    }

                    try {
                        // Build franchisee data array
                        $franchiseeData = [
                            'franchisee_profile_photo' => $entry['profile_photo'],
                            'corporation_name' => $entry['corporation_name'],
                            'last_name' => $entry['last_name'],
                            'first_name' => $entry['first_name'],
                            'middle_name' => $entry['middle_name'],
                            'name_suffix' => $entry['suffix'],
                            'status' => $franchisee_statuses[$entry['status']] ?? null,
                            'tin' => $entry['tin'],
                            'birthdate' => $entry['birth_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['birth_date']))->format('Y-m-d') : null,
                            'gender' => $entry['gender'],
                            'nationality' => $entry['nationality'],
                            'religion' => $entry['religion'],
                            'marital_status' => $entry['marital_status'],
                            'spouse_name' => $entry['spouse_name'],
                            'spouse_birthdate' => $entry['spouse_birth_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['spouse_birth_date']))->format('Y-m-d') : null,
                            'wedding_date' => $entry['wedding_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['wedding_date']))->format('Y-m-d') : null,
                            'number_of_children' => $entry['no_of_kids'],
                            'residential_address_province' => $entry['province'],
                            'residential_address_city' => $entry['city_municipality'],
                            'residential_address_barangay' => $entry['barangay'],
                            'residential_address_street' => $entry['street'],
                            'residential_address_postal' => $entry['postal_code'],
                            'contact_number' => null,
                            'contact_number_2' => null,
                            'contact_number_3' => null,
                            'email' => null,
                            'email_2' => null,
                            'email_3' => null,
                            'fm_point_person' => $entry['fmc_point_person'],
                            'fm_contact_number' => null,
                            'fm_contact_number_2' => null,
                            'fm_contact_number_3' => null,
                            'fm_email_address' => null,
                            'fm_email_address_2' => null,
                            'fm_email_address_3' => null,
                            'fm_district_manager' => $entry['fmc_district_manager'],
                            'fm_region' => $entry['fmc_region'],
                            'date_start_bakery_management_seminar' => $entry['bms_seminar_start_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['bms_seminar_start_date']))->format('Y-m-d') : null,
                            'date_end_bakery_management_seminar' => $entry['bms_seminar_end_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['bms_seminar_end_date']))->format('Y-m-d') : null,
                            'date_start_bread_baking_course' => $entry['bbc_start_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['bbc_start_date']))->format('Y-m-d') : null,
                            'date_end_bread_baking_course' => $entry['bbc_end_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['bbc_end_date']))->format('Y-m-d') : null,
                            'operations_manual_number' => $entry['om_number'],
                            'operations_manual_release' => $entry['om_release_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['om_release_date']))->format('Y-m-d') : null,
                            'date_applied' => $entry['application_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['application_date']))->format('Y-m-d') : null,
                            'date_approved' => $entry['approved_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['approved_date']))->format('Y-m-d') : null,
                            'background' => in_array($entry['background'], $backgroundList) ? $entry['background'] : 'Others',
                            'custom_background' => ! in_array($entry['background'], $backgroundList) ? $entry['background'] : null,
                            'source_of_information' => in_array($entry['source_of_information'], $sourceOfInfoList) ? $entry['source_of_information'] : 'Others',
                            'custom_source_of_information' => ! in_array($entry['source_of_information'], $sourceOfInfoList) ? $entry['source_of_information'] : null,
                            'generation' => in_array($entry['generation'], $generationList) ? $entry['generation'] : 'Others',
                            'custom_generation' => ! in_array($entry['generation'], $generationList) ? $entry['generation'] : null,
                            'education' => $entry['education'],
                            'course' => $entry['course'],
                            'occupation' => $entry['occupation'],
                            'legacy' => $entry['legacy'],
                            'remarks' => $entry['remarks'],
                            'date_separated' => $entry['separation_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['separation_date']))->format('Y-m-d') : null,
                            'is_draft' => false,
                        ];

                        // Parse multiple contact numbers from semicolon-separated string
                        $contacts = explode(';', preg_replace('/\s*;\s*/', ';', str_replace(['；', ',', '|', ' '], [';', ';', ';', ''], $entry['contact_number'] ?? '')));
                        $franchiseeData['contact_number'] = $contacts[0] ?? null;
                        $franchiseeData['contact_number_2'] = $contacts[1] ?? null;
                        $franchiseeData['contact_number_3'] = $contacts[2] ?? null;

                        // Parse multiple email addresses from semicolon-separated string
                        $emails = explode(';', preg_replace('/\s*;\s*/', ';', str_replace(['；', ',', '|', ' '], [';', ';', ';', ''], $entry['email_address'] ?? '')));
                        $franchiseeData['email'] = $emails[0] ?? null;
                        $franchiseeData['email_2'] = $emails[1] ?? null;
                        $franchiseeData['email_3'] = $emails[2] ?? null;

                        // Parse multiple FM contact numbers from semicolon-separated string
                        $fmContacts = explode(';', preg_replace('/\s*;\s*/', ';', str_replace(['；', ',', '|', ' '], [';', ';', ';', ''], $entry['fmc_contact_number'] ?? '')));
                        $franchiseeData['fm_contact_number'] = $fmContacts[0] ?? null;
                        $franchiseeData['fm_contact_number_2'] = $fmContacts[1] ?? null;
                        $franchiseeData['fm_contact_number_3'] = $fmContacts[2] ?? null;

                        // Parse multiple FM email addresses from semicolon-separated string
                        $fmEmails = explode(';', preg_replace('/\s*;\s*/', ';', str_replace(['；', ',', '|', ' '], [';', ';', ';', ''], $entry['fmc_email_address'] ?? '')));
                        $franchiseeData['fm_email_address'] = $fmEmails[0] ?? null;
                        $franchiseeData['fm_email_address_2'] = $fmEmails[1] ?? null;
                        $franchiseeData['fm_email_address_3'] = $fmEmails[2] ?? null;

                        // Create or update franchisee (overwrites if same franchisee_code exists)
                        $franchisee = Franchisee::updateOrCreate(
                            ['franchisee_code' => $entry['franchisee_code']],
                            $franchiseeData
                        );

                        $franchisees[] = $franchisee;
                    } catch (Exception $e) {
                        $errors[] = "Row {$rowNumber} - Franchisee '{$entry['franchisee_code']}': ".$e->getMessage();
                        Log::error('Franchisee import error', [
                            'row' => $rowNumber,
                            'franchisee_code' => $entry['franchisee_code'],
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Map store groups from Excel values to enum values
                $storeGroupMap = [
                    'JFC' => StoreGroupEnum::CompanyOwnedJFC()->value,
                    'BGC' => StoreGroupEnum::CompanyOwnedBGC()->value,
                    'FZE' => StoreGroupEnum::FranchiseeFZE()->value,
                ];

                // Map store status from Excel values to enum values
                $storeStatusMap = [
                    'Open' => StoreStatusEnum::Open()->value,
                    'Future' => StoreStatusEnum::Future()->value,
                    'Temporary Closed' => StoreStatusEnum::TemporaryClosed()->value,
                    'Closed' => StoreStatusEnum::Closed()->value,
                    'Deactivated' => StoreStatusEnum::Deactivated()->value,
                ];

                // Process store data
                foreach ($storeEntries as $index => $entry) {
                    $rowNumber = $index + 2; // +2 because arrays are 0-indexed and Excel has header row

                    // Skip rows without required codes
                    if (empty($entry['franchisee_code']) || empty($entry['branch_code'])) {
                        $errors[] = "Row {$rowNumber} - Store '{$entry['branch_code']}' for franchisee '{$entry['franchisee_code']}': Franchisee code or branch code is empty";

                        continue;
                    }

                    try {
                        // Find the franchisee for this store
                        $franchisee = Franchisee::where('franchisee_code', $entry['franchisee_code'])->first();
                        if (! $franchisee) {
                            throw new Exception('Franchisee Not Found: '.$entry['franchisee_code']);
                        }

                        // Check if store already exists to determine if it's newly created
                        $existingStore = Store::where('store_code', $entry['branch_code'])
                            ->where('franchisee_id', $franchisee->id)
                            ->where('cluster_code', $entry['cluster_code'])
                            ->where('sales_point_code', $entry['sales_point_code'])
                            ->where('jbmis_code', $entry['jbmis_code'])
                            ->first();
                        $isNewStore = ! $existingStore;

                        // Build store data array
                        $storeData = [
                            'jbmis_code' => $entry['jbmis_code'],
                            'jbs_name' => $entry['jbs_name'],
                            'store_type' => $entry['store_type'],
                            'store_group' => $storeGroupMap[$entry['store_group']] ?? null,
                            'cluster_code' => $entry['cluster_code'],
                            // 'franchisee_code' => $entry['franchisee_code'],
                            'franchisee_id' => $franchisee->id,
                            'sales_point_code' => $entry['sales_point_code'],
                            'store_status' => $storeStatusMap[$entry['status']] ?? $entry['status'],
                            'region' => $entry['region'],
                            'area' => $entry['area'],
                            'district' => $entry['district'],
                            'om_district_code' => $entry['district_code'],
                            'om_district_name' => $entry['district_name'],
                            'om_district_manager' => $entry['district_manager'],
                            'om_cost_center_code' => $entry['cost_center_code'],
                            'old_continuing_license_fee' => $this->sanitizePercentage($entry['old_continuing_license_fee']),
                            'current_continuing_license_fee' => $this->sanitizePercentage($entry['current_continuing_license_fee']),
                            'continuing_license_fee_in_effect' => $entry['continuing_license_fee_in_effect'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['continuing_license_fee_in_effect']))->format('Y-m-d') : null,
                            'brf_in_effect' => strtoupper(trim($entry['brf_in_effect'])) === 'Y' ? 1 : 0,
                            'report_percent' => $this->sanitizePercentage($entry['report_percent']),
                            'date_opened' => $entry['date_opened'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['date_opened']))->format('Y-m-d') : null,
                            'franchise_date' => $entry['franchise_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['franchise_date']))->format('Y-m-d') : null,
                            'original_franchise_date' => $entry['original_franchise_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['original_franchise_date']))->format('Y-m-d') : null,
                            'renewal_date' => $entry['renewal_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['renewal_date']))->format('Y-m-d') : null,
                            'last_renewal_date' => $entry['last_renewal_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['last_renewal_date']))->format('Y-m-d') : null,
                            'effectivity_date' => $entry['effectivity_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['effectivity_date']))->format('Y-m-d') : null,
                            'target_opening_date' => $entry['target_opening_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['target_opening_date']))->format('Y-m-d') : null,
                            'soft_opening_date' => $entry['soft_opening_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['soft_opening_date']))->format('Y-m-d') : null,
                            'grand_opening_date' => $entry['grand_opening_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['grand_opening_date']))->format('Y-m-d') : null,
                            'maintenance_permanent_closure_date' => $entry['permanent_closure_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['permanent_closure_date']))->format('Y-m-d') : null,
                            'maintenance_temporary_closed_at' => $entry['temp_closure_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['temp_closure_date']))->format('Y-m-d') : null,
                            'maintenance_reopening_date' => $entry['reopening_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['reopening_date']))->format('Y-m-d') : null,
                            'projected_peso_bread_sales_per_month' => $this->sanitizeAmount($entry['projected_peso_sales_bread']),
                            'projected_peso_non_bread_sales_per_month' => $this->sanitizeAmount($entry['projected_peso_sales_nonbread']),
                            'bir_2303' => $entry['bir_2303'],
                            'cgl_insurance_policy_number' => $entry['cgl_insurance_policy_number'],
                            'cgl_expiry_date' => $entry['cgl_expiry_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['cgl_expiry_date']))->format('Y-m-d') : null,
                            'fire_insurance_policy_number' => $entry['fire_insurance_policy_number'],
                            'fire_expiry_date' => $entry['fire_expiry_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['fire_expiry_date']))->format('Y-m-d') : null,
                            'area_population' => $entry['area_population'],
                            'catchment' => $entry['catchment'],
                            'foot_traffic' => $entry['foot_traffic'],
                            'manpower' => $entry['manpower'],
                            'rental' => $entry['rental'],
                            'square_meter' => $entry['square_meter'],
                            'sales_per_capita' => $this->sanitizeAmount($entry['sales_per_capita']),
                            'projected_total_cost' => $this->sanitizeAmount($entry['total_projected_cost']),
                            'contract_of_lease_start_date' => $entry['contract_of_lease_start_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['contract_of_lease_start_date']))->format('Y-m-d') : null,
                            'contract_of_lease_end_date' => $entry['contract_of_lease_end_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['contract_of_lease_end_date']))->format('Y-m-d') : null,
                            'escalation' => $entry['escalation'],
                            'lessor_name' => $entry['lessors_name'],
                            'lease_payment_date' => $entry['payment_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['payment_date']))->format('Y-m-d') : null,
                            'notarized_stamp_payment_receipt_number' => $entry['notarized_stamp_payment_receipt_number'],
                            'col_notarized_date' => $entry['notarization_of_col_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['notarization_of_col_date']))->format('Y-m-d') : null,
                            'col_notarized_by' => $entry['col_notarized_by'],
                            'maintenance_last_repaint_at' => $entry['last_repaint_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['last_repaint_date']))->format('Y-m-d') : null,
                            'maintenance_last_renovation_at' => $entry['last_renovation_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['last_renovation_date']))->format('Y-m-d') : null,
                            'maintenance_temporary_closed_reason' => $entry['temp_closure_reason'],
                            'maintenance_permanent_closure_reason' => $entry['permanent_closure_reason'],
                            'maintenance_upgrade_date' => $entry['upgrade_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['upgrade_date']))->format('Y-m-d') : null,
                            'maintenance_downgrade_date' => $entry['downgrade_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['downgrade_date']))->format('Y-m-d') : null,
                            'maintenance_remarks' => $entry['maintenance_remarks'],
                            'maintenance_store_acquired_at' => $entry['store_acquisition_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['store_acquisition_date']))->format('Y-m-d') : null,
                            'maintenance_store_transferred_at' => $entry['store_transfer_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['store_transfer_date']))->format('Y-m-d') : null,
                            'maintenance_old_franchisee_code' => $entry['old_franchise_code'],
                            'maintenance_old_branch_code' => $entry['old_branch_code'],
                            'store_province' => $entry['province'],
                            'store_city' => $entry['city_municipality'],
                            'store_barangay' => $entry['barangay'],
                            'store_street' => $entry['street'],
                            'store_postal_code' => $entry['postal_code'],
                            'store_phone_number' => $entry['telephone_number'],
                            'store_mobile_number' => $entry['cellphone_number'],
                            'store_email' => $entry['email_address'],
                            'with_cctv' => strtoupper(trim($entry['with_cctv'])) === 'Y' ? 1 : 0,
                            'cctv_installed_at' => $entry['installation_date'] ? Carbon::instance(Date::excelToDateTimeObject((float) $entry['installation_date']))->format('Y-m-d') : null,
                            'with_internet' => strtoupper(trim($entry['with_internet'])) === 'Y' ? 1 : 0,
                            'with_pos' => strtoupper(trim($entry['with_pos'])) === 'Y' ? 1 : 0,
                            'warehouse_remarks' => $entry['warehouse_remarks'],
                            'is_draft' => false,
                        ];

                        // Handle warehouse mapping - use 'Others' if not in predefined list
                        [$storeData['warehouse'], $storeData['custom_warehouse_name']] =
                            ! in_array($entry['warehouse'] ?? '', $warehouseEnums)
                                ? ['Others', $entry['warehouse']]
                                : [$entry['warehouse'], null];

                        // Create or update store (overwrites if same store_code + franchisee_id + cluster_code + sales_point_code + jbmis_code exists)
                        $store = Store::updateOrCreate(
                            [
                                'store_code' => $entry['branch_code'],
                                'franchisee_id' => $franchisee->id,
                                'cluster_code' => $entry['cluster_code'],
                                'sales_point_code' => $entry['sales_point_code'],
                                'jbmis_code' => $entry['jbmis_code'],
                            ],
                            $storeData
                        );

                        // Generate reminder instances for newly created stores
                        if ($isNewStore && ! $store->is_draft) {
                            $this->reminderService->generateStoreReminderInstance($store);
                        }

                        $stores[] = $store;
                    } catch (Exception $e) {
                        $errors[] = "Row {$rowNumber} - Store '{$entry['branch_code']}' for franchisee '{$entry['franchisee_code']}': ".$e->getMessage();
                        Log::error('Store import error', [
                            'row' => $rowNumber,
                            'branch_code' => $entry['branch_code'],
                            'franchisee_code' => $entry['franchisee_code'],
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                return [
                    'franchisees' => $franchisees,
                    'stores' => $stores,
                    'errors' => $errors,
                ];
            } finally {
                // Clean up temporary file if it was created
                if ($tempFilePath && file_exists($tempFilePath)) {
                    unlink($tempFilePath);
                }
            }
        });
    }

    /**
     * Remove formatting from monetary values and convert to float
     * Handles: commas, spaces, tabs
     */
    private function sanitizeAmount($value)
    {
        if (empty($value) || $value === null) {
            return null;
        }

        // Remove common formatting characters
        $sanitized = (string) $value;
        $sanitized = str_replace([',', ' ', '\t'], '', $sanitized);

        // Return null if not a valid number
        if (! is_numeric($sanitized)) {
            return null;
        }

        return (float) $sanitized;
    }

    /**
     * Convert percentage values to consistent format
     * Handles: values with % symbol, decimal format (0.15), percentage format (15)
     */
    private function sanitizePercentage($value)
    {
        if (empty($value) || $value === null) {
            return null;
        }

        $sanitized = (string) $value;

        // If has % symbol, remove it and return as-is (already in percentage format)
        if (strpos($sanitized, '%') !== false) {
            $sanitized = str_replace(['%', ' ', '\t'], '', $sanitized);
            if (! is_numeric($sanitized)) {
                return null;
            }

            return (float) $sanitized;
        }

        // Clean up whitespace
        $sanitized = str_replace([' ', '\t'], '', $sanitized);
        if (! is_numeric($sanitized)) {
            return null;
        }

        $numericValue = (float) $sanitized;

        // Convert decimal format (0.15) to percentage (15)
        if ($numericValue >= 0 && $numericValue <= 1) {
            return $numericValue * 100;
        }

        // Return as-is if already in percentage format
        return $numericValue;
    }
}
