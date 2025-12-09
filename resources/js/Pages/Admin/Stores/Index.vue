<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

import ConfirmationModal from '@/Components/Modal/ConfirmationModal.vue';
import DataTable from '@/Pages/Admin/Stores/DataTable.vue';
import FilterBadge from '@/Components/Common/Badge/FilterBadge.vue';
import FilterIcon from '@/Components/Icon/FilterIcon.vue';
import FilterStoreModal from '@/Components/Modal/FilterStoreModal.vue';
import Layout from '@/Layouts/Admin/Layout.vue';
import OngoingStoreRatingCard from '@/Components/StoreRatings/OngoingStoreRatingCard.vue';
import PlusIcon from '@/Components/Icon/PlusIcon.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SearchInput from '@/Components/Common/Input/SearchInput.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';

const datatable = ref(null);

const props = defineProps({
    hasPendingCreation: Boolean,
    ongoingStoreRatings: Array,
});

const headers = reactive([
    { name: 'Store Name', data: 'jbs_name', show: true, sortable: true },
    { name: 'Franchisee', data: 'franchisee_name', show: true, sortable: true },
    { name: 'Store Type', data: 'store_type', show: true, sortable: true },
    { name: 'Store Group', data: 'store_group', show: true, sortable: true },
    { name: 'Status', data: 'status', show: true, sortable: true },
    { name: 'Region', data: 'region', show: true, sortable: true },
    { name: 'Province', data: 'store_province', show: true, sortable: true },
    { name: 'City/ Municipality', data: 'store_city', show: true, sortable: true },
    { name: 'Last Modified At', data: 'updated_at', show: true, sortable: true },
    { name: 'Created At', data: 'created_at', show: true, sortable: true },
]);

const search = ref(null);

const filter = ref(null);
const activeFilters = ref([]);
const filterOpen = ref(false);

const confirmationModal = reactive({
    open: false,
    header: 'Store Creation In Progress',
    message:
        'You have an unfinished store creation in progress. Would you like to continue or start over? Starting over will permanently erase your previously entered information.',
    icon: 'document',
    action_label: 'Continue',
    action: route('stores.create'),
    data: null,
    cancel_label: 'Start Over',
    cancel_action: true,
});

function handleSearch(text) {
    datatable.value.search(text);
}

function handleCreate() {
    if (props.hasPendingCreation) {
        confirmationModal.open = true;
    } else {
        router.visit(route('stores.create'));
    }
}

function startOver() {
    router.visit(route('stores.create', { start: true }));
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
        case 'stores.jbs_name':
            filter.value.form.store = null;
            break;
        case 'stores.franchisee_id':
            filter.value.form.franchisee = null;
            break;
        case 'stores.store_type':
            filter.value.form.store_type = null;
            break;
        case 'stores.store_status':
            filter.value.form.status = null;
            break;
        case 'stores.region':
            filter.value.form.region = null;
            break;
    }
}

function clearFilters() {
    filter.value.activeFilters = [];
    datatable.value.paginate(1);
}

const canUpdateStores = computed(() => {
    return usePage().props.auth.permissions.includes('update-stores');
});
</script>

<template>
    <Head title="Stores" />

    <Layout :showTopBar="false">
        <div>
            <div class="sm:flex sm:items-center mt-8 mb-6">
                <div class="sm:flex-auto">
                    <h1 class="text-3xl font-bold text-gray-900">Stores</h1>
                </div>
            </div>

            <div class="mt-4 flex items-center gap-2">
                <SearchInput
                    v-model="search"
                    class="flex-1 !rounded-md"
                    placeholder="Search"
                    @update:modelValue="handleSearch($event)"
                />

                <PrimaryButton
                    v-if="canUpdateStores"
                    class="font-semibold text-sm"
                    @click="handleCreate"
                >
                    <PlusIcon class="h-5 w-5" />
                    Add Store
                </PrimaryButton>
                <SecondaryButton
                    class="!font-medium !text-gray-700 !ml-1 gap-2"
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

            <div v-if="ongoingStoreRatings && ongoingStoreRatings.length > 0">
                <ongoing-store-rating-card :store-ratings="ongoingStoreRatings" />
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
                :open="confirmationModal.open"
                method="visit"
                @close="confirmationModal.open = false"
                @success="handleSuccess"
                @cancel-action="startOver"
            />

            <FilterStoreModal
                ref="filter"
                :active-filters="activeFilters"
                :open="filterOpen"
                @applyFilters="applyFilters"
                @close="filterOpen = false"
                @reset-filters="datatable.resetFilters()"
            />
        </div>
    </Layout>
</template>
