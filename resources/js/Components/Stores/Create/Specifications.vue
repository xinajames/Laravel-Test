<script setup>
import { useForm } from '@inertiajs/vue3';
import FinancialLegalDocuments from '@/Components/Stores/Specifications/FinancialLegalDocuments.vue';
import LeaseLegalInformation from '@/Components/Stores/Specifications/LeaseLegalInformation.vue';
import LocationSpecification from '@/Components/Stores/Specifications/LocationSpecification.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import StoreMaintenanceUpdate from '@/Components/Stores/Specifications/StoreMaintenanceUpdate.vue';

const emits = defineEmits(['back', 'next']);

const props = defineProps({
    store: Object,
});

const form = useForm({
    bir_2303: props.store?.bir_2303,
    cgl_insurance_policy_number: props.store?.cgl_insurance_policy_number,
    cgl_expiry_date: props.store?.cgl_expiry_date,
    fire_insurance_policy_number: props.store?.fire_insurance_policy_number,
    fire_expiry_date: props.store?.fire_expiry_date,
    area_population: props.store?.area_population,
    catchment: props.store?.catchment,
    foot_traffic: props.store?.foot_traffic,
    manpower: props.store?.manpower,
    rental: props.store?.rental,
    square_meter: props.store?.square_meter,
    sales_per_capita: props.store?.sales_per_capita,
    projected_peso_bread_sales_per_month: props.store?.projected_peso_bread_sales_per_month,
    projected_peso_non_bread_sales_per_month: props.store?.projected_peso_non_bread_sales_per_month,
    projected_total_cost: props.store?.projected_total_cost,
    contract_of_lease_renewal_expiry_date: props.store?.contract_of_lease_renewal_expiry_date,
    escalation: props.store?.escalation,
    lessor_name: props.store?.lessor_name,
    lease_payment_date: props.store?.lease_payment_date,
    notarized_stamp_payment_receipt_number: props.store?.notarized_stamp_payment_receipt_number,
    col_notarized_date: props.store?.col_notarized_date,
    col_notarized_by: props.store?.col_notarized_by,
    maintenance_last_repaint_at: props.store?.maintenance_last_repaint_at,
    maintenance_last_renovation_at: props.store?.maintenance_last_renovation_at,
    maintenance_temporary_closed_at: props.store?.maintenance_temporary_closed_at,
    maintenance_temporary_closed_reason: props.store?.maintenance_temporary_closed_reason,
    maintenance_reopening_date: props.store?.maintenance_reopening_date,
    maintenance_permanent_closure_date: props.store?.maintenance_permanent_closure_date,
    maintenance_permanent_closure_reason: props.store?.maintenance_permanent_closure_reason,
    maintenance_upgrade_date: props.store?.maintenance_upgrade_date,
    maintenance_downgrade_date: props.store?.maintenance_downgrade_date,
    maintenance_remarks: props.store?.maintenance_remarks,
    maintenance_store_acquired_at: props.store?.maintenance_store_acquired_at,
    maintenance_store_transferred_at: props.store?.maintenance_store_transferred_at,
    maintenance_old_franchisee_code: props.store?.maintenance_old_franchisee_code,
    maintenance_old_branch_code: props.store?.maintenance_old_branch_code,
    current_step: 'specifications',
    application_step: 'store-requirements',
});

// Function to emit next event
function handleNext() {
    form.post(route('stores.update', props.store.id));
}
</script>

<template>
    <form @submit.prevent="handleNext">
        <div class="space-y-6">
            <FinancialLegalDocuments :form="form" :store="store" />
            <LocationSpecification :form="form" :store="store" />
            <LeaseLegalInformation :form="form" :store="store" />
            <StoreMaintenanceUpdate :form="form" :store="store" />
        </div>

        <div class="pt-10 flex justify-between items-center">
            <div />
            <div class="flex items-center gap-4">
                <SecondaryButton
                    class="!font-medium !text-sm !text-gray-700 disabled:opacity-70 disabled:cursor-not-allowed"
                    type="button"
                    @click="emits('back')"
                >
                    Back
                </SecondaryButton>
                <PrimaryButton :disabled="form.processing" type="submit">Next</PrimaryButton>
            </div>
        </div>
    </form>
</template>
