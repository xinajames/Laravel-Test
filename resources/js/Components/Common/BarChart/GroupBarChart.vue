<script setup>
import { defineProps } from 'vue';
import { Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    BarElement,
    CategoryScale,
    LinearScale,
    Legend,
    Tooltip,
} from 'chart.js';

ChartJS.register(BarElement, CategoryScale, LinearScale, Legend, Tooltip);

const props = defineProps({
    chartData: {
        type: Object,
        required: true,
    },
    chartOptions: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Bar :data="props.chartData" :options="props.chartOptions" />
</template>
