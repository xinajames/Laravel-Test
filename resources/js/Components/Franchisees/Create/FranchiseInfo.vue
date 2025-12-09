<script setup>
import { useForm } from '@inertiajs/vue3';
import AdditionalNotesCard from '@/Components/Franchisees/FranchiseInfo/AdditionalNotesCard.vue';
import ApplicationDetailsCard from '@/Components/Franchisees/FranchiseInfo/ApplicationDetailsCard.vue';
import BackgroundInformationCard from '@/Components/Franchisees/FranchiseInfo/BackgroundInformationCard.vue';
import FranchiseManagementContactsCard from '@/Components/Franchisees/FranchiseInfo/FranchiseManagementContactsCard.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import TrainingManualsCard from '@/Components/Franchisees/FranchiseInfo/TrainingManualsCard.vue';

const emits = defineEmits(['back', 'next']);

const props = defineProps({
    franchisee: Object,
});

const form = useForm({
    fm_point_person: props.franchisee?.fm_point_person,
    fm_email_address: props.franchisee?.fm_email_address,
    fm_email_address_2: props.franchisee?.fm_email_address_2,
    fm_email_address_3: props.franchisee?.fm_email_address_3,
    fm_district_manager: props.franchisee?.fm_district_manager,
    fm_contact_number: props.franchisee?.fm_contact_number,
    fm_contact_number_2: props.franchisee?.fm_contact_number_2,
    fm_contact_number_3: props.franchisee?.fm_contact_number_3,
    fm_region: props.franchisee?.fm_region,
    date_start_bakery_management_seminar: props.franchisee?.date_start_bakery_management_seminar,
    date_end_bakery_management_seminar: props.franchisee?.date_end_bakery_management_seminar,
    date_start_bread_baking_course: props.franchisee?.date_start_bread_baking_course,
    date_end_bread_baking_course: props.franchisee?.date_end_bread_baking_course,
    operations_manual_number: props.franchisee?.operations_manual_number,
    operations_manual_release: props.franchisee?.operations_manual_release,
    date_applied: props.franchisee?.date_applied,
    date_approved: props.franchisee?.date_approved,
    background: props.franchisee?.background,
    custom_background: props.franchisee?.custom_background,
    education: props.franchisee?.education,
    course: props.franchisee?.course,
    occupation: props.franchisee?.occupation,
    source_of_information: props.franchisee?.source_of_information,
    custom_source_of_information: props.franchisee?.custom_source_of_information,
    legacy: props.franchisee?.legacy,
    generation: props.franchisee?.generation,
    custom_generation: props.franchisee?.custom_generation,
    date_separated: props.franchisee?.date_separated,
    remarks: props.franchisee?.remarks,
    current_step: 'franchisee-info',
    application_step: 'requirements',
    is_draft: true,
});

function handleNext() {
    form.post(route('franchisees.update', props.franchisee.id));
}

defineExpose({
    form,
});
</script>
<template>
    <form @submit.prevent="handleNext">
        <div class="space-y-6">
            <FranchiseManagementContactsCard :form="form" :franchisee="franchisee" />
            <TrainingManualsCard :form="form" :franchisee="franchisee" />
            <ApplicationDetailsCard :form="form" :franchisee="franchisee" />
            <BackgroundInformationCard :form="form" :franchisee="franchisee" />
            <AdditionalNotesCard :form="form" :franchisee="franchisee" />
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
                <PrimaryButton :disabled="form.processing" type="submit">Next</PrimaryButton>
            </div>
        </div>
    </form>
</template>
