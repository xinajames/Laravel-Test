<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import ConfirmationModal from '@/Components/Modal/ConfirmationModal.vue';
import DataTable from '@/Pages/Admin/Franchisees/DataTable.vue';
import DocumentAddIcon from '@/Components/Icon/DocumentAddIcon.vue';
import FilterBadge from '@/Components/Common/Badge/FilterBadge.vue';
import FilterIcon from '@/Components/Icon/FilterIcon.vue';
import FilterFranchiseeModal from '@/Components/Modal/FilterFranchiseeModal.vue';
import Layout from '@/Layouts/Admin/Layout.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SearchInput from '@/Components/Common/Input/SearchInput.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';

const props = defineProps({
    hasPendingApplication: Boolean,
});

const datatable = ref(null);

const headers = reactive([
    { name: 'Franchisee', data: 'first_name', show: true, sortable: true },
    { name: 'Corporation Name', data: 'corporation_name', show: true, sortable: true },
    { name: 'No. of Stores', data: 'stores_count', show: true, sortable: true },
    { name: 'Address', data: 'residential_address_street', show: true, sortable: true },
    { name: 'FMC - Region', data: 'fm_region', show: true, sortable: true },
    { name: 'Email', data: 'email', show: true, sortable: true },
    { name: 'Mobile', data: 'mobile', show: true, sortable: true },
    { name: 'Status', data: 'status', show: true, sortable: true },
    { name: 'Last Modified At', data: 'updated_at', show: true, sortable: true },
    { name: 'Created At', data: 'created_at', show: true, sortable: true },
]);

const search = ref(null);

const filter = ref(null);
const filterOpen = ref(false);

function handleSearch(text) {
    datatable.value.search(text);
}

function handleCreate(start) {
    router.visit(route('franchisees.apply', start ? { start: true } : null));
}

const confirmationModal = reactive({
    open: false,
    header: 'Franchisee Application In Progress',
    message:
        'You have an existing franchisee application in progress. Would you like to continue where you left off or start a new one? Starting over will permanently erase your previously entered information.',
    icon: 'document',
    action_label: 'Continue Application',
    action: route('franchisees.apply'),
    data: null,
    cancel_label: 'Start Over',
    cancel_action: true,
});

function continueApplication() {
    confirmationModal.open = true;
}

function handleSuccess() {
    confirmationModal.open = false;
}

function applyFilters() {
    filterOpen.value = false;
    // Call datatable component applyFilters function here
    if (datatable.value) {
        datatable.value.applyFilters(filter.value.activeFilters);
    }
}

const getFilterText = (filter) => {
    if (!filter) return '';

    return `${filter.label}`;
};

function removeFilter(index, column, operator) {
    filter.value.activeFilters.splice(index, 1);
    if (datatable.value) {
        datatable.value.removeFilter(index);
    }

    switch (column) {
        case 'franchisees.corporation_name':
            filter.value.form.corporation = null;
            break;
        case 'franchisees.status':
            filter.value.form.status = null;
            break;
        case 'franchisees.created_at':
            operator === '>='
                ? (filter.value.form.date_created_from = null)
                : (filter.value.form.date_created_till = null);
            break;
    }
}

function clearFilters() {
    filter.value.activeFilters = [];
    datatable.value.paginate(1);
}

const canUpdateFranchisees = computed(() => {
    return usePage().props.auth.permissions.includes('update-franchisees');
});
</script>

<template>
    <Head title="Franchisees" />

    <Layout :showTopBar="false">
        <div>
            <div class="sm:flex sm:items-center mt-8 mb-6">
                <div class="sm:flex-auto">
                    <h1 class="text-3xl font-bold text-gray-900">Franchisees</h1>
                </div>
            </div>

            <div class="mt-4 flex items-center gap-2">
                <SearchInput
                    v-model="search"
                    class="flex-1 !rounded-md"
                    placeholder="Search"
                    @update:modelValue="handleSearch($event)"
                />

                <div v-if="hasPendingApplication">
                    <PrimaryButton class="font-semibold text-sm" @click="continueApplication()">
                        <DocumentAddIcon />
                        Continue Application
                    </PrimaryButton>
                </div>

                <div v-else>
                    <PrimaryButton
                        v-if="canUpdateFranchisees"
                        class="font-semibold text-sm"
                        @click="handleCreate(null)"
                    >
                        <DocumentAddIcon />
                        New Application
                    </PrimaryButton>
                </div>

                <SecondaryButton
                    class="gap-2 text-gray-700 !font-medium"
                    @click="filterOpen = true"
                >
                    <FilterIcon class="text-gray-500" />
                    Filter
                </SecondaryButton>
            </div>

            <!-- Active Filters -->
            <div v-if="filter && filter.activeFilters" class="flex flex-wrap items-center gap-2">
                <FilterBadge
                    :activeFilters="filter?.activeFilters"
                    :getFilterText="getFilterText"
                    @clearFilters="filter.handleReset()"
                    @removeFilter="removeFilter"
                />
            </div>

            <DataTable ref="datatable" :headers="headers" />

            <ConfirmationModal
                :action="confirmationModal.action"
                :action_label="confirmationModal.action_label"
                :cancel_action="confirmationModal.cancel_action"
                :cancel_label="confirmationModal.cancel_label"
                :data="confirmationModal.data"
                :header="confirmationModal.header"
                :icon="confirmationModal.icon"
                :message="confirmationModal.message"
                method="visit"
                :open="confirmationModal.open"
                @cancel-action="handleCreate(true)"
                @close="confirmationModal.open = false"
                @success="handleSuccess"
            />
            <FilterFranchiseeModal
                ref="filter"
                :open="filterOpen"
                @close="filterOpen = false"
                @applyFilters="applyFilters"
                @reset-filters="datatable.resetFilters()"
            />
        </div>
    </Layout>
</template>
