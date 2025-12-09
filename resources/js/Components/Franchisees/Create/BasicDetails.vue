<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import ContactInformation from '@/Components/Franchisees/BasicDetails/ContactInformation.vue';
import PersonalInformationCard from '@/Components/Franchisees/BasicDetails/PersonalInformationCard.vue';
import ProfileOverviewCard from '@/Components/Franchisees/BasicDetails/ProfileOverviewCard.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';

const emits = defineEmits(['next']);

const props = defineProps({
    franchisee: Object,
});

const form = useForm({
    profile_photo: props.franchisee?.franchisee_profile_photo,
    corporation_name: props.franchisee?.corporation_name,
    first_name: props.franchisee?.first_name,
    middle_name: props.franchisee?.middle_name,
    last_name: props.franchisee?.last_name,
    name_suffix: props.franchisee?.name_suffix,
    status: props.franchisee?.status,
    tin: props.franchisee?.tin,
    birthdate: props.franchisee?.birthdate,
    age: null,
    gender: props.franchisee?.gender,
    nationality: props.franchisee?.nationality,
    religion: props.franchisee?.religion,
    marital_status: props.franchisee?.marital_status,
    spouse_name: props.franchisee?.spouse_name,
    spouse_birthdate: props.franchisee?.spouse_birthdate,
    wedding_date: props.franchisee?.wedding_date,
    number_of_children: props.franchisee?.number_of_children,
    residential_address_province: props.franchisee?.residential_address_province,
    residential_address_city: props.franchisee?.residential_address_city,
    residential_address_barangay: props.franchisee?.residential_address_barangay,
    residential_address_street: props.franchisee?.residential_address_street,
    residential_address_postal: props.franchisee?.residential_address_postal,
    contact_number: props.franchisee?.contact_number,
    contact_number_2: props.franchisee?.contact_number_2,
    contact_number_3: props.franchisee?.contact_number_3,
    email: props.franchisee?.email,
    email_2: props.franchisee?.email_2,
    email_3: props.franchisee?.email_3,
    current_step: 'basic-details',
    application_step: 'franchisee-info',
    is_draft: true,
});

const profile = ref(null);

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
            <ProfileOverviewCard ref="profile" :form="form" :franchisee="franchisee" />
            <PersonalInformationCard :form="form" :franchisee="franchisee" />
            <ContactInformation :form="form" :franchisee="franchisee" />
        </div>

        <div class="pt-10 flex justify-between items-center">
            <div></div>
            <div class="flex items-center gap-4">
                <SecondaryButton
                    :disabled="true"
                    class="!font-medium !text-sm !text-gray-700 disabled:opacity-70 disabled:cursor-not-allowed"
                    type="button"
                >
                    Back
                </SecondaryButton>
                <PrimaryButton :disabled="form.processing" type="submit">Next</PrimaryButton>
            </div>
        </div>
    </form>
</template>
