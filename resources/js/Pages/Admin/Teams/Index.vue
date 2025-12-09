<script setup>
import Layout from '@/Layouts/Admin/Layout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import SearchInput from '@/Components/Common/Input/SearchInput.vue';
import UserAddIcon from '@/Components/Icon/UserAddIcon.vue';
import FilterIcon from '@/Components/Icon/FilterIcon.vue';
import FilterBadge from '@/Components/Common/Badge/FilterBadge.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import DataTable from '@/Pages/Admin/Teams/DataTable.vue';
import InviteMemberModal from '@/Components/Modal/InviteMemberModal.vue';
import FilterRoleModal from '@/Components/Modal/FilterRoleModal.vue';

const datatable = ref(null);

const headers = reactive([
    { name: 'Team Member', data: 'name', show: true, sortable: true },
    { name: 'Role', data: 'type', show: true, sortable: true },
    { name: 'Status', data: 'status', show: true, sortable: false },
    { name: 'Last Modified At', data: 'updated_at', show: true, sortable: true },
    { name: 'Created At', data: 'created_at', show: true, sortable: true },
]);

const search = ref(null);

const filter = ref(null);
const activeFilters = ref([]);
const filterOpen = ref(false);
const inviteModal = ref(false);

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

function handleSuccess() {
    datatable.value.paginate(1);
}

const canUpdateTeam = computed(() => {
    return usePage().props.auth.permissions.includes('update-team');
});
</script>

<template>
    <Head title="Teams" />

    <Layout :showTopBar="false">
        <div>
            <div class="sm:flex sm:items-center mt-8 mb-6">
                <div class="sm:flex-auto">
                    <h1 class="text-3xl font-bold text-gray-900">Teams</h1>
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
                    v-if="canUpdateTeam"
                    class="font-semibold text-sm"
                    @click="inviteModal = true"
                >
                    <UserAddIcon class="h-5 w-5" />
                    Invite Member
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

            <DataTable ref="datatable" :headers="headers" />

            <InviteMemberModal
                :open="inviteModal"
                @close="inviteModal = false"
                @success="handleSuccess"
            />

            <FilterRoleModal
                ref="filter"
                :active-filters="activeFilters"
                :open="filterOpen"
                @close="filterOpen = false"
                @applyFilters="applyFilters"
                @reset-filters="datatable.resetFilters()"
            />
        </div>
    </Layout>
</template>
