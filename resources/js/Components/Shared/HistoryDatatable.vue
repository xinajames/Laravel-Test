<script setup>
import { reactive } from 'vue';
import DataTableHeader from '@/Components/Shared/DataTableHeader.vue';
import DataTableItem from '@/Components/Shared/DataTableItem.vue';
import Avatar from '@/Components/Common/Avatar/Avatar.vue';

const props = defineProps({
    header: String,
    sortable: { type: Boolean, default: false },
    sortData: { type: Object, default: () => ({}) },
    data: String,
    histories: Array,
});

const headers = reactive([
    { name: 'Activity', data: 'activity', show: true },
    { name: 'User', data: 'user', show: true },
    { name: 'Date & Time', data: 'date_time', show: true },
]);
</script>

<template>
    <div class="overflow-x-auto h-full w-full">
        <table class="w-full divide-y divide-gray-300 h-full" style="min-width: 600px;">
            <thead class="bg-gray-100">
                <tr>
                    <DataTableHeader
                        v-for="(header, index) in headers"
                        :key="index"
                        :data="header.data"
                        :header="header.name"
                        :show="header.show"
                        class="bg-gray-100"
                    />
                </tr>
            </thead>
            <tbody v-if="histories?.length > 0" class="divide-y divide-gray-200 bg-white">
                <tr v-for="(history, index) in props.histories" :key="index">
                    <DataTableItem>
                        <div class="flex-1">
                            <p class="font-medium text-sm">
                                {{ history.title }}
                            </p>
                            <p class="text-sm text-[#6B7280]">
                                {{ history.description }}
                            </p>
                        </div>
                    </DataTableItem>
                    <DataTableItem>
                        <div class="flex gap-4 items-center">
                            <Avatar
                                :image-url="history.user?.profile_photo"
                                image-class="h-10 w-10 object-cover rounded-full"
                            />
                            <div class="flex-1">
                                <p class="font-medium text-sm">
                                    {{ history.user?.name || 'System' }}
                                </p>
                                <p class="text-sm text-[#6B7280]">
                                    {{ history.user?.type || 'Automated' }}
                                </p>
                            </div>
                        </div>
                    </DataTableItem>
                    <DataTableItem>
                        <div class="flex-1 items-start">
                            <p class="text-sm text-[#111827]">
                                {{ history.date }}
                            </p>
                            <p class="text-sm text-[#6B7280]">
                                {{ history.time }}
                            </p>
                        </div>
                    </DataTableItem>
                </tr>
            </tbody>
            <tbody v-else class="h-full">
                <tr class="h-full">
                    <td colspan="3" class="text-center py-20 text-gray-400 h-full">
                        <div class="flex flex-col items-center justify-center space-y-2 min-h-[200px]">
                            <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-lg font-medium text-gray-500">No history found</p>
                            <p class="text-sm text-gray-400">There are no changes recorded for this field.</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
