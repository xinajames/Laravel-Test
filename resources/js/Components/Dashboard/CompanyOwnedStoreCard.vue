<script setup>
import { computed, ref } from 'vue';
import BarChart from '@/Components/Common/BarChart/BarChart.vue';
import EllipseIcon from '@/Components/Icon/EllipseIcon.vue';
import DashboardInformationModal from '@/Components/Modal/DashboardInformationModal.vue';
import InformationCircleIcon from '@/Components/Icon/InformationCircleIcon.vue';

const props = defineProps({
    storeTypes: { type: Array, required: true },
    stores: { type: Array, required: true },
});

const showInfo = ref(false);

const chartData = computed(() => {
    const labels = props.stores.map((s) => s.group || 'N/A');

    const radiusForTop = (ctx) => {
        const { chart, dataIndex, datasetIndex } = ctx;
        for (let i = chart.data.datasets.length - 1; i >= 0; i--) {
            if ((chart.data.datasets[i].data[dataIndex] || 0) > 0) {
                return i === datasetIndex ? { topLeft: 8, topRight: 8 } : 0;
            }
        }
        return 0;
    };

    const datasets = [
        {
            label: 'Outlet',
            backgroundColor: '#AB9CE0',
            data: props.stores.map((s) => s.storeType?.outlet ?? 0),
            borderRadius: radiusForTop,
            borderSkipped: 'bottom',
        },
        {
            label: 'Junior',
            backgroundColor: '#FFBCE0',
            data: props.stores.map((s) => s.storeType?.junior ?? 0),
            borderRadius: radiusForTop,
            borderSkipped: 'bottom',
        },
        {
            label: 'Express',
            backgroundColor: '#F395A1',
            data: props.stores.map((s) => s.storeType?.express ?? 0),
            borderRadius: radiusForTop,
            borderSkipped: 'bottom',
        },
        {
            label: 'Branch',
            backgroundColor: '#FFD29F',
            data: props.stores.map((s) => s.storeType?.branch ?? 0),
            borderRadius: radiusForTop,
            borderSkipped: 'bottom',
        },
    ];
    return { labels, datasets };
});

const maxValue = computed(() =>
    Math.max(...props.stores.flatMap((s) => Object.values(s.storeType || {})), 0)
);

const calculateStepSize = (max) => (max <= 50 ? 10 : max <= 100 ? 20 : max <= 200 ? 50 : 100);

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        x: { stacked: true },
        y: {
            stacked: true,
            beginAtZero: true,
            ticks: { stepSize: calculateStepSize(maxValue.value) },
        },
    },
}));
</script>

<template>
    <div class="p-4 bg-white rounded-2xl border border-gray-200 shadow-md w-full">
        <div class="flex flex-col">
            <div class="flex items-center gap-1">
                <p>Company Owned Stores</p>
                <InformationCircleIcon
                    class="w-5 h-5 text-gray-600 cursor-pointer"
                    @click="showInfo = true"
                />
            </div>

            <p class="text-2xl font-bold">{{ stores.reduce((a, s) => a + (s.count || 0), 0) }}</p>

            <div class="flex flex-wrap gap-x-4 gap-y-2 mt-4">
                <div v-for="(t, i) in storeTypes" :key="i" class="flex items-center gap-2">
                    <EllipseIcon :class="t.iconClass" />
                    <p class="text-sm">{{ t.label }}</p>
                </div>
            </div>

            <div class="mt-4 h-[300px]">
                <BarChart :chart-data="chartData" :chart-options="chartOptions" />
            </div>
        </div>
    </div>

    <DashboardInformationModal
        :open="showInfo"
        type="company-owned-store"
        @close="showInfo = false"
    />
</template>
