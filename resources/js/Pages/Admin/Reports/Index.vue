<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { DocumentIcon } from '@heroicons/vue/24/outline/index.js';

import FilterReportsModal from '@/Components/Modal/FilterReportsModal.vue';
import Layout from '@/Layouts/Admin/Layout.vue';

const props = defineProps({
    reportOptions: Array,
});

const report_option = ref(null);
const modalOpen = ref(false);

const generateReport = (report) => {
    report_option.value = report;
    modalOpen.value = true;
};

const handleFiltersApply = (filters) => {
    modalOpen.value = false;
};

const handleFiltersReset = () => {
    modalOpen.value = false;
};
</script>

<template>
    <Head title="Reports" />

    <Layout :showTopBar="false">
        <div class="sm:flex sm:items-center mt-8 mb-6">
            <div class="sm:flex-auto">
                <h1 class="text-3xl font-bold text-gray-900">Reports</h1>
            </div>
        </div>

        <div>
            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-gray-500 pb-2">
                    Click
                    <strong>Generate</strong>
                    to request a report. You’ll be notified once it’s ready or if an error occurs.
                </dt>
                <dd class="mt-2 text-sm text-gray-900">
                    <ul
                        class="border border-gray-200 rounded-md divide-y divide-gray-200 bg-white"
                        role="list"
                    >
                        <li
                            v-for="report in reportOptions"
                            :key="report.value"
                            class="p-4 flex items-center justify-between text-sm"
                        >
                            <div class="w-0 flex-1 flex items-center select-none">
                                <DocumentIcon class="h-6 w-6 text-gray-700" />
                                <span
                                    class="ml-2 flex-1 w-0 truncate cursor-pointer"
                                    @click="generateReport(report)"
                                >
                                    {{ report.label }}
                                </span>
                            </div>
                            <div class="ml-4 flex-shrink-0">
                                <button
                                    class="font-medium text-primary cursor-pointer"
                                    @click="generateReport(report)"
                                >
                                    Generate
                                </button>
                            </div>
                        </li>
                    </ul>
                </dd>
            </div>

            <FilterReportsModal
                :open="modalOpen"
                :report-option="report_option"
                @applyFilters="handleFiltersApply"
                @close="modalOpen = false"
                @resetFilters="handleFiltersReset"
            />
        </div>
    </Layout>
</template>
