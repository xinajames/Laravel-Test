<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import FranchiseeOwnerCard from '@/Components/Stores/BasicDetails/FranchiseeOwnerCard.vue';
import ManagementOperationCard from '@/Components/Stores/BasicDetails/ManagementOperationCard.vue';
import OpeningFranchiseTimeline from '@/Components/Stores/BasicDetails/OpeningFranchiseTimeline.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import StoreOverview from '@/Components/Stores/BasicDetails/StoreOverview.vue';

const emits = defineEmits(['next']);

const props = defineProps({
    store: Object,
});

const form = useForm({
    user: null,
    photos: [],
    delete_photo_id: null,
    franchisee_id: props.store?.franchisee_id,
    franchisee_name: props.store?.franchisee?.full_name,
    corporation_name: props.store?.franchisee?.corporation_name,
    jbs_name: props.store?.jbs_name,
    store_type: props.store?.store_type,
    store_group: props.store?.store_group,
    cluster_code: props.store?.cluster_code,
    jbmis_code: props.store?.jbmis_code,
    franchisee_code: props.store?.franchisee?.franchisee_code,
    sales_point_code: props.store?.sales_point_code,
    store_status: props.store?.store_status,
    region: props.store?.region,
    area: props.store?.area,
    district: props.store?.district,
    google_maps_link: props.store?.google_maps_link,
    om_district_code: props.store?.om_district_code,
    om_district_name: props.store?.om_district_name,
    om_district_manager: props.store?.om_district_manager,
    om_cost_center_code: props.store?.om_cost_center_code,
    old_continuing_license_fee: props.store?.old_continuing_license_fee,
    current_continuing_license_fee: props.store?.current_continuing_license_fee,
    continuing_license_fee_in_effect: props.store?.continuing_license_fee_in_effect,
    brf_in_effect: props.store?.brf_in_effect,
    report_percent: props.store?.report_percent,
    date_opened: props.store?.date_opened,
    franchise_date: props.store?.franchise_date,
    original_franchise_date: props.store?.original_franchise_date,
    renewal_date: props.store?.renewal_date,
    last_renewal_date: props.store?.last_renewal_date,
    effectivity_date: props.store?.effectivity_date,
    target_opening_date: props.store?.target_opening_date,
    soft_opening_date: props.store?.soft_opening_date,
    grand_opening_date: props.store?.grand_opening_date,
    current_step: 'basic-details',
    application_step: 'contact-info',
    is_draft: true,
});

const franchiseeOwner = ref(null);

function handleNext() {
    form.post(route('stores.update', props.store.id));
}

defineExpose({
    form,
});
</script>

<template>
    <form @submit.prevent="handleNext">
        <div class="space-y-6">
            <FranchiseeOwnerCard :id="store.id" ref="franchiseeOwner" :form="form" />
            <StoreOverview :form="form" :store="store" />
            <ManagementOperationCard :form="form" :store="store" />
            <OpeningFranchiseTimeline :form="form" :store="store" />
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
