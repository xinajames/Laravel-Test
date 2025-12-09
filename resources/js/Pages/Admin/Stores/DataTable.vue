<script setup>
import { usePagination } from '@/Composables/Pagination.js';
import { computed, onMounted, reactive } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { MenuItem, MenuItems } from '@headlessui/vue';
import DataTableService from '@/Services/DataTableService.js';
import DataTableHeader from '@/Components/Shared/DataTableHeader.vue';
import DataTableItem from '@/Components/Shared/DataTableItem.vue';
import DataTablePagination from '@/Components/Shared/DataTablePagination.vue';
import StatusBadge from '@/Components/Common/Badge/StatusBadge.vue';
import DotsVertical from '@/Components/Icon/DotsVertical.vue';
import Dropdown from '@/Components/Shared/Dropdown.vue';
import EyeIcon from '@/Components/Icon/EyeIcon.vue';
import PencilAltIcon from '@/Components/Icon/PencilAltIcon.vue';
import TrashIcon from '@/Components/Icon/TrashIcon.vue';
import BanIcon from '@/Components/Icon/BanIcon.vue';
import ConfirmationModal from '@/Components/Modal/ConfirmationModal.vue';
import RefreshIcon from '@/Components/Icon/RefreshIcon.vue';

const props = defineProps({
    headers: Array,
    perPage: { type: Number, default: 10 },
    placeholder: { type: String, default: 'No data available.' },
});

const pageInformation = usePagination().pageInformation;

onMounted(() => {
    paginate(1);
});

const datatable = reactive({
    settings: {
        filters: { data: [] },
        search: '',
        orders: {
            data: [{ column: 'stores.updated_at', value: 'desc' }],
        },
        perPage: props.perPage,
    },
    value: { data: [], pagination: {} },
    info: { page: '', showing: '' },
    state: { processing: false },
    route: route('stores.dataTable'),
});

const confirmationModal = reactive({
    open: false,
    header: 'Franchisee Application In Progress',
    message:
        'You have an existing franchisee application in progress. Would you like to continue where you left off or start a new one? Starting over will permanently erase your previously entered information.',
    icon: 'document',
    action_label: 'Start Over',
    action: null,
});

const sortData = reactive({
    column: 'updated_at',
    order: 'desc',
});

function handleAction(type, id) {
    if (type === 'delete') {
        confirmationModal.header = 'Delete Store';
        confirmationModal.message =
            'Are you sure you want to delete this store? This action cannot be undone.';
        confirmationModal.icon = 'delete';
        confirmationModal.action_label = 'Delete';
        confirmationModal.action = route('stores.delete', id);
    } else if (type === 'deactivate') {
        confirmationModal.header = 'Deactivate Store';
        confirmationModal.message = 'Are you sure you want to deactivate this store?';
        confirmationModal.icon = 'deactivate';
        confirmationModal.action_label = 'Deactivate';
        confirmationModal.action = route('stores.deactivate', id);
    } else if (type === 'reactivate') {
        confirmationModal.header = 'Reactivate Store';
        confirmationModal.message = 'Are you sure you want to reactivate this store?';
        confirmationModal.icon = 'reactivate';
        confirmationModal.action_label = 'Reactivate';
        confirmationModal.action = route('stores.activate', id);
    }
    confirmationModal.open = true;
}

function handleRedirect(type, id) {
    if (type === 'view') {
        router.visit(route('stores.show', id));
    }
    if (type === 'edit') {
        router.visit(route('stores.edit', id));
    }
}

function paginate(page = 1) {
    datatable.state.processing = true;
    DataTableService.paginate({
        route: datatable.route,
        perPage: datatable.settings.perPage,
        currentPage: page,
        search: datatable.settings.search,
        filters: datatable.settings.filters.data,
        orders: datatable.settings.orders.data,
    })
        .then(function (response) {
            datatable.value.data = response.data.data;
            datatable.value.pagination = {
                count: response.data.per_page,
                current_page: response.data.current_page,
                links: response.data.links,
                per_page: response.data.per_page,
                total: response.data.total,
                total_pages: response.data.last_page,
                message: '',
            };
            datatable.state.processing = false;
            pageInformation(datatable);
        })
        .catch(function (response) {
            datatable.state.processing = false;
        });
}

