<script setup>
import { computed } from 'vue';

const {
    type,
    category,
    class: customClass,
} = defineProps({
    type: {
        type: String,
        required: true,
    },
    category: {
        type: String,
        required: true,
        validator: (value) => ['storeStatus', 'franchiseeStatus', 'userStatus'].includes(value),
    },
    class: {
        type: String,
        default: '',
    },
});

// Define color mappings for different categories
const statusColors = {
    storeStatus: {
        Open: { badge: 'bg-green-100 text-green-800', icon: 'fill-green-500' },
        Future: { badge: 'bg-indigo-100 text-indigo-800', icon: 'fill-indigo-500' },
        'Temporary Closed': { badge: 'bg-pink-100 text-pink-800', icon: 'fill-pink-500' },
        Closed: { badge: 'bg-red-100 text-red-800', icon: 'fill-red-500' },
        Deactivated: { badge: 'bg-gray-100 text-gray-800', icon: 'fill-gray-500' },
    },
    franchiseeStatus: {
        Active: { badge: 'bg-green-100 text-green-800', icon: 'fill-green-500' },
        Inactive: { badge: 'bg-red-100 text-red-800', icon: 'fill-red-500' },
    },
    userStatus: {
        Active: { badge: 'bg-green-100 text-green-800', icon: 'fill-green-500' },
        Inactive: { badge: 'bg-gray-100 text-gray-800', icon: 'fill-red-500' },
        Deactivated: { badge: 'bg-red-100 text-red-800', icon: 'fill-red-500' },
    },
};

// Compute badge and icon styles based on `type` and `category`
const badgeClasses = computed(() => {
    return statusColors[category]?.[type]?.badge || 'bg-gray-100 text-gray-600';
});

const iconFill = computed(() => {
    return statusColors[category]?.[type]?.icon || 'fill-gray-400';
});
</script>

<template>
    <span
        :class="[badgeClasses, customClass]"
        class="inline-flex items-center gap-x-1.5 rounded-md px-2 py-1 text-xs font-medium"
    >
        <svg
            v-if="!customClass.includes('[&_svg]:hidden')"
            :class="iconFill"
            aria-hidden="true"
            class="size-1.5"
            viewBox="0 0 6 6"
        >
            <circle cx="3" cy="3" r="3" />
        </svg>
        <slot />
    </span>
</template>
