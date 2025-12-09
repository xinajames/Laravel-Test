<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref } from 'vue';
import { MenuItem, MenuItems } from '@headlessui/vue';
import { usePagination } from '@/Composables/Pagination.js';
import DataTableHeader from '@/Components/Shared/DataTableHeader.vue';
import DataTableItem from '@/Components/Shared/DataTableItem.vue';
import StatusBadge from '@/Components/Common/Badge/StatusBadge.vue';
import DotsVertical from '@/Components/Icon/DotsVertical.vue';
import Dropdown from '@/Components/Shared/Dropdown.vue';
import EyeIcon from '@/Components/Icon/EyeIcon.vue';
import PencilAltIcon from '@/Components/Icon/PencilAltIcon.vue';
import TrashIcon from '@/Components/Icon/TrashIcon.vue';
import BanIcon from '@/Components/Icon/BanIcon.vue';
import Avatar from '@/Components/Common/Avatar/Avatar.vue';
import AdjustmentIcon from '@/Components/Icon/AdjustmentIcon.vue';
import RefreshIcon from '@/Components/Icon/RefreshIcon.vue';
import DataTableService from '@/Services/DataTableService.js';
import DataTablePagination from '@/Components/Shared/DataTablePagination.vue';
import ChangeRoleModal from '@/Components/Modal/ChangeRoleModal.vue';
import ConfirmationModal from '@/Components/Modal/ConfirmationModal.vue';

const props = defineProps({
    headers: Array,
    perPage: { type: Number, default: 10 },
    placeholder: { type: String, default: 'No data available.' },
});

const changeRoleModal = ref(false);
const currentRoleId = ref(null);
const userId = ref(null);

const confirmationModals = reactive({
    deactivate: {
        open: false,
        header: 'Deactivate Team Member',
        message: 'Are you sure you want to deactivate this team member?',
        icon: 'deactivate',
        action_label: 'Deactivate',
        action: '',
    },
    reactivate: {
        open: false,
        header: 'Reactivate Team Member',
        message:
            'This will restore the team member’s access to the application. Proceed with reactivation?',
        icon: 'reactivate',
        action_label: 'Reactivate',
        action: '',
    },
    delete: {
        open: false,
        header: 'Delete Team Member',
        message:
            'Are you sure you want to delete this team member? This will permanently remove the user and revoke their access to the application. This action cannot be undone.',
        icon: 'delete',
        action_label: 'Delete',
        action: '',
    },
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
            data: [{ column: 'updated_at', value: 'desc' }],
        },
        perPage: props.perPage,
    },
    value: { data: [], pagination: {} },
    info: { page: '', showing: '' },
    state: { processing: false },
    route: route('teams.dataTable'),
});

const sortData = reactive({
    column: 'updated_at',
    order: 'desc',
});

function handleAction(type, id, roleId) {
    switch (type) {
        case 'change_role':
            currentRoleId.value = roleId;
            userId.value = id;
            changeRoleModal.value = true;
            break;
        case 'deactivate':
            confirmationModals.deactivate.action = route('teams.deactivate', id);
            confirmationModals.deactivate.open = true;
            break;
        case 'reactivate':
            confirmationModals.reactivate.action = route('teams.activate', id);
            confirmationModals.reactivate.open = true;
            break;
        case 'delete':
            confirmationModals.delete.action = route('teams.delete', id);
            console.log(confirmationModals.delete);
            confirmationModals.delete.open = true;
            break;
        default:
            break;
    }
}

function handleSuccess(type) {
    confirmationModals[type].open = false;
    paginate(1);
}

function handleRedirect(type, id) {
    if (type === 'view') {
        router.visit(route('teams.show', id));
    }
    if (type === 'edit') {
        router.visit(route('teams.edit', id));
    }
}

