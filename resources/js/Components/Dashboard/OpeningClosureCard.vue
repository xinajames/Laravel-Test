<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { MenuItem, MenuItems } from '@headlessui/vue';
import { CalendarIcon } from '@heroicons/vue/24/outline';
import axios from 'axios';

import DropdownSelect from '@/Components/Common/Select/DropdownSelect.vue';
import Dropdown from '@/Components/Shared/Dropdown.vue';
import LineChart from '@/Components/Common/LineChart/LineChart.vue';
import EllipseIcon from '@/Components/Icon/EllipseIcon.vue';
import DashboardCustomDateModal from '@/Components/Modal/DashboardCustomDateModal.vue';
import DashboardInformationModal from '@/Components/Modal/DashboardInformationModal.vue';
import InformationCircleIcon from '@/Components/Icon/InformationCircleIcon.vue';

const props = defineProps({ storeTypes: { type: Array, required: true } });

const activeTab = ref('openings');
const currentYear = new Date().getFullYear();
const dateOptions = [
    { label: 'This year', value: 'this_year', year: currentYear },
    { label: 'Last year', value: 'last_year', year: currentYear - 1 },
    { label: '2 years ago', value: '2_yrs_ago', year: currentYear - 2 },
    { label: 'Custom', value: 'custom' },
];
const selectedDateOption = ref(dateOptions[0]);
const customSelectedYear = ref(null);
const modalOpen = ref(false);
const previousOptionBeforeModal = ref(selectedDateOption.value);
const stores = ref([]);

const groups = [
    { label: 'Luzon', value: 'LUZ' },
    { label: 'Visayas', value: 'VIS' },
    { label: 'Mindanao', value: 'MIN' },
];
const selectedRegion = ref(groups[0]);

const store_groups = [
    { label: 'Full Franchise', value: 'FullFranchise' },
    { label: 'Company Owned', value: 'CompanyOwned' },
];
const selectedStoreGroup = ref(store_groups[0]);

const monthsOrder = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
];

const yearTotal = ref({
    Branch: 0,
    Express: 0,
    Junior: 0,
    Outlet: 0,
    Others: 0,
    total: 0,
});

const showInfo = ref(false);

function getSelectedYear() {
    if (selectedDateOption.value.value === 'custom') {
        return customSelectedYear.value || currentYear;
    }
    return selectedDateOption.value.year;
}

function getData() {
    const params = {
        region: selectedRegion.value.value,
        store_group: selectedStoreGroup.value.value,
        date_year: getSelectedYear(),
        date_field:
            activeTab.value === 'openings'
                ? 'grand_opening_date'
                : 'maintenance_permanent_closure_date',
    };

    axios.get(route('dashboard.getStoreOpeningClosures'), { params }).then(({ data }) => {
        yearTotal.value = data.year_total;
        stores.value = [
            {
                region: selectedRegion.value.label,
                period: selectedDateOption.value.label,
                data: monthsOrder.map((month) => ({
                    month: month,
                    branch: data[month]?.Branch || 0,
                    express: data[month]?.Express || 0,
                    junior: data[month]?.Junior || 0,
                    outlet: data[month]?.Outlet || 0,
                })),
            },
        ];
    });
}

function updateDate(option) {
    if (option.value === 'custom') {
        previousOptionBeforeModal.value = selectedDateOption.value;
        modalOpen.value = true;
    } else {
        selectedDateOption.value = option;
        customSelectedYear.value = null;
        getData();
    }
}

function handleYearSelected(year) {
    customSelectedYear.value = year;
    selectedDateOption.value = dateOptions.find((opt) => opt.value === 'custom');
    getData();
}

function closeModal() {
    modalOpen.value = false;
    // If no custom year was selected, revert to previous option
    if (selectedDateOption.value.value === 'custom' && !customSelectedYear.value) {
        selectedDateOption.value = previousOptionBeforeModal.value;
    }
}

watch([selectedRegion, selectedStoreGroup], getData);

watch(activeTab, getData);

