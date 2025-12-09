<script setup>
import { useForm } from '@inertiajs/vue3';
import EquipmentTechnology from '@/Components/Stores/ContactInfo/EquipmentTechnology.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import OthersDetailsCard from '@/Components/Stores/ContactInfo/OthersDetailsCard.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import StoreAddressCard from '@/Components/Stores/ContactInfo/StoreAddressCard.vue';

const emits = defineEmits(['back', 'next']);

const props = defineProps({
    store: Object,
});

const form = useForm({
    store_province: props.store?.store_province,
    store_city: props.store?.store_city,
    store_barangay: props.store?.store_barangay,
    store_street: props.store?.store_street,
    store_postal_code: props.store?.store_postal_code,
    store_phone_number: props.store?.store_phone_number,
    store_mobile_number: props.store?.store_mobile_number,
    store_email: props.store?.store_email,
    with_cctv: props.store?.with_cctv,
    cctv_installed_at: props.store?.cctv_installed_at,
    with_internet: props.store?.with_internet,
    internet_installed_at: props.store?.internet_installed_at,
    with_pos: props.store?.with_pos,
    pos_name: props.store?.pos_name,
    pos_installed_at: props.store?.pos_installed_at,
    warehouse: props.store?.warehouse,
    custom_warehouse_name: props.store?.custom_warehouse_name,
    warehouse_remarks: props.store?.warehouse_remarks,
    current_step: 'contact-info',
    application_step: 'specifications',
});

function handleNext() {
    form.post(route('stores.update', props.store.id));
}
</script>

<template>
    <form @submit.prevent="handleNext">
        <div class="space-y-6">
            <StoreAddressCard :form="form" :store="store" />
            <EquipmentTechnology :form="form" :store="store" />
            <OthersDetailsCard :form="form" :store="store" />
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
