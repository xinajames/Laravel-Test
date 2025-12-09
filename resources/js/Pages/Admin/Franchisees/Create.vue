<script setup>
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref, watch } from 'vue';
import { FRANCHISEE_APPLICATION_STEP } from '@/Composables/Enums.js';

import BasicDetails from '@/Components/Franchisees/Create/BasicDetails.vue';
import Finish from '@/Components/Franchisees/Create/Finish.vue';
import FranchiseApplicationNav from '@/Components/Franchisees/FranchiseApplicationNav.vue';
import FranchiseInfo from '@/Components/Franchisees/Create/FranchiseInfo.vue';
import MainLayout from '@/Layouts/Admin/MainLayout.vue';
import Requirements from '@/Components/Franchisees/Create/Requirements.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import ConfirmationModal from '@/Components/Modal/ConfirmationModal.vue';

const props = defineProps({
    franchisee: Object,
});

const currentStep = ref(null);

const basicDetails = ref(null);
const franchiseInfo = ref(null);
const requirements = ref(null);
const finish = ref(null);

const confirmationModal = reactive({
    action: route('franchisees.cancelApplication', props.franchisee.id),
    open: false,
    header: 'Cancel Application',
    message: 'Are you sure you want to cancel your application? This action cannot be undone.',
    icon: 'document',
    action_label: 'Cancel Application',
});

function handleStep(step) {
    currentStep.value = step;
}

function handleExit() {
    router.visit(route('franchisees'));
}

watch(
    () => props.franchisee,
    (value) => {
        currentStep.value = value.application_step;
    },
    {
        immediate: true,
    }
);
</script>

<template>
    <Head title="Franchisees Create" />

    <MainLayout
        :show-button="currentStep !== 'finished'"
        buttonText="Save & Exit"
        subText="Fill in the necessary details to create and register a new franchisee."
        title="Franchisee Application"
        @action="handleExit()"
    >
        <template #left-slot>
            <FranchiseApplicationNav
                :current-step="currentStep"
                class="border border-gray rounded"
            />
            <SecondaryButton
                v-if="currentStep !== 'finished'"
                class="mt-6 !font-medium !text-gray-700 !ml-1"
                @click="confirmationModal.open = true"
            >
                Cancel Application
            </SecondaryButton>
        </template>
        <template #right-slot>
            <BasicDetails
                v-if="currentStep === FRANCHISEE_APPLICATION_STEP.BasicDetails"
                ref="basicDetails"
                :franchisee="franchisee"
            />
            <FranchiseInfo
                v-if="currentStep === FRANCHISEE_APPLICATION_STEP.FranchiseInfo"
                ref="franchiseInfo"
                :franchisee="franchisee"
                @back="handleStep('basic-details')"
            />
            <Requirements
                v-if="currentStep === FRANCHISEE_APPLICATION_STEP.Requirements"
                ref="requirements"
                :franchisee="franchisee"
                @back="handleStep('franchisee-info')"
            />
            <Finish
                v-if="currentStep === FRANCHISEE_APPLICATION_STEP.Finished"
                ref="finish"
                :franchisee="franchisee"
            />
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
