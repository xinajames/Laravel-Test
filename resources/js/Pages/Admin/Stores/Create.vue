<script setup>
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref, watch } from 'vue';
import { CREATE_STORE_STEP } from '@/Composables/Enums.js';

import MainLayout from '@/Layouts/Admin/MainLayout.vue';
import CreateStoreNav from '@/Components/Stores/Create/CreateStoreNav.vue';
import BasicDetails from '@/Components/Stores/Create/BasicDetails.vue';
import ContactInfo from '@/Components/Stores/Create/ContactInfo.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import Specifications from '@/Components/Stores/Create/Specifications.vue';
import StoreRequirements from '@/Components/Stores/Create/StoreRequirements.vue';
import Finish from '@/Components/Stores/Create/Finish.vue';
import ConfirmationModal from '@/Components/Modal/ConfirmationModal.vue';

const props = defineProps({
    store: Object,
});

const currentStep = ref(null);

const basicDetails = ref(null);
const contactInfo = ref(null);
const specifications = ref(null);
const storeRequirements = ref(null);
const finish = ref(null);

const confirmationModal = reactive({
    action: route('stores.cancelCreate', props.store.id),
    open: false,
    header: 'Cancel Store Creation',
    message: 'Cancelling will permanently discard all entered information. Do you want to proceed?',
    icon: 'document',
    action_label: 'Cancel Store Creation',
});

function handleStep(step) {
    currentStep.value = step;
}

function handleExit() {
    router.visit(route('stores'));
}

watch(
    () => props.store,
    (value) => {
        currentStep.value = value.application_step;
    },
    {
        immediate: true,
    }
);
</script>

<template>
    <Head title="Store Create" />

    <MainLayout
        :show-button="currentStep !== 'finished'"
        buttonText="Save & Exit"
        subText="Fill in the necessary details to create and register a new store."
        title="Add Julie's Store"
        @action="handleExit()"
    >
        <template #left-slot>
            <CreateStoreNav :current-step="currentStep" class="border border-gray rounded" />
            <SecondaryButton
                v-if="currentStep !== 'finished'"
                class="mt-6 !font-medium !text-gray-700 !ml-1"
                @click="confirmationModal.open = true"
            >
                Cancel Create
            </SecondaryButton>
        </template>
        <template #right-slot>
            <BasicDetails
                v-if="currentStep === CREATE_STORE_STEP.BasicDetails"
                ref="basicDetails"
                :store="store"
            />

            <ContactInfo
                v-if="currentStep === CREATE_STORE_STEP.ContactInfo"
                :store="store"
                @back="handleStep(CREATE_STORE_STEP.BasicDetails)"
            />

            <Specifications
                v-if="currentStep === CREATE_STORE_STEP.Specifications"
                :store="store"
                @back="handleStep(CREATE_STORE_STEP.ContactInfo)"
            />

            <StoreRequirements
                v-if="currentStep === CREATE_STORE_STEP.StoreRequirements"
                :store="store"
                @back="handleStep(CREATE_STORE_STEP.Specifications)"
            />

            <Finish v-if="currentStep === CREATE_STORE_STEP.Finished" :store="store" />
        </template>
    </MainLayout>

    <ConfirmationModal
        :action="confirmationModal.action"
        :action_label="confirmationModal.action_label"
        :header="confirmationModal.header"
        :icon="confirmationModal.icon"
        :message="confirmationModal.message"
        :open="confirmationModal.open"
        @close="confirmationModal.open = false"
    />

    <Teleport to="#header"></Teleport>
</template>