function showColumn(column) {
    return props.headers.some(function (header) {
        return header.name === column && header.show;
    });
}

function search(text) {
    // replaces whitespace characters with a comma
    datatable.settings.search = text.trim().replace(/\s+/g, ',');
    paginate(1);
}

function handleSort(column, order) {
    datatable.settings.orders.data = [{ column: column, value: order }];
    sortData.column = column;
    sortData.order = order;
    paginate(1);
}

function applyFilters(filters) {
    const existingFilters = new Set(
        datatable.settings.filters.data.map(
            (filterData) => `${filterData.column}-${filterData.operator}`
        )
    );
    filters.forEach((filter) => {
        const filterIdentifier = `${filter.column}-${filter.operator}`;
        if (!existingFilters.has(filterIdentifier)) {
            datatable.settings.filters.data.push(filter);
            existingFilters.add(filterIdentifier);
        }
    });

    paginate(1);
}

function removeFilter(index) {
    datatable.settings.filters.data.splice(index, 1);
    paginate(1);
}

function resetFilters() {
    datatable.settings.filters.data = [];
    paginate(1);
}

defineExpose({
    applyFilters,
    datatable,
    paginate,
    removeFilter,
    resetFilters,
    search,
});

const canUpdateStores = computed(() => {
    return usePage().props.auth.permissions.includes('update-stores');
});
</script>

