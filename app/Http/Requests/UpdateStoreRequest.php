<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'franchisee_id' => 'nullable',
            'store_status' => Rule::requiredIf($this->input('current_step') === 'basic-details'),
            'jbs_name' => Rule::requiredIf($this->input('current_step') === 'basic-details'),
            'cluster_code' => 'nullable',
            'jbmis_code' => 'nullable',
            'store_type' => Rule::requiredIf($this->input('current_step') === 'basic-details'),
            'store_group' => Rule::requiredIf($this->input('current_step') === 'basic-details'),
            'sales_point_code' => 'nullable',
            'region' => 'nullable',
            'area' => 'nullable',
            'district' => 'nullable',
            'google_maps_link' => 'nullable',
            'om_district_code' => 'nullable',
            'om_district_name' => 'nullable',
            'om_district_manager' => 'nullable',
            'om_cost_center_code' => 'nullable',
            'old_continuing_license_fee' => 'nullable',
            'current_continuing_license_fee' => 'nullable',
            'continuing_license_fee_in_effect' => 'nullable',
            'brf_in_effect' => 'nullable',
            'report_percent' => 'nullable',
            'date_opened' => 'nullable',
            'franchise_date' => 'nullable',
            'original_franchise_date' => 'nullable',
            'renewal_date' => 'nullable',
            'last_renewal_date' => 'nullable',
            'effectivity_date' => 'nullable',
            'target_opening_date' => 'nullable',
            'soft_opening_date' => 'nullable',
            'grand_opening_date' => 'nullable',
            'store_province' => 'nullable',
            'store_city' => 'nullable',
            'store_barangay' => 'nullable',
            'store_street' => 'nullable',
            'store_postal_code' => 'nullable',
            'store_phone_number' => 'nullable',
            'store_mobile_number' => 'nullable',
            'store_email' => 'nullable',
            'with_cctv' => 'nullable',
            'cctv_installed_at' => 'nullable',
            'with_internet' => 'nullable',
            'internet_installed_at' => 'nullable',
            'with_pos' => 'nullable',
            'pos_name' => 'nullable',
            'pos_installed_at' => 'nullable',
            'warehouse' => 'nullable',
            'custom_warehouse_name' => 'nullable',
            'warehouse_remarks' => 'nullable',
            'bir_2303' => 'nullable',
            'cgl_insurance_policy_number' => 'nullable',
            'cgl_expiry_date' => 'nullable',
            'fire_insurance_policy_number' => 'nullable',
            'fire_expiry_date' => 'nullable',
            'area_population' => 'nullable',
            'catchment' => 'nullable',
            'foot_traffic' => 'nullable',
            'manpower' => 'nullable',
            'rental' => 'nullable',
            'square_meter' => 'nullable',
            'sales_per_capita' => 'nullable',
            'projected_peso_bread_sales_per_month' => 'nullable',
            'projected_peso_non_bread_sales_per_month' => 'nullable',
            'projected_total_cost' => 'nullable',
            'contract_of_lease_start_date' => 'nullable',
            'contract_of_lease_end_date' => 'nullable',
            'escalation' => 'nullable',
            'lessor_name' => 'nullable',
            'lease_payment_date' => 'nullable',
            'notarized_stamp_payment_receipt_number' => 'nullable',
            'col_notarized_date' => 'nullable',
            'col_notarized_by' => 'nullable',
            'maintenance_last_repaint_at' => 'nullable',
            'maintenance_last_renovation_at' => 'nullable',
            'maintenance_temporary_closed_at' => 'nullable',
            'maintenance_temporary_closed_reason' => 'nullable',
            'maintenance_reopening_date' => 'nullable',
            'maintenance_permanent_closure_date' => 'nullable',
            'maintenance_permanent_closure_reason' => 'nullable',
            'maintenance_upgrade_date' => 'nullable',
            'maintenance_downgrade_date' => 'nullable',
            'maintenance_remarks' => 'nullable',
            'maintenance_store_acquired_at' => 'nullable',
            'maintenance_store_transferred_at' => 'nullable',
            'maintenance_old_franchisee_code' => 'nullable',
            'maintenance_old_branch_code' => 'nullable',
            'is_draft' => 'nullable',
            'application_step' => 'nullable',
            'photos' => 'nullable',
            'delete_photo_id' => 'nullable',
            'documents_legal_franchise_agreement' => 'nullable',
            'documents_lease_property' => 'nullable',
            'documents_business_compliance' => 'nullable',
            'documents_insurance_policies' => 'nullable',
            'documents_other_clearances_forms' => 'nullable',
            'documents_others' => 'nullable',
        ];

        return array_intersect_key($rules, request()->all());
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateClusterJbmisCoordination($validator);
        });
    }

    /**
     * Validate that cluster_code and jbmis_code changes are coordinated.
     */
    protected function validateClusterJbmisCoordination($validator)
    {
        // Only validate if we're updating a store (route parameter exists)
        if (!$this->route('store')) {
            return;
        }

        // Only validate if cluster_code or jbmis_code are actually present in the request
        $hasClusterCode = $this->has('cluster_code');
        $hasJbmisCode = $this->has('jbmis_code');
        
        if (!$hasClusterCode && !$hasJbmisCode) {
            return; // Neither field is being updated, skip validation
        }

        $clusterCode = $this->input('cluster_code');
        $jbmisCode = $this->input('jbmis_code');

        $store = $this->route('store');
        $originalClusterCode = $store->cluster_code;
        $originalJbmisCode = $store->jbmis_code;

        $clusterChanged = $hasClusterCode && $clusterCode !== $originalClusterCode;
        $jbmisChanged = $hasJbmisCode && $jbmisCode !== $originalJbmisCode;

        // Check if both original values exist (not null)
        $bothOriginalValuesExist = !is_null($originalClusterCode) && !is_null($originalJbmisCode);

        // Coordinated update validation only applies when both original values exist
        if ($bothOriginalValuesExist) {
            // If either code is changing, both must change
            if ($clusterChanged && !$jbmisChanged) {
                $validator->errors()->add('jbmis_code', 
                    'JBMIS code must also be updated when cluster code changes.');
            }

            if ($jbmisChanged && !$clusterChanged) {
                $validator->errors()->add('cluster_code', 
                    'Cluster code must also be updated when JBMIS code changes.');
            }
        }

        // If any field is changing, ensure they're not empty
        if ($clusterChanged || $jbmisChanged) {
            if (empty($clusterCode) && $clusterChanged) {
                $validator->errors()->add('cluster_code', 
                    'Cluster code cannot be empty when updating.');
            }
            if (empty($jbmisCode) && $jbmisChanged) {
                $validator->errors()->add('jbmis_code', 
                    'JBMIS code cannot be empty when updating.');
            }
        }
    }
}
