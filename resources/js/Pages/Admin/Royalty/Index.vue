<script setup>
import { Head } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import { CalendarIcon } from '@heroicons/vue/24/outline';

import Layout from '@/Layouts/Admin/Layout.vue';
import SearchInput from '@/Components/Common/Input/SearchInput.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import DocumentTextIcon from '@/Components/Icon/DocumentTextIcon.vue';
import Spinner3Icon from '@/Components/Icon/Spinner3Icon.vue';
import DataTable from '@/Pages/Admin/Royalty/DataTable.vue';
import GenerateRoyaltyModal from '@/Components/Modal/GenerateRoyaltyModal.vue';
import ConfirmationModal from '@/Components/Modal/ConfirmationModal.vue';

defineProps({
    generatingBatches: Array,
});

const generateModal = ref(false);
const dataTable = ref(null);

const confirmationModal = reactive({
    action: null,
    data: null,
    open: false,
    header: 'Generating MNSR Royalty Workbook',
    message: 'This may take a few minutes. We’ll let you know once it’s ready for download.',
    icon: 'loading',
    action_label: 'Close',
    cancel_label: null,
});

function openConfirmationModal() {
    generateModal.value = false;

    confirmationModal.open = true;
    confirmationModal.action = null;
    confirmationModal.action_label = 'Close';
    confirmationModal.header = 'Generating MNSR Royalty Workbook';
    confirmationModal.message =
        'This may take a few minutes. We’ll let you know once it’s ready for download.';
    confirmationModal.icon = 'loading';
}
</script>

<template>
    <Head title="Royalty" />

    <Layout :showTopBar="false">
        <div class="mb-8">
            <div class="sm:flex sm:items-center mt-8 mb-6">
                <div class="sm:flex-auto">
                    <h1 class="text-3xl font-bold text-gray-900">Royalty</h1>
                </div>
            </div>

            <div class="mt-4 flex items-center gap-2">
                <SearchInput
                    class="flex-1 !rounded-md"
                    placeholder="Search"
                    @update:modelValue="$refs.dataTable?.search($event)"
                />

                <div>
                    <PrimaryButton class="font-semibold text-sm" @click="generateModal = true">
                        Generate Royalty
                    </PrimaryButton>
                </div>

                <SecondaryButton class="gap-2 text-gray-700 !font-medium">
                    <CalendarIcon class="h-5 w-5 text-gray-500" />
                    Last 30 Days
                </SecondaryButton>
            </div>

            <!-- Loop through royalties array -->
            <div class="mt-8 flex-1 space-y-2">
                <div
                    v-for="royalty in generatingBatches"
                    :key="royalty.id"
                    class="bg-white border border-gray-200 py-4 px-5 flex gap-4 rounded-xl items-center justify-between"
                >
                    <div class="flex gap-4 items-center">
                        <DocumentTextIcon />
                        <div>
                            <p class="text-sm text-primary">{{ royalty.status }}</p>
                            <p class="text-gray-900">{{ royalty.title }}</p>
                        </div>
                    </div>
                    <Spinner3Icon class="h-[32px] w-[32px]" />
                </div>
            </div>

            <DataTable ref="dataTable" />

            <GenerateRoyaltyModal
                :open="generateModal"
                @close="generateModal = false"
                @save="openConfirmationModal"
            />

            <ConfirmationModal
                :action="confirmationModal.action"
                :action_label="confirmationModal.action_label"
                :cancel_label="confirmationModal.cancel_label"
                :data="confirmationModal.data"
                :header="confirmationModal.header"
                :icon="confirmationModal.icon"
                :message="confirmationModal.message"
                :open="confirmationModal.open"
                @close="confirmationModal.open = false"
            />
        </div>
    </Layout>
</template>
