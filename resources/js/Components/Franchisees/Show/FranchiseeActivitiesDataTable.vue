<script setup>
import { ref, computed } from 'vue';
import Avatar from '@/Components/Common/Avatar/Avatar.vue';
import CheckCircleIcon from '@/Components/Icon/CheckCircleIcon.vue';
import DataTableHeader from '@/Components/Shared/DataTableHeader.vue';
import ActivitiesPagination from '@/Components/Shared/ActivitiesPagination.vue';

const props = defineProps({
    activities: { type: Array, default: () => [] },
    recent: { type: Boolean, default: false },
});

const headers = [
    { key: '', label: '' },
    { key: 'activity', label: 'Activity', sortable: true },
    { key: 'User', label: 'User', sortable: true },
    { key: 'date_time', label: 'Date & Time', sortable: true },
];

const currentPage = ref(1);
const perPage = ref(10);

const paginatedActivities = computed(() => {
    if (props.recent) {
        return props.activities.slice(0, 5);
    }
    const start = (currentPage.value - 1) * perPage.value;
    const end = start + perPage.value;
    return props.activities.slice(start, end);
});

// Prepare pagination data for the ActivitiesPagination component.
const paginationData = computed(() => {
    return {
        total: props.activities.length,
        current_page: props.recent ? 1 : currentPage.value,
        total_pages: props.recent ? 1 : Math.ceil(props.activities.length / perPage.value),
        message: 'No data found',
    };
});

// Pagination info data
const paginationInfo = computed(() => {
    const total = props.activities.length;
    if (props.recent) {
        return {
            page: 'page 1 of 1',
            showing: ` (1 to ${total}) of ${total} results`,
        };
    }
    const totalPages = Math.ceil(total / perPage.value) || 1;
    const current = currentPage.value;
    const first = total > 0 ? (current - 1) * perPage.value + 1 : 0;
    const last = Math.min(current * perPage.value, total);
    return {
        page: `page ${current} of ${totalPages}`,
        showing: ` (${first} to ${last}) of ${total} results`,
    };
});

// Pagination functions
function handleFirstPage() {
    currentPage.value = 1;
}

function handlePreviousPage() {
    if (currentPage.value > 1) {
        currentPage.value--;
    }
}

function handleToPage(page) {
    const totalPages = Math.ceil(props.activities.length / perPage.value);
    if (page >= 1 && page <= totalPages) {
        currentPage.value = page;
    }
}

function handleNextPage() {
    const totalPages = Math.ceil(props.activities.length / perPage.value);
    if (currentPage.value < totalPages) {
        currentPage.value++;
    }
}

function handleLastPage() {
    currentPage.value = Math.ceil(props.activities.length / perPage.value);
}
</script>

<template>
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle">
                    <div class="overflow-hidden border-[#E0E0E0] border rounded-2xl">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-gray-100">
                                <tr class="relative">
                                    <DataTableHeader
                                        v-for="(header, index) in headers"
                                        :key="index"
                                        :data="header.key"
                                        :header="header.label"
                                        :sortable="header.sortable"
                                    />
                                </tr>
                            </thead>
                            <tbody
                                v-if="paginatedActivities && paginatedActivities.length > 0"
                                class="divide-y divide-gray-200 bg-white"
                            >
                                <tr v-for="activity in paginatedActivities" :key="activity.id">
                                    <td
                                        class="w-[56px] pl-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                                    >
                                        <div
                                            class="flex justify-center items-center w-10 h-10 rounded-md bg-gray-100"
                                        >
                                            <CheckCircleIcon class="text-[#505673]" />
                                        </div>
                                    </td>
                                    <td
                                        class="flex-1 whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6"
                                    >
                                        <p class="text-sm font-medium">{{ activity.title }}</p>
                                        <p class="text-sm text-gray-500">
                                            {{ activity.description }}
                                        </p>
                                    </td>
                                    <td class="py-4 px-3 whitespace-nowrap">
                                        <div class="flex items-center gap-4">
                                            <div class="flex items-center gap-2">
                                                <Avatar
                                                    :key="activity.user.id"
                                                    :image-url="activity.user.profile_photo_url"
                                                    :user="activity.user"
                                                />
                                            </div>
                                            <div>
                                                <p
                                                    class="text-sm font-medium text-gray-900 cursor-pointer"
                                                >
                                                    {{ activity.user.name }}
                                                </p>
                                                <p class="text-sm text-gray-500">
                                                    {{ activity.user.admin_type }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-3 whitespace-nowrap text-sm text-gray-500">
                                        <div>
                                            <p class="text-gray-900">{{ activity.date }}</p>
                                            <p>{{ activity.time }}</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tbody v-else>
                                <tr>
                                    <td
                                        class="px-6 py-24 whitespace-nowrap text-sm font-medium text-gray-900 text-center"
                                        :colspan="headers.length"
                                    >
                                        No data found —
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Activities Pagination Component -->
                    <ActivitiesPagination
                        v-if="!recent"
                        :info="paginationInfo"
                        :pagination="paginationData"
                        @first-page="handleFirstPage"
                        @previous-page="handlePreviousPage"
                        @to-page="handleToPage"
                        @next-page="handleNextPage"
                        @last-page="handleLastPage"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