const chartData = computed(() => {
    const months = stores.value[0]?.data.map((d) => d.month) || [];
    const palette = { branch: '#FFD29F', express: '#F395A1', junior: '#FFBCE0', outlet: '#AB9CE0' };
    return {
        labels: months,
        datasets: ['branch', 'express', 'junior', 'outlet'].map((c) => ({
            label: c.charAt(0).toUpperCase() + c.slice(1),
            data: stores.value[0]?.data.map((d) => d[c]) || [],
            borderColor: palette[c],
            tension: 0.4,
            fill: false,
        })),
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { beginAtZero: false, grid: { drawBorder: false, borderDash: [5, 5] } },
        x: { grid: { drawBorder: false, display: false } },
    },
};

const selectedDateDisplay = computed(() => {
    if (selectedDateOption.value.value === 'custom') {
        return customSelectedYear.value ? String(customSelectedYear.value) : 'Select Year';
    }
    return String(selectedDateOption.value.year);
});

onMounted(getData);
</script>

<template>
    <div class="p-4 bg-white rounded-2xl border border-gray-200 shadow-md w-full">
        <div class="flex items-center justify-between">
            <!-- Toggle -->
            <div class="flex items-center justify-between gap-2">
                <div class="bg-gray-100 rounded-lg p-1.5 flex gap-2">
                    <div
                        v-for="tab in ['openings', 'closures']"
                        :key="tab"
                        :class="activeTab === tab ? 'bg-white border border-gray-300' : ''"
                        class="rounded-lg p-1.5 cursor-pointer transition"
                        @click="activeTab = tab"
                    >
                        <p
                            :class="[
                                'font-medium text-xs',
                                activeTab === tab ? 'text-gray-800' : 'text-gray-500',
                            ]"
                        >
                            {{ tab.charAt(0).toUpperCase() + tab.slice(1) }}
                        </p>
                    </div>
                </div>
                <InformationCircleIcon
                    class="w-5 h-5 text-gray-600 cursor-pointer"
                    @click="showInfo = true"
                />
            </div>

            <div class="flex items-center gap-4">
                <!-- Select Region -->
                <DropdownSelect
                    v-model="selectedRegion"
                    :value="selectedRegion"
                    custom-class="!border-gray-300 !mt-0 w-full"
                >
                    <option
                        v-for="(group, index) in groups"
                        :key="index"
                        :selected="group.value === selectedRegion.value"
                        :value="group"
                    >
                        {{ group.label }}
                    </option>
                </DropdownSelect>
                <!-- Select Store Group -->
                <DropdownSelect
                    v-model="selectedStoreGroup"
                    :value="selectedStoreGroup"
                    custom-class="!border-gray-300 !mt-0 w-full"
                >
                    <option
                        v-for="(group, index) in store_groups"
                        :key="index"
                        :selected="group.value === selectedStoreGroup.value"
                        :value="group"
                    >
                        {{ group.label }}
                    </option>
                </DropdownSelect>
                <!-- Select Date Filters -->
                <Dropdown>
                    <template #trigger>
                        <div
                            class="bg-white rounded-md px-3 py-2 flex items-center gap-2 text-sm text-gray-700 cursor-pointer border border-gray-300 shadow-sm !mt-1"
                        >
                            <CalendarIcon class="w-4 h-4 text-gray-500 font-medium" />
                            {{ selectedDateDisplay }}
                        </div>
                    </template>
                    <template #menu>
                        <div
                            ref="container"
                            class="max-h-60 overflow-y-auto min-w-[200px] w-full bg-white rounded-lg"
                        >
                            <MenuItems class="flex flex-col w-full">
                                <MenuItem
                                    v-for="option in dateOptions"
                                    :key="option.value"
                                    class="w-full"
                                >
                                    <button
                                        :class="
                                            selectedDateOption.value === option.value
                                                ? 'text-red-600 font-semibold bg-gray-50'
                                                : 'text-gray-700'
                                        "
                                        class="w-full px-5 py-3 text-dark-gray-dark text-sm font-medium text-left flex items-center gap-4 whitespace-nowrap"
                                        @click="updateDate(option)"
                                    >
                                        {{ option.label }}
                                    </button>
                                </MenuItem>
                            </MenuItems>
                        </div>
                    </template>
                </Dropdown>
            </div>
        </div>

        <div class="flex flex-wrap gap-x-4 gap-y-2 mt-4">
            <div v-for="(t, i) in storeTypes" :key="i" class="flex items-center gap-2">
                <EllipseIcon :class="t.iconClass" />
                <p class="text-sm">
                    {{ t.label }}
                    <span v-if="yearTotal[t.label]">({{ yearTotal[t.label] }})</span>
                </p>
            </div>
        </div>

        <div class="h-[346px]">
            <LineChart :chart-data="chartData" :chart-options="chartOptions" />
        </div>

        <DashboardCustomDateModal
            :current-year="customSelectedYear"
            :open="modalOpen"
            title="Select Custom Year"
            @close="closeModal"
            @year-selected="handleYearSelected"
        />
    </div>

    <DashboardInformationModal :open="showInfo" type="opening-closure" @close="showInfo = false" />
</template>
