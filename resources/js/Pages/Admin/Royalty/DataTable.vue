<script setup>
import { onMounted, reactive, ref } from 'vue';
import DataTableHeader from '@/Components/Shared/DataTableHeader.vue';
import DataTableItem from '@/Components/Shared/DataTableItem.vue';
import ExternalLinkIcon from '@/Components/Icon/ExternalLinkIcon.vue';
import ChevronRightIcon from '@/Components/Icon/ChevronRightIcon.vue';
import RoyaltyViewDocument from '@/Components/Modal/RoyaltyViewDocument.vue';
import DocumentTextIcon from '@/Components/Icon/DocumentTextIcon.vue';
import XCircleIcon from '@/Components/Icon/XCircleIcon.vue';
import DataTableService from '@/Services/DataTableService';
import DataTablePagination from '@/Components/Shared/DataTablePagination.vue';
import { usePagination } from '@/Composables/Pagination.js';

const props = defineProps({
    headers: Array,
    perPage: { type: Number, default: 10 },
    placeholder: { type: String, default: 'No data available.' },
});

const pageInformation = usePagination().pageInformation;

onMounted(() => {
    paginate(1);
});

const headers = ref([
    { name: 'GENERATED', data: 'generated', show: true, sortable: false },
    { name: 'GENERATED ON', data: 'generated_on', show: true, sortable: true },
    { name: 'MNSR', data: 'mnsr', show: true, sortable: false },
    { name: 'ROYALTY WB', data: 'royalty_wb', show: true, sortable: false },
    { name: '', data: 'status', show: true, sortable: false },
]);

const datatable = reactive({
    settings: {
        filters: { data: [] },
        search: '',
        orders: {
            data: [{ column: 'updated_at', value: 'desc' }],
        },
        perPage: props.perPage,
    },
    value: { data: [], pagination: {} },
    info: { page: '', showing: '' },
    state: { processing: false },
    route: route('royalty.dataTable'),
});

const sortData = reactive({
    column: 'updated_at',
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

const viewDocumentModal = ref(false);
const selectedRow = ref(null);

// Function to open modal and set selected row
const openModal = (row) => {
    selectedRow.value = row;
    viewDocumentModal.value = true;
};

function search(text) {
    datatable.settings.search = text;
    paginate(1);
}

const handleDownload = (url, filename = 'downloaded-file') => {
    if (!url) return;

    const encodedUrl = encodeURI(url);

    const link = document.createElement('a');
    link.href = encodedUrl;
    link.download = filename;
    link.rel = 'noopener';

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const handleInvalidationSuccess = () => {
    viewDocumentModal.value = false;
    selectedRow.value = null;
    paginate(datatable.value.pagination.current_page || 1);
};

defineExpose({
    datatable,
    paginate,
    search,
});
</script>

<template>
    <div class="mt-8 flow-root">
        <div class="overflow-x-auto rounded-lg border border-[#E5E7EB]">
            <div class="inline-block min-w-full align-middle">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead>
                        <tr class="bg-gray-100">
                            <data-table-header
                                v-for="(header, index) in headers"
                                :key="index"
                                :data="header.data"
                                :header="header.name"
                                :show="header.show"
                                :sortable="header.sortable"
                                class="bg-gray-100"
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
                            class="cursor-pointer"
                            @click="openModal(row)"
                        >
                            <DataTableItem>
                                <div class="flex items-center gap-4">
                                    <DocumentTextIcon />
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ row.title }}
                                        </div>
                                        <div class="text-sm text-gray-500">{{ row.remarks }}</div>
                                    </div>
                                </div>
                            </DataTableItem>
                            <DataTableItem>
                                <div class="text-sm text-gray-900">
                                    {{ row.formatted_completed_date }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ row.formatted_completed_time }}
                                </div>
                            </DataTableItem>
                            <DataTableItem>
                                <div class="flex gap-4 items-center">
                                    <div class="text-sm text-gray-500 font-medium">
                                        {{ row.mnsr_file_name }}
                                    </div>
                                    <button
                                        v-if="row.mnsr_file_name"
                                        @click.stop="
                                            handleDownload(
                                                route('royalty.download.output', row.mnsr_file_id),
                                                row.mnsr_file_name
                                            )
                                        "
                                    >
                                        <ExternalLinkIcon class="cursor-pointer text-gray-400" />
                                    </button>
                                </div>
                            </DataTableItem>
                            <DataTableItem>
                                <div class="flex gap-2 items-center">
                                    <div class="text-sm text-gray-500 font-medium">
                                        {{ row.royalty_file_name }}
                                    </div>
                                    <!-- TODO: add download when royalty file & data is ready -->
                                    <button
                                        v-if="row.mnsr_file_name"
                                        @click.stop="
                                            handleDownload(
                                                route(
                                                    'royalty.download.output',
                                                    row.royalty_file_id
                                                ),
                                                row.mnsr_file_name
                                            )
                                        "
                                    >
                                        <ExternalLinkIcon class="cursor-pointer text-gray-400" />
                                    </button>
                                </div>
                            </DataTableItem>
                            <DataTableItem>
                                <div class="flex items-center justify-center">
                                    <XCircleIcon
                                        v-if="row.status == 4"
                                        class="h-4 w-4 text-red-500 flex-shrink-0"
                                    />
                                </div>
                            </DataTableItem>
                            <DataTableItem>
                                <div class="cursor-pointer text-gray-400">
                                    <ChevronRightIcon />
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

        <DataTablePagination :info="datatable.info" :pagination="datatable.value.pagination" />

        <RoyaltyViewDocument
            :open="viewDocumentModal"
            :royalty="selectedRow"
            @close="viewDocumentModal = false"
            @success="handleInvalidationSuccess"
        />
    </div>
</template>
