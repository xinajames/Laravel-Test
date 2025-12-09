<script setup>
import { computed, onMounted, ref } from 'vue';
import EllipseIcon from '@/Components/Icon/EllipseIcon.vue';
import SegmentBar from '@/Components/Common/SegmentBar/SegmentBar.vue';
import DashboardInformationModal from '@/Components/Modal/DashboardInformationModal.vue';
import InformationCircleIcon from '@/Components/Icon/InformationCircleIcon.vue';

const regions = ref([]);
const totalFranchisee = ref(0);

const segmentsWithPercent = computed(() => {
    const sum = regions.value.reduce((a, r) => a + r.count, 0) || 1;
    return regions.value.map((r) => ({ ...r, percent: (r.count / sum) * 100 }));
});

const showInfo = ref(false);

function getData() {
    axios.get(route('dashboard.getFranchiseeRegionDetails')).then(({ data }) => {
        totalFranchisee.value = data.TOTAL;

        const cfg = {
            LUZ: { label: 'Luzon', class: 'bg-rose-300', iconClass: 'text-rose-300' },
            VIS: { label: 'Visayas', class: 'bg-orange-200', iconClass: 'text-orange-200' },
            MIN: { label: 'Mindanao', class: 'bg-red-400', iconClass: 'text-red-400' },
            NULL: { label: 'Others', class: 'bg-gray-300', iconClass: 'text-gray-300' },
        };

        regions.value = Object.entries(data)
            .filter(([k]) => k !== 'TOTAL' && k !== 'NULL')
            .map(([k, v]) => ({
                label: cfg[k].label,
                count: v,
                class: cfg[k].class,
                iconClass: cfg[k].iconClass,
            }))
            .filter((r) => r.count > 0);
    });
}

onMounted(getData);
</script>

<template>
    <div
        class="relative z-20 bg-white shadow-md rounded-2xl p-6 mt-4 flex flex-col md:flex-row items-center md:h-[240px] mx-4 md:mx-10"
    >
        <div
            class="border-b md:border-b-0 md:border-r-2 border-gray-300 pb-4 md:pb-0 md:h-full pr-0 md:pr-8 pl-2 flex items-center justify-center w-full md:w-auto"
        >
            <img
                alt="Dashboard Logo"
                class="w-[100px] md:w-[142px] h-[80px] md:h-[120px] object-contain"
                src="/img/julies_bakeshop.png"
            />
        </div>
        <div
            class="flex flex-col md:flex-row items-center md:items-start pl-0 md:pl-6 gap-6 md:gap-10 w-full mt-6 md:mt-0"
        >
            <!-- Map -->
            <div class="flex justify-center md:justify-start w-full md:w-auto">
                <img
                    alt="Map"
                    class="h-[180px] md:h-[245px] w-auto object-contain"
                    src="/img/map.png"
                />
            </div>
            <!-- Details -->
            <div class="flex flex-col gap-6 w-full py-8">
                <!-- Total -->
                <div
                    class="flex flex-col gap-1 items-center md:items-start text-center md:text-left"
                >
                    <div class="flex items-center gap-2">
                        <p class="text-base md:text-lg text-gray-600">Total Franchisee</p>
                        <InformationCircleIcon
                            class="w-5 h-5 text-gray-600 cursor-pointer"
                            @click="showInfo = true"
                        />
                    </div>
                    <h1 class="text-4xl font-bold text-gray-900">
                        {{ totalFranchisee }}
                    </h1>
                </div>

                <!-- Region Stats -->
                <div class="flex flex-col gap-4 w-full">
                    <div class="flex flex-wrap justify-center md:justify-start gap-8">
                        <div
                            v-for="(region, index) in segmentsWithPercent"
                            :key="index"
                            class="flex flex-col items-center md:items-start"
                        >
                            <div class="flex gap-2 items-center">
                                <EllipseIcon :class="region.iconClass" />
                                <p class="text-sm">{{ region.label }}</p>
                            </div>
                            <h1 class="font-bold text-3xl text-gray-600">{{ region.count }}</h1>
                        </div>
                    </div>

                    <div class="w-full">
                        <SegmentBar :segments="segmentsWithPercent" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <DashboardInformationModal :open="showInfo" type="total-franchisee" @close="showInfo = false" />
</template>
