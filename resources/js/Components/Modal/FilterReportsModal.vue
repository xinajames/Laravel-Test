<script setup>
import { onMounted, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline/index.js';

import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import FilterModal from '@/Components/Shared/FilterModal.vue';
import SearchInputDropdown from '@/Components/Common/Select/SearchInputDropdown.vue';
import YearPicker from '@/Components/Common/DatePicker/YearPicker.vue';

const emits = defineEmits(['close', 'applyFilters', 'resetFilters']);

const props = defineProps({
    open: Boolean,
    reportOption: Object,
});

const form = useForm({
    region: null,
    store_group: null,
    sales_type: null,
    store_status: null,
    year: null,
    report_type: props.reportOption?.value,
});

const regions = ref([]);
const store_groups = ref([
    { label: 'All', value: 'All' },
    { label: 'Full Franchise', value: 'FullFranchise' },
    { label: 'Company Owned', value: 'CompanyOwned' },
]);
const sales_types = ref([]);
const store_statuses = ref([]);

const selectedRegion = ref(null);
const selectedStoreGroup = ref(null);
const selectedSalesType = ref(null);
const selectedStoreStatus = ref(null);
const selectedYear = ref(null);

watch(
    () => props.reportOption?.value,
    (newReportOption) => {
        form.report_type = newReportOption;
    }
);

watch(selectedYear, (newYear) => {
    form.year = newYear;
});

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            if (regions.value.length) {
                selectedRegion.value = regions.value[0];
                form.region = selectedRegion.value?.value;
            }

            if (store_groups.value.length) {
                selectedStoreGroup.value = store_groups.value[0];
                form.store_group = selectedStoreGroup.value?.value;
            }

            if (sales_types.value.length && props.reportOption?.value === 4) {
                selectedSalesType.value = sales_types.value[0];
                form.sales_type = selectedSalesType.value?.value;
            }

            if (store_statuses.value.length && props.reportOption?.value === 3) {
                selectedStoreStatus.value = store_statuses.value[0];
                form.store_status = selectedStoreStatus.value?.value;
            }

            selectedYear.value = null;
            form.year = null;
        }
    }
);

const handleUpdateRegion = (data) => {
    selectedRegion.value = data;
    form.region = data.value;
};

const handleUpdateStoreGroup = (data) => {
    selectedStoreGroup.value = data;
    form.store_group = data.value;
};

const handleUpdateSalesType = (data) => {
    selectedSalesType.value = data;
    form.sales_type = data.value;
};

const handleUpdateStoreStatus = (data) => {
    selectedStoreStatus.value = data;
    form.store_status = data.value;
};

function getRegionsData() {
    let url = route('enums.getDataList', { key: 'region-dropdown' });
    axios.get(url).then((response) => {
        regions.value = [{ label: 'All', value: 'All' }].concat(response.data);
    });
}

function getEnums(enum_key, target) {
    let url = route('enums.getDataList', { key: enum_key });
    axios.get(url).then((response) => {
        target.value = [{ label: 'All', value: 'All' }].concat(response.data);
    });
}

const applyFilters = () => {
    const filters = {
        region: selectedRegion?.value?.value,
        store_group: selectedStoreGroup?.value?.value,
        sales_type: selectedSalesType?.value?.value,
        store_status: selectedStoreStatus?.value?.value,
        report_type: form?.report_type,
    };

    // Add year for reports that require it (Renewals=3, JBS Sales Performance=4, Insurance=6, Contract of Lease=7)
    if (
        form?.report_type === 3 ||
        form?.report_type === 4 ||
        form?.report_type === 6 ||
        form?.report_type === 7
    ) {
        filters.year = selectedYear.value;
    }

    router.post(route('reports.generate'), filters, {
        onSuccess: () => {
            // On success, reset the form
            selectedRegion.value = null;
            selectedStoreGroup.value = null;
            selectedSalesType.value = null;
            selectedStoreStatus.value = null;

            form.region = null;
            form.store_group = null;
            form.sales_type = null;
            form.store_status = null;

            selectedYear.value = null;
            form.year = null;

            emits('applyFilters', filters);
        },
        onError: (errors) => {
            console.error('Error generating report:', errors);
        },
    });
};

const resetFilters = () => {
    selectedRegion.value = null;
    selectedStoreGroup.value = null;
    selectedSalesType.value = null;
    selectedStoreStatus.value = null;

    form.region = null;
    form.store_group = null;
    form.sales_type = null;
    form.store_status = null;

    const currentYear = new Date().getFullYear();
    selectedYear.value = currentYear;
    form.year = currentYear;

    emits('resetFilters');
};

const closeModal = () => {
    emits('close');
};

const cancel = () => {
    resetFilters();
    closeModal();
};

function getPreviewUrl(fileUrl) {
    const encodedUrl = encodeURIComponent(fileUrl);
    return `https://view.officeapps.live.com/op/view.aspx?src=${encodedUrl}`;
}

