<script setup>
import { reactive, ref } from 'vue';

import FilterIcon from '@/Components/Icon/FilterIcon.vue';
import SearchInput from '@/Components/Common/Input/SearchInput.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import ActivityDataTable from './ActivityDataTable.vue';
import FilterBadge from '@/Components/Common/Badge/FilterBadge.vue';
import FilterActivityModal from '@/Components/Modal/FilterActivityModal.vue';

const props = defineProps({
    store: {
        type: Object,
        default: () => {},
    },
});

const datatable = ref(null);

const headers = reactive([
    { name: 'Activity', data: 'description', show: true, sortable: true },
    { name: 'User', data: 'name', show: true, sortable: true },
    { name: 'Date & Time', data: 'created_at', show: true, sortable: true },
]);

const search = ref(null);

const filter = ref(null);

const activeFilters = ref([]);

const filterOpen = ref(false);

function handleSearch(text) {
    datatable.value.search(text);
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

const removeFilter = (index) => {
    filter.value.activeFilters.splice(index, 1);
    if (datatable.value) {
        datatable.value.removeFilter(index);
    }
};

function clearFilters() {
    filter.value.activeFilters = [];
    datatable.value.paginate(1);
}
</script>

<template>
    <div class="p-8 space-y-4">
        <div class="flex gap-4 items-center">
            <SearchInput
                v-model="search"
                class="flex-1"
                custom-class="!rounded-md !py-2"
                placeholder="Search"
                size="large"
                @update:modelValue="handleSearch($event)"
            />

            <SecondaryButton class="!font-medium !text-gray-700 !ml-1" @click="filterOpen = true">
                <FilterIcon class="text-gray-500" />
                Filter
            </SecondaryButton>
        </div>
        <div v-if="filter && filter.activeFilters" class="flex flex-wrap items-center gap-2">
            <FilterBadge
                :activeFilters="filter?.activeFilters"
                :getFilterText="getFilterText"
                @clearFilters="filter.handleReset()"
                @removeFilter="removeFilter"
            />
        </div>
        <ActivityDataTable ref="datatable" :headers="headers" :store="store" />

        <FilterActivityModal
            ref="filter"
            :open="filterOpen"
            @applyFilters="applyFilters"
            @close="filterOpen = false"
            @reset-filters="datatable.resetFilters()"
        />
    </div>
</template>

<style scoped></style>
