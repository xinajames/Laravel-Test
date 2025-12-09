<script setup>
import { useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';
import { FRANCHISEE_APPLICATION_STEP } from '@/Composables/Enums.js';

import ConfirmationModal from '@/Components/Modal/ConfirmationModal.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import PersonalFinancialDocumentsCard from '@/Components/Franchisees/Requirements/PersonalFinancialDocumentsCard.vue';
import CorporateDocumentsCard from '@/Components/Franchisees/Requirements/CorporateDocumentsCard.vue';
import LegalAgreementsSpecialDocumentsCard from '@/Components/Franchisees/Requirements/LegalAgreementsSpecialDocumentsCard.vue';
import OfficialCorrespondenceCard from '@/Components/Franchisees/Requirements/OfficialCorrespondenceCard.vue';
import FranchiseeOtherDocuments from '@/Components/Franchisees/Requirements/FranchiseeOtherDocuments.vue';

const props = defineProps({
    franchisee: Object,
});

const emits = defineEmits(['back', 'submitted']);

const form = useForm({
    documents_personal_financial: [
        {
            files: [],
            label: 'Letter of Intent',
            value: 'letter_of_intent',
        },
        {
            files: [],
            label: 'Franchise Qualification Data Form',
            value: 'franchise_qualification_data_form',
        },
        {
            files: [],
            label: 'Valid ID',
            value: 'valid_id',
        },
        {
            files: [],
            label: 'Bank Certificate',
            value: 'bank_certificate',
        },
        {
            files: [],
            label: 'Medical Certification',
            value: 'medical_certification',
        },
    ],
    documents_corporate: [
        {
            files: [],
            label: 'BIR 2303 (Corporation)',
            value: 'bir_2303',
        },
        {
            files: [],
            label: 'Articles of Incorporation (Corporation)',
            value: 'articles_of_incorporation',
        },
        {
            files: [],
            label: 'SEC (Corporation)',
            value: 'sec_corporation',
        },
    ],
    documents_legal_agreements_special: [
        {
            files: [],
            label: 'Deed of Assignment',
            value: 'deed_of_assignment',
        },
        {
            files: [],
            label: 'Deed of Sale',
            value: 'deed_of_sale',
        },
        {
            files: [],
            label: 'Memorandum of Agreement',
            value: 'memorandum_of_agreement',
        },
        {
            files: [],
            label: 'Memorandum of Understanding',
            value: 'memorandum_of_understanding',
        },
        {
            files: [],
            label: 'Special Power of Attorney',
            value: 'special_power_of_attorney',
        },
    ],
    documents_official_correspondence: [
        {
            files: [],
            label: 'Default Letter',
            value: 'default_letter',
        },
        {
            files: [],
            label: 'Certification Letter',
            value: 'certification_letter',
        },
        {
            files: [],
            label: 'Termination Letter',
            value: 'termination_letter',
        },
    ],
    documents_others: [
        {
            files: [],
            label: 'Franchisee Other Documents',
            value: 'franchisee_other_documents',
        },
    ],
    current_step: 'requirements',
    application_step: FRANCHISEE_APPLICATION_STEP.Finished,
    is_draft: false,
});

const confirmationModal = reactive({
    action: route('franchisees.update', props.franchisee.id),
    open: false,
    header: 'Confirm Submission',
    message:
        'Are you ready to submit your franchise application? Please review your details carefully to ensure all information is accurate.',
    icon: 'document',
    action_label: 'Submit',
});

function submit() {
    confirmationModal.open = true;
}

function handleSuccess() {
    confirmationModal.open = false;
}

defineExpose({
    submit,
});
</script>

<template>
    <div class="space-y-6">
        <PersonalFinancialDocumentsCard :form="form" />
        <CorporateDocumentsCard :form="form" />
        <LegalAgreementsSpecialDocumentsCard :form="form" />
        <OfficialCorrespondenceCard :form="form" />
        <FranchiseeOtherDocuments :form="form" />
    </div>

    <div class="pt-10 flex justify-between items-center">
        <div></div>
        <div class="flex items-center gap-4">
            <SecondaryButton
                class="!font-medium !text-sm !text-gray-700 disabled:opacity-70 disabled:cursor-not-allowed"
                type="button"
                @click="emits('back')"
            >
                Back
            </SecondaryButton>
            <PrimaryButton @click="submit">Submit Application</PrimaryButton>
        </div>
    </div>

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
