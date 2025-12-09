<script setup>
import { computed } from 'vue';

const props = defineProps({
    segments: {
        type: Array,
        required: true,
    },
});

const getLeft = (index) => {
    let total = 0;
    for (let i = 0; i < index; i++) {
        total += props.segments[i].percent;
    }
    return `${total}%`;
};

const getRoundedClass = (index) => {
    if (index === 0) return 'rounded-l-full';
    if (index === props.segments.length - 1) return 'rounded-r-full';
    return '';
};
</script>

<template>
    <div class="relative h-3 w-full rounded-full overflow-hidden bg-gray-100">
        <template v-for="(segment, index) in segments" :key="index">
            <div
                class="absolute h-full"
                :class="segment.class + ' ' + getRoundedClass(index)"
                :style="{ left: getLeft(index), width: segment.percent + '%' }"
            ></div>
        </template>
    </div>
</template>
