<script setup>
import { computed } from 'vue';
import Modal from '@/Components/Shared/Modal.vue';

const emit = defineEmits(['close']);

const props = defineProps({
    open: Boolean,
    type: String,
});

const handleClose = () => {
    emit('close');
};

const title = computed(() => {
    switch (props.type) {
        case 'total-franchisee':
            return 'Total Franchisee';
        case 'total-stores':
            return 'Total Stores';
        case 'franchise-store':
            return 'Franchise Stores';
        case 'company-owned-store':
            return 'Company Owned Stores';
        case 'opening-closure':
            return 'Opening and Closure Information';
        case 'temporary-closure':
            return 'Temporary Closure Information';
        default:
            return '';
    }
});
</script>

<template>
    <Modal :open="open" :closeable="true" max-width="md" @close="handleClose">
        <template #content>
            <div class="relative px-6 py-5 space-y-4">
                <!-- Title -->
                <h4 class="font-semibold font-sans text-center">{{ title }}</h4>

                <!-- Content -->
                <p v-if="props.type === 'total-franchisee'" class="text-sm text-gray-700 text-left">
                    Displays the count of active franchisees who have at least one store marked as
                    Open or Temporary Closed, grouped by FMC - Region.
                </p>
                <p v-if="props.type === 'total-stores'" class="text-sm text-gray-700 text-left">
                    Displays the count of stores marked as Open or Temporary Closed. This includes
                    stores with no assigned store group or unclear classifications. The data is
                    grouped first by Store Type, then by Region for easier review.
                </p>
                <p v-if="props.type === 'franchise-store'" class="text-sm text-gray-700 text-left">
                    Displays the count of stores under the Franchisee (Full Franchise) group that
                    are currently marked as Open or Temporary Closed. The data is grouped by Store
                    Type and then by Region.
                </p>
                <p
                    v-if="props.type === 'company-owned-store'"
                    class="text-sm text-gray-700 text-left"
                >
                    Displays the count of stores classified under the Company Owned group (JFC and
                    BGC) with a status of Open or Temporary Closed. The data is grouped by Store
                    Type and then by Region.
                </p>
                <p v-if="props.type === 'opening-closure'" class="text-sm text-gray-700 text-left">
                    Shows the monthly count of store openings or permanent closures for the selected
                    year, grouped by Store Type.
                    <br />
                    <br />
                    <strong>Filter options:</strong>
                    <br />
                    • Report Type: Store Openings or Store Closures
                    <br />
                    • Year
                    <br />
                    • Region: LUZ, VIS, MIN
                    <br />
                    • Store Group: Full Franchise or Company Owned (JFC & BGC)
                    <br />
                    <br />
                    Only stores with valid dates and matching filters will be included. Be sure to
                    select the correct options to get accurate results.
                </p>
                <p
                    v-if="props.type === 'temporary-closure'"
                    class="text-sm text-gray-700 text-left"
                >
                    Shows the monthly count of stores that were temporarily closed during the
                    selected year, grouped by Store Type.
                    <br />
                    <br />
                    <strong>Filter options:</strong>
                    <br />
                    • Year
                    <br />
                    • Region: LUZ, VIS, MIN
                    <br />
                    • Store Group: Full Franchise or Company Owned (JFC & BGC)
                    <br />
                    <br />
                    Only stores with valid temporary closure dates within the selected year and
                    matching the filters will be included. Be sure to select the correct options to
                    get accurate results.
                </p>
            </div>
        </template>
    </Modal>
</template>
