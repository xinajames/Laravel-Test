<script setup>
import { Head } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

import ConfirmationModal from '@/Components/Modal/ConfirmationModal.vue';
import DataTable from '@/Pages/Admin/Documents/DataTable.vue';
import FilterBadge from '@/Components/Common/Badge/FilterBadge.vue';
import FilterIcon from '@/Components/Icon/FilterIcon.vue';
import FilterDocumentModal from '@/Components/Modal/FilterDocumentModal.vue';
import Layout from '@/Layouts/Admin/Layout.vue';
import SearchInput from '@/Components/Common/Input/SearchInput.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';

const datatable = ref(null);

const search = ref(null);

const filter = ref(null);
const activeFilters = ref([]);
const filterOpen = ref(false);

const confirmationModal = reactive({
    open: false,
    header: 'Delete Document',
    message: 'Are you sure you want to delete this document? This action cannot be undone.',
    icon: 'delete',
    action_label: 'Delete',
    action: null,
    data: null,
});

function handleSearch(text) {
    datatable.value.search(text);
}

function handleSuccess() {
    confirmationModal.open = false;
}

function applyFilters() {
    filterOpen.value = false;
    if (datatable.value) {
        activeFilters.value = filter.value.activeFilters;
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
        activeFilters.value = filter.value.activeFilters;
        datatable.value.removeFilter(index);
    }

    switch (column) {
        case 'documents.name':
            filter.value.form.name = null;
            break;
        case 'documents.file_type':
            filter.value.form.file_type = null;
            break;
        case 'documents.created_by':
            filter.value.form.created_by = null;
            break;
        case 'documents.created_at':
            filter.value.form.created_at = null;
            break;
    }
}

function clearFilters() {
    filter.value.activeFilters = [];
    datatable.value.paginate(1);
}
</script>

<template>
    <Head title="Documents" />

    <Layout :showTopBar="false">
        <div>
            <div class="sm:flex sm:items-center mt-8 mb-6">
                <div class="sm:flex-auto">
                    <h1 class="text-3xl font-bold text-gray-900">Documents</h1>
                </div>
            </div>

            <div class="mt-4 flex items-center gap-2">
                <SearchInput
                    v-model="search"
                    class="flex-1 !rounded-md"
                    placeholder="Search"
                    @update:modelValue="handleSearch($event)"
                />
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

            <DataTable ref="datatable" />

            <ConfirmationModal
                :action="confirmationModal.action"
                :action_label="confirmationModal.action_label"
                :data="confirmationModal.data"
                :header="confirmationModal.header"
                :icon="confirmationModal.icon"
                :message="confirmationModal.message"
                :open="confirmationModal.open"
                method="visit"
                @close="confirmationModal.open = false"
                @success="handleSuccess"
            />

            <FilterDocumentModal
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
