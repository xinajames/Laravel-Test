<script setup>
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

import DataTable from '@/Components/Stores/Show/StoreDocumentsDataTable.vue';
import FilterBadge from '@/Components/Common/Badge/FilterBadge.vue';
import FilterDocumentModal from '@/Components/Modal/FilterDocumentModal.vue';
import FilterIcon from '@/Components/Icon/FilterIcon.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SearchInput from '@/Components/Common/Input/SearchInput.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import UploadIcon from '@/Components/Icon/UploadIcon.vue';
import UploadDocumentModal from '@/Components/Modal/UploadDocumentModal.vue';

const props = defineProps({
    store: Object,
});

const datatable = ref(null);

const search = ref(null);

const filter = ref(null);
const activeFilters = ref([]);
const filterOpen = ref(false);

const uploadOpen = ref(false);

function handleSearch(text) {
    datatable.value.search(text);
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

const canUpdateStores = computed(() => {
    return usePage().props.auth.permissions.includes('update-stores');
});
</script>

<template>
    <div class="p-8">
        <div class="flex gap-5 items-center">
            <SearchInput
                v-model="search"
                class="flex-1"
                custom-class="!rounded-md !py-2"
                placeholder="Search"
                size="large"
                @update:modelValue="handleSearch($event)"
            />

            <div class="flex items center gap-2">
                <PrimaryButton
                    v-if="canUpdateStores"
                    class="font-semibold text-sm"
                    @click="uploadOpen = true"
                >
                    <UploadIcon class="size-5 flex-shrink-0" />
                    Upload
                </PrimaryButton>
                <SecondaryButton class="!font-medium !text-gray-700" @click="filterOpen = true">
                    <FilterIcon class="text-gray-500" />
                    Filter
                </SecondaryButton>
            </div>
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

        <DataTable ref="datatable" :store-id="store.id" />
    </div>

    <UploadDocumentModal
        :id="store.id"
        :model="'store'"
        :open="uploadOpen"
        @close="uploadOpen = false"
        @success="datatable.paginate(1)"
    />

    <FilterDocumentModal
        ref="filter"
        :active-filters="activeFilters"
        :open="filterOpen"
        @applyFilters="applyFilters"
        @close="filterOpen = false"
        @reset-filters="datatable.resetFilters()"
    />
</template>

<style scoped></style>