<template>
    <div class="mt-4 flow-root">
        <div class="overflow-x-auto rounded-lg border border-[#E5E7EB]">
            <div class="inline-block min-w-full align-middle">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead>
                        <tr class="bg-gray-100">
                            <DataTableHeader
                                v-for="(header, index) in headers"
                                :key="index"
                                :data="header.data"
                                :header="header.name"
                                :show="header.show"
                                :sortData="sortData"
                                :sortable="true"
                                class="bg-gray-100"
                                @toggle-sort="handleSort"
                            />
                            <th class="sticky"></th>
                        </tr>
                    </thead>
                    <tbody
                        v-if="datatable.state.processing"
                        class="bg-white divide-y divide-gray-200"
                    >
                        <tr>
                            <td :colspan="headers.length + 1" class="px-6 py-20 text-center">
                                <div class="flex justify-center items-center">
                                    <svg
                                        class="animate-spin h-8 w-8 text-gray-500"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        xmlns="http://www.w3.org/2000/svg"
                                    >
                                        <circle
                                            class="opacity-25"
                                            cx="12"
                                            cy="12"
                                            r="10"
                                            stroke="currentColor"
                                            stroke-width="4"
                                        ></circle>
                                        <path
                                            class="opacity-75"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                            fill="currentColor"
                                        ></path>
                                    </svg>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tbody
                        v-else-if="datatable.value.data?.length > 0"
                        class="divide-y divide-gray-200 bg-white"
                    >
                        <tr
                            v-for="(row, rowIndex) in datatable.value.data"
                            :key="rowIndex"
                            class="items-start"
                        >
                            <DataTableItem v-if="showColumn('Store Name')">
                                <Link :href="route('stores.show', row.id)" class="underline">
                                    {{ row.jbs_name }}
                                </Link>
                                <div class="text-sm text-gray-500">
                                    {{ row.store_code }}
                                </div>
                            </DataTableItem>
                            <DataTableItem v-if="showColumn('Franchisee')">
                                <div>
                                    {{ row.franchisee_name }}
                                    <span v-if="row.corporation_name">
                                        - {{ row.corporation_name }}
                                    </span>
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ row.franchisee_code }}
                                </div>
                            </DataTableItem>
                            <DataTableItem v-if="showColumn('Store Type')">
                                <div>{{ row.store_type }}</div>
                            </DataTableItem>
                            <DataTableItem v-if="showColumn('Store Group')">
                                <div>{{ row.store_group }}</div>
                            </DataTableItem>
                            <DataTableItem v-if="showColumn('Status')">
                                <StatusBadge
                                    :type="row.store_status"
                                    category="storeStatus"
                                    class="!rounded-full [&_svg]:hidden"
                                >
                                    {{ row.store_status }}
                                </StatusBadge>
                            </DataTableItem>
                            <DataTableItem v-if="showColumn('Region')">
                                <div>{{ row.region }}</div>
                            </DataTableItem>
                            <DataTableItem v-if="showColumn('Province')">
                                <div>{{ row.store_province }}</div>
                            </DataTableItem>
                            <DataTableItem v-if="showColumn('City/ Municipality')">
                                <div>{{ row.store_city }}</div>
                            </DataTableItem>
                            <DataTableItem v-if="showColumn('Last Modified At')">
                                {{ row.formatted_updated_at }}
                            </DataTableItem>
                            <DataTableItem v-if="showColumn('Created At')">
                                {{ row.formatted_created_at }}
                            </DataTableItem>
                            <DataTableItem class="sticky -right-1 bg-white">
                                <Dropdown>
                                    <template v-slot:trigger>
                                        <div
                                            class="bg-lightest-gray rounded-full p-1 flex justify-center items-center"
                                        >
                                            <dots-vertical class="w-6 h-6 text-medium-gray" />
                                        </div>
                                    </template>
                                    <template v-slot:menu>
                                        <div
                                            ref="container"
                                            class="max-h-60 overflow-y-auto min-w-[200px] w-full bg-white rounded-lg"
                                        >
                                            <MenuItems class="flex flex-col w-full">
                                                <MenuItem class="w-full">
                                                    <button
                                                        class="w-full px-5 py-3 text-dark-gray-dark text-sm font-medium text-left flex items-center gap-4 whitespace-nowrap"
                                                        @click="handleRedirect('view', row.id)"
                                                    >
                                                        <EyeIcon class="h-5 w-5" />
                                                        View
                                                    </button>
                                                </MenuItem>
                                                <MenuItem v-if="canUpdateStores" class="w-full">
                                                    <button
                                                        class="w-full px-5 py-3 text-dark-gray-dark text-sm font-medium text-left flex items-center gap-4 whitespace-nowrap"
                                                        @click="handleRedirect('edit', row.id)"
                                                    >
                                                        <PencilAltIcon class="h-5 w-5" />
                                                        Edit
                                                    </button>
                                                </MenuItem>
                                                <MenuItem v-if="canUpdateStores" class="w-full">
                                                    <button
                                                        class="w-full px-5 py-3 text-dark-gray-dark text-sm font-medium text-left flex items-center gap-4 whitespace-nowrap"
                                                        @click="handleAction('delete', row.id)"
                                                    >
                                                        <TrashIcon class="h-5 w-5" />
                                                        Delete
                                                    </button>
                                                </MenuItem>
                                                <MenuItem v-if="canUpdateStores" class="w-full">
                                                    <button
                                                        class="w-full px-5 py-3 text-dark-gray-dark text-sm font-medium text-left flex items-center gap-4 whitespace-nowrap"
                                                        @click="
                                                            handleAction(
                                                                row.status === 1
                                                                    ? 'deactivate'
                                                                    : 'reactivate',
                                                                row.id
                                                            )
                                                        "
                                                    >
                                                        <BanIcon
                                                            v-if="row.status === 1"
                                                            class="h-5 w-5"
                                                        />
                                                        <RefreshIcon v-else class="h-5 w-5" />
                                                        {{
                                                            row.status === 1
                                                                ? 'Deactivate'
                                                                : 'Reactivate'
                                                        }}
                                                    </button>
                                                </MenuItem>
                                            </MenuItems>
                                        </div>
                                    </template>
                                </Dropdown>
                            </DataTableItem>
                        </tr>
                    </tbody>
                    <tbody v-else class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td
                                :colspan="headers.length"
                                class="px-6 py-4 text-center text-gray-500"
                            >
                                {{ placeholder }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <DataTablePagination :info="datatable.info" :pagination="datatable.value.pagination" />

    <ConfirmationModal
        :action="confirmationModal.action"
        :action_label="confirmationModal.action_label"
        :header="confirmationModal.header"
        :icon="confirmationModal.icon"
        :message="confirmationModal.message"
        :open="confirmationModal.open"
        @close="confirmationModal.open = false"
        @success="paginate(1)"
    />
</template>

<style scoped></style>
