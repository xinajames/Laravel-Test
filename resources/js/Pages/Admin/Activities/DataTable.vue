<script setup>
import { onMounted, reactive } from 'vue';
import { usePagination } from '@/Composables/Pagination.js';
import Avatar from '@/Components/Common/Avatar/Avatar.vue';
import DataTableHeader from '@/Components/Shared/DataTableHeader.vue';
import CheckCircleIcon from '@/Components/Icon/CheckCircleIcon.vue';
import DataTableService from '@/Services/DataTableService.js';
import DataTableItem from '@/Components/Shared/DataTableItem.vue';
import DataTablePagination from '@/Components/Shared/DataTablePagination.vue';

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
            data: [{ column: 'created_at', value: 'desc' }],
        },
        perPage: props.perPage,
    },
    value: { data: [], pagination: {} },
    info: { page: '', showing: '' },
    state: { processing: false },
    route: route('activities.dataTable'),
});

const sortData = reactive({
    column: 'created_at',
    order: 'desc',
});

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
        .catch(function () {
            datatable.state.processing = false;
        });
}

function search(text) {
    datatable.settings.search = text;
    paginate(1);
}

function applyFilters(filters) {
    filters.forEach((filter) => {
        const index = datatable.settings.filters.data.findIndex(
            (existingFilter) =>
                existingFilter.column === filter.column &&
                existingFilter.operator === filter.operator
        );
        if (index > -1) {
            datatable.settings.filters.data[index] = filter;
        } else {
            datatable.settings.filters.data.push(filter);
        }
    });
    paginate(1);
}

function handleSort(column, order) {
    datatable.settings.orders.data = [{ column: column, value: order }];
    sortData.column = column;
    sortData.order = order;
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
                                :sortable="header.sortable"
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
                            <DataTableItem>
                                <div class="flex items-center space-x-4">
                                    <div
                                        class="flex justify-center items-center w-10 h-10 rounded-md bg-gray-100"
                                    >
                                        <CheckCircleIcon class="text-[#505673]" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium">{{ row.title }}</p>
                                        <p class="text-sm text-gray-500">{{ row.description }}</p>
                                    </div>
                                </div>
                            </DataTableItem>
                            <DataTableItem>
                                <div class="flex items-center gap-4">
                                    <Avatar
                                        :key="row.id"
                                        :image-url="row.profile_photo_url"
                                        :user="row.user"
                                    />

                                    <div class="leading-tight">
                                        <p class="text-sm font-medium text-gray-900 cursor-pointer">
                                            {{ row.user_name }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            {{ row.causer_role }}
                                        </p>
                                    </div>
                                </div>
                            </DataTableItem>
                            <DataTableItem>
                                <div class="flex flex-col leading-tight">
                                    <p class="text-gray-900 font-medium">
                                        {{ row.formatted_date }}
                                    </p>
                                    <p>{{ row.formatted_time }}</p>
                                </div>
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
</template>