const canUpdateTeam = computed(() => {
    return usePage().props.auth.permissions.includes('update-team');
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
    filters.forEach((filter) => {
        const index = datatable.settings.filters.data.findIndex(
            (existingFilter) =>
                existingFilter.column === filter.column &&
                existingFilter.operator === filter.operator
        );
        if (index > -1) {
            // Update the existing filter with the new one (e.g., updating its value)
            datatable.settings.filters.data[index] = filter;
        } else {
            // Add as a new filter if it doesn't exist
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
                            <data-table-header
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
                                <div class="flex gap-4 items-center">
                                    <div class="h-10 w-10">
                                        <Avatar
                                            :image-url="row.profile_photo_url"
                                            image-class="!size-10 object-cover rounded-full"
                                        />
                                    </div>
                                    <div class="flex-1">
                                        <Link :href="route('teams.show', row.id)" class="underline">
                                            {{ row.name }}
                                        </Link>
                                        <p class="text-sm text-[#6B7280]">
                                            {{ row.email }}
                                        </p>
                                    </div>
                                </div>
                            </DataTableItem>
                            <DataTableItem>
                                <div class="text-sm text-gray-500">
                                    {{ row.type }}
                                </div>
                            </DataTableItem>
                            <DataTableItem>
                                <StatusBadge
                                    :type="row.status_label"
                                    category="userStatus"
                                    class="!rounded-full [&_svg]:hidden"
                                >
                                    {{ row.status_label }}
                                </StatusBadge>
                            </DataTableItem>
                            <DataTableItem>
                                <div class="text-sm text-gray-500">
                                    {{ row.formatted_updated_at }}
                                </div>
                            </DataTableItem>
                            <DataTableItem>
                                <div class="text-sm text-gray-500">
                                    {{ row.formatted_created_at }}
                                </div>
                            </DataTableItem>
                            <DataTableItem class="sticky -right-1 bg-white w-[68px]">
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
                                                <MenuItem v-if="canUpdateTeam" class="w-full">
                                                    <button
                                                        class="w-full px-5 py-3 text-dark-gray-dark text-sm font-medium text-left flex items-center gap-4 whitespace-nowrap"
                                                        @click="handleRedirect('edit', row.id)"
                                                    >
                                                        <PencilAltIcon class="h-5 w-5" />
                                                        Edit
                                                    </button>
                                                </MenuItem>
                                                <MenuItem v-if="canUpdateTeam" class="w-full">
                                                    <button
                                                        class="w-full px-5 py-3 text-dark-gray-dark text-sm font-medium text-left flex items-center gap-4 whitespace-nowrap"
                                                        @click="
                                                            handleAction(
                                                                'change_role',
                                                                row.id,
                                                                row.user_role_id
                                                            )
                                                        "
                                                    >
                                                        <AdjustmentIcon class="h-5 w-5" />
                                                        Change Role
                                                    </button>
                                                </MenuItem>
                                                <MenuItem v-if="canUpdateTeam" class="w-full">
                                                    <button
                                                        @click="handleAction('delete', row.id)"
                                                        class="w-full px-5 py-3 text-dark-gray-dark text-sm font-medium text-left flex items-center gap-4 whitespace-nowrap"
                                                    >
                                                        <TrashIcon class="h-5 w-5" />
                                                        Delete
                                                    </button>
                                                </MenuItem>
                                                <MenuItem v-if="canUpdateTeam" class="w-full">
                                                    <button
                                                        @click="
                                                            handleAction(
                                                                row.status === 1
                                                                    ? 'deactivate'
                                                                    : 'reactivate',
                                                                row.id
                                                            )
                                                        "
                                                        class="w-full px-5 py-3 text-dark-gray-dark text-sm font-medium text-left flex items-center gap-4 whitespace-nowrap"
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
                </table>
            </div>
        </div>
    </div>

    <ChangeRoleModal
        :open="changeRoleModal"
        :role-id="currentRoleId"
        :user-id="userId"
        @success="paginate(1)"
        @close="changeRoleModal = false"
    />

    <ConfirmationModal
        v-for="(modal, key) in confirmationModals"
        :key="key"
        :action_label="modal.action_label"
        :action="modal.action"
        :header="modal.header"
        :icon="modal.icon"
        :message="modal.message"
        :open="modal.open"
        @close="modal.open = false"
        @success="handleSuccess(key)"
    />

    <DataTablePagination :info="datatable.info" :pagination="datatable.value.pagination" />
</template>
