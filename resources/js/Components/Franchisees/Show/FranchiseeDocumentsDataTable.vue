<script setup>
import { usePagination } from '@/Composables/Pagination.js';
import { computed, onMounted, reactive, ref } from 'vue';
import { MenuItem, MenuItems } from '@headlessui/vue';

import DataTableService from '@/Services/DataTableService.js';
import DataTableItem from '@/Components/Shared/DataTableItem.vue';
import DataTablePagination from '@/Components/Shared/DataTablePagination.vue';
import DotsVertical from '@/Components/Icon/DotsVertical.vue';
import Dropdown from '@/Components/Shared/Dropdown.vue';
import EyeIcon from '@/Components/Icon/EyeIcon.vue';
import TrashIcon from '@/Components/Icon/TrashIcon.vue';
import DownloadIcon from '@/Components/Icon/DownloadIcon.vue';
import ConfirmationModal from '@/Components/Modal/ConfirmationModal.vue';
import DocumentTextIcon from '@/Components/Icon/DocumentTextIcon.vue';
import EmptyDocumentIcon from '@/Components/Icon/EmptyDocumentIcon.vue';
import UploadIcon from '@/Components/Icon/UploadIcon.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import UploadDocumentModal from '@/Components/Modal/UploadDocumentModal.vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    franchiseeId: { type: Number, required: true },
    perPage: { type: Number, default: 10 },
    placeholder: { type: String, default: 'No documents available.' },
});

const pageInformation = usePagination().pageInformation;

onMounted(() => {
    paginate(1);
});

const uploadOpen = ref(false);

const confirmationModal = reactive({
    open: false,
    header: 'Delete Document',
    message: 'Are you sure you want to delete this document? This action cannot be undone.',
    icon: 'delete',
    action_label: 'Delete',
    action: null,
});

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
    route: route('documents.dataTable', {
        model: 'franchisee',
        id: props.franchiseeId,
    }),
});

const sortData = reactive({
    column: 'updated_at',
    order: 'desc',
});

function handleAction(type, id) {
    if (type === 'delete') {
        confirmationModal.header = 'Delete Document';
        confirmationModal.message =
            'Are you sure you want to delete this document? This action cannot be undone.';
        confirmationModal.icon = 'delete';
        confirmationModal.action_label = 'Delete';
        confirmationModal.action = route('documents.delete', id);
        confirmationModal.open = true;
    } else if (type === 'download') {
        console.log('Download document', id);
    } else if (type === 'view') {
        console.log('View document', id);
    }
}

const canUpdateFranchisees = computed(() => {
    return usePage().props.auth.permissions.includes('update-franchisees');
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

function showColumn(column) {
    return props.headers.some((header) => header.name === column && header.show);
}

function search(text) {
    datatable.settings.search = text;
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

function handleDownload(url) {
    window.location.replace(url);
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
                    <tbody
                        v-if="datatable.state.processing"
                        class="bg-white divide-y divide-gray-200"
                    >
                        <tr>
                            <td class="px-6 py-20 text-center">
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
                            <td class="w-[56px] pl-4 py-4 whitespace-nowrap">
                                <div class="flex justify-center items-center w-10 h-10 rounded-md">
                                    <DocumentTextIcon class="text-gray-600" />
                                </div>
                            </td>
                            <DataTableItem>
                                <div class="text-sm font-medium text-gray-900">
                                    {{ row.document_name.replace(/\.[^/.]+$/, '') }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ row.formatted_file_type }} • {{ row.formatted_file_size }}
                                </div>
                            </DataTableItem>
                            <DataTableItem class="text-sm text-right">
                                <div>Created on {{ row.formatted_created_at }}</div>
                                <div>by {{ row.created_by_name }}</div>
                            </DataTableItem>
                            <DataTableItem class="sticky right-1 bg-white w-14">
                                <Dropdown>
                                    <template v-slot:trigger>
                                        <div
                                            class="bg-lightest-gray rounded-full p-1 flex justify-center items-center"
                                        >
                                            <DotsVertical class="w-5 h-5 text-gray-400" />
                                        </div>
                                    </template>
                                    <template v-slot:menu>
                                        <div
                                            class="max-h-60 overflow-y-auto min-w-[200px] w-full bg-white rounded-lg"
                                        >
                                            <MenuItems class="flex flex-col w-full">
                                                <MenuItem class="w-full">
                                                    <a
                                                        :href="row.preview"
                                                        class="w-full px-5 py-3 text-gray-700 text-sm font-medium text-left flex items-center gap-4 whitespace-nowrap"
                                                        target="_blank"
                                                    >
                                                        <EyeIcon class="h-5 w-5 text-gray-600" />
                                                        View
                                                    </a>
                                                </MenuItem>
                                                <MenuItem
                                                    v-if="canUpdateFranchisees"
                                                    class="w-full"
                                                >
                                                    <button
                                                        class="w-full px-5 py-3 text-gray-700 text-sm font-medium text-left flex items-center gap-4 whitespace-nowrap"
                                                        @click="handleAction('delete', row.id)"
                                                    >
                                                        <TrashIcon class="h-5 w-5 text-gray-600" />
                                                        Delete
                                                    </button>
                                                </MenuItem>
                                                <MenuItem class="w-full">
                                                    <button
                                                        class="w-full px-5 py-3 text-gray-700 text-sm font-medium text-left flex items-center gap-4 whitespace-nowrap"
                                                        @click="handleDownload(row.download_url)"
                                                    >
                                                        <DownloadIcon
                                                            class="h-5 w-5 text-gray-600"
                                                        />
                                                        Download
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
                            <td class="py-10 text-center">
                                <div class="flex flex-col justify-center items-center p-6 gap-6">
                                    <EmptyDocumentIcon />
                                    <div class="text-center">
                                        <h5 class="font-semibold text-base">
                                            No documents uploaded yet
                                        </h5>
                                        <p class="text-gray-600 text-sm mt">
                                            Start by uploading the required franchisee documents.
                                        </p>
                                    </div>
                                    <PrimaryButton
                                        class="!bg-primary !font-medium"
                                        @click="uploadOpen = true"
                                    >
                                        <UploadIcon class="size-5" />
                                        Upload Documents
                                    </PrimaryButton>
                                </div>
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

    <UploadDocumentModal
        :open="uploadOpen"
        :id="franchiseeId"
        :model="'franchisee'"
        @close="uploadOpen = false"
        @success="paginate(1)"
    />
</template>

<style scoped></style>
