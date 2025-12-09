<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: String,
    rating: Number,
});

const ratingColor = computed(() => {
    if (props.rating >= 0 && props.rating <= 2) return 'bg-yellow-300';
    if (props.rating > 2 && props.rating < 4) return 'bg-orange-500';
    return 'bg-green-500';
});
</script>

<template>
    <div>
        <div class="flex justify-between items-center gap-4">
            <p class="text-sm">{{ label }}</p>
            <p>{{ rating.toFixed(1) }}</p>
        </div>
        <div class="w-full h-1 rounded bg-gray-300 mt-2 overflow-hidden">
            <div
                :style="{ width: Math.trunc((rating / 5) * 100) + '%' }"
                :class="ratingColor"
                class="h-full transition-all duration-300"
            ></div>
        </div>
    </div>
</template>
