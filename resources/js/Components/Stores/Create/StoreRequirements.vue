<script setup>
import { reactive } from 'vue';
import { useForm } from '@inertiajs/vue3';

import ConfirmationModal from '@/Components/Modal/ConfirmationModal.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import LegalFranchiseAgreements from '@/Components/Stores/StoreRequirements/LegalFranchiseAgreements.vue';
import LeasePropertyDocuments from '@/Components/Stores/StoreRequirements/LeasePropertyDocuments.vue';
import BusinessComplianceDocuments from '@/Components/Stores/StoreRequirements/BusinessComplianceDocuments.vue';
import InsurancePolicies from '@/Components/Stores/StoreRequirements/InsurancePolicies.vue';
import OtherClearancesAndForms from '@/Components/Stores/StoreRequirements/OtherClearancesAndForms.vue';
import StoreOtherDocuments from '@/Components/Stores/StoreRequirements/StoreOtherDocuments.vue';

const props = defineProps({
    store: Object,
});

const emits = defineEmits(['back', 'next']);

const form = useForm({
    documents_legal_franchise_agreement: [
        {
            files: [],
            label: 'Franchise Agreement',
            value: 'franchise_agreement',
        },
        {
            files: [],
            label: 'Supplemental Agreement',
            value: 'supplemental_agreement',
        },
        {
            files: [],
            label: 'Amendment to Franchise Agreement',
            value: 'amendment_to_franchise_agreement',
        },
        {
            files: [],
            label: 'Amendment to Supplemental Agreement',
            value: 'amendment_to_supplemental_agreement',
        },
    ],
    documents_lease_property: [
        {
            files: [],
            label: 'Contract of Lease',
            value: 'contract_of_lease',
        },
        {
            files: [],
            label: 'Transfer Certificate of Title',
            value: 'transfer_certificate_of_title',
        },
        {
            files: [],
            label: 'Tax Declaration',
            value: 'tax_declaration',
        },
    ],
    documents_business_compliance: [
        {
            files: [],
            label: 'Department of Trade and Industry',
            value: 'department_of_trade_and_industry',
        },
        {
            files: [],
            label: "Business / Mayor's Permit",
            value: 'business_mayors_permit',
        },
        {
            files: [],
            label: 'Certificate of Registration',
            value: 'certificate_of_registration',
        },
        {
            files: [],
            label: 'Food and Drug Administration',
            value: 'food_and_drug_administration',
        },
    ],
    documents_insurance_policies: [
        {
            files: [],
            label: 'CGL Policy',
            value: 'cgl_policy',
        },
        {
            files: [],
            label: 'Fire Policy',
            value: 'fire_policy',
        },
        {
            files: [],
            label: 'GPA Policy',
            value: 'gpa_policy',
        },
    ],
    documents_other_clearances_forms: [
        {
            files: [],
            label: 'Opening Clearance',
            value: 'opening_clearance',
        },
        {
            files: [],
            label: 'Closure Clearance',
            value: 'closure_clearance',
        },
        {
            files: [],
            label: 'Franchise Clearance (Expansion or Renewal)',
            value: 'franchise_clearance',
        },
        {
            files: [],
            label: 'Store Movement',
            value: 'store_movement',
        },
        {
            files: [],
            label: 'Store Closure Form - BGC',
            value: 'store_closure_form',
        },
        {
            files: [],
            label: 'Store Closure Form - BGC',
            value: 'store_closure_form',
        },
        {
            files: [],
            label: 'Closure Letter',
            value: 'closure_letter',
        },
        {
            files: [],
            label: 'Reminder Letter',
            value: 'reminder_letter',
        },
    ],
    documents_others: [
        {
            files: [],
            label: 'Store Other Documents',
            value: 'store_other_documents',
        },
    ],
    current_step: 'store-requirements',
    application_step: 'finished',
    is_draft: false,
});

// Function to emit next event
function handleNext() {
    emits('next');
}

const confirmationModal = reactive({
    action: route('stores.update', props.store.id),
    open: false,
    header: 'Confirm Submission',
    message:
        'Are you ready to submit your store application? Please review your details carefully to ensure all information is accurate.',
    icon: 'document',
    action_label: 'Submit',
});

function submit() {
    confirmationModal.open = true;
}

function handleSuccess() {
    confirmationModal.open = false;
}
</script>

<template>
    <form @submit.prevent="handleNext">
        <div class="space-y-6">
            <LegalFranchiseAgreements :form="form" />
            <LeasePropertyDocuments :form="form" />
            <BusinessComplianceDocuments :form="form" />
            <InsurancePolicies :form="form" />
            <OtherClearancesAndForms :form="form" />
            <StoreOtherDocuments :form="form" />
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
                <PrimaryButton @click="submit">Next</PrimaryButton>
            </div>
        </div>
    </form>

    <ConfirmationModal
        :action="confirmationModal.action"
        :action_label="confirmationModal.action_label"
        :data="form"
        :header="confirmationModal.header"
        :icon="confirmationModal.icon"
        :message="confirmationModal.message"
        :open="confirmationModal.open"
        @close="confirmationModal.open = false"
        @success="handleSuccess"
    />
</template>