function handleDownload(url, filename = 'report.xlsx') {
    if (!url) return;

    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.rel = 'noopener';

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function handlePreview(url) {
    if (!url) return;
    window.open(getPreviewUrl(url), '_blank');
}

onMounted(() => {
    getRegionsData();
    // getEnums('store-group-enum', store_groups);
    getEnums('sales-type-enum', sales_types);
    getEnums('store-status-enum', store_statuses);

    selectedYear.value = null;
    form.year = null;
});
</script>

<template>
    <FilterModal
        :open="open"
        :title="`Generate: ${props.reportOption?.label || ''}`"
        @close="cancel"
    >
        <template #content>
            <div>
                <!-- Last failed attempt error -->
                <div
                    v-if="props.reportOption?.last_status === 4"
                    class="p-6 bg-red-50 border border-red-200 rounded"
                >
                    <p class="text-sm text-red-600 font-medium">
                        The last attempt to generate this report failed. Please re-check your
                        filters and try again. If the issue persists, contact your system
                        administrator for assistance.
                    </p>
                </div>

                <!-- Ongoing generation warning -->
                <div
                    v-if="props.reportOption && props.reportOption.allow_generate === false"
                    class="p-6 bg-yellow-50 border border-yellow-200 rounded"
                >
                    <p class="text-sm text-yellow-700 font-medium">
                        A report generation is currently in progress. Please wait until it completes
                        before requesting to generate a new one.
                    </p>
                </div>

                <!-- Last generated report -->
                <div
                    v-if="props.reportOption?.file_url"
                    class="p-6 bg-gray-50 border border-gray-200 rounded text-sm text-gray-700"
                >
                    <p class="font-medium mb-1">Last Generated Report:</p>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <a
                                href="#"
                                @click.prevent="handlePreview(props.reportOption.file_url)"
                                class="text-primary hover:underline"
                            >
                                {{ props.reportOption.file_name || 'View Report' }}
                            </a>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                @click="
                                    handleDownload(
                                        props.reportOption.file_url,
                                        props.reportOption.file_name
                                    )
                                "
                            >
                                <ArrowDownTrayIcon class="w-5 h-5 text-primary cursor-pointer" />
                            </button>
                        </div>
                    </div>

                    <p class="text-xs text-gray-500 mt-1">
                        Generated on: {{ props.reportOption.created_at }}
                    </p>
                </div>

                <!-- Filters -->
                <div class="border-t border-light-gray p-6 space-y-4">
                    <!-- Region -->
                    <SearchInputDropdown
                        :dataList="regions"
                        :modelValue="selectedRegion?.label || ''"
                        label="Region"
                        @update-data="handleUpdateRegion"
                    />

                    <!-- Store Group (Ganvio, Insurance Report, Contract of Lease, JBS Sales Performance) -->
                    <SearchInputDropdown
                        v-if="
                            props.reportOption?.value === 1 ||
                            props.reportOption?.value === 2 ||
                            props.reportOption?.value === 6 ||
                            props.reportOption?.value === 7 ||
                            props.reportOption?.value === 4
                        "
                        :dataList="store_groups"
                        :modelValue="selectedStoreGroup?.label || ''"
                        label="Store Group"
                        @update-data="handleUpdateStoreGroup"
                    />

                    <SearchInputDropdown
                        v-if="props.reportOption?.value === 4"
                        :dataList="sales_types"
                        :modelValue="selectedSalesType?.label || ''"
                        label="Sales Type"
                        @update-data="handleUpdateSalesType"
                    />

                    <!-- Store Status -->
                    <SearchInputDropdown
                        v-if="props.reportOption?.value === 3"
                        :dataList="store_statuses"
                        :modelValue="selectedStoreStatus?.label || ''"
                        label="Store Status"
                        @update-data="handleUpdateStoreStatus"
                    />

                    <!-- Year Picker (Renewals, Sales Performance, Insurance Report, Contract of Lease) -->
                    <YearPicker
                        v-if="
                            props.reportOption?.value === 3 ||
                            props.reportOption?.value === 4 ||
                            props.reportOption?.value === 6 ||
                            props.reportOption?.value === 7
                        "
                        v-model="selectedYear"
                        label="Year"
                        placeholder="YYYY"
                        :minDate="props.reportOption?.value === 4 ? '2009' : null"
                        :yearRange="props.reportOption?.value === 4 ? [2009, new Date().getFullYear()] : [1900, new Date().getFullYear()]"
                        :allYears="props.reportOption?.value !== 4"
                    />
                </div>
            </div>

            <!-- Footer Buttons -->
            <div class="border-t border-light-gray mt-6 p-6 bg-gray-50">
                <div class="flex flex-col sm:flex-row gap-4 justify-end">
                    <SecondaryButton class="!font-medium w-full sm:w-auto" @click="cancel">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton
                        :disabled="!props.reportOption?.allow_generate"
                        class="!font-medium w-full sm:w-auto"
                        @click="applyFilters"
                    >
                        Generate
                    </PrimaryButton>
                </div>
            </div>
        </template>
    </FilterModal>
</template>
