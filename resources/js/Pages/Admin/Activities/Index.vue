<script setup>
import { Head } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import Layout from '@/Layouts/Admin/Layout.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import SearchInput from '@/Components/Common/Input/SearchInput.vue';
import FilterIcon from '@/Components/Icon/FilterIcon.vue';
import FilterBadge from '@/Components/Common/Badge/FilterBadge.vue';
import DataTable from '@/Pages/Admin/Activities/DataTable.vue';
import FilterActivityModal from '@/Components/Modal/FilterActivityModal.vue';

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
    <Head title="Activities" />

    <Layout :showTopBar="false">
        <div>
            <div class="sm:flex sm:items-center mt-8 mb-6">
                <div class="sm:flex-auto">
                    <h1 class="text-3xl font-bold text-gray-900">Activities</h1>
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

            <div v-if="filter && filter.activeFilters" class="flex flex-wrap items-center gap-2">
                <FilterBadge
                    :activeFilters="filter?.activeFilters"
                    :getFilterText="getFilterText"
                    @clearFilters="filter.handleReset()"
                    @removeFilter="removeFilter"
                />
            </div>

            <DataTable ref="datatable" :headers="headers" />
            <FilterActivityModal
                ref="filter"
                :open="filterOpen"
                @close="filterOpen = false"
                @applyFilters="applyFilters"
                @reset-filters="datatable.resetFilters()"
            />
        </div>
    </Layout>
</template>
