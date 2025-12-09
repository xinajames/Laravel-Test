<script setup>
defineProps({
    imageUrl: {
        type: String,
        default: null,
    },
    alt: {
        type: String,
        default: 'Avatar',
    },
    customClass: {
        type: String,
        default: '',
    },
    imageClass: {
        type: String,
        default: 'size-10 rounded-full',
    },
    showBadge: {
        type: Boolean,
        default: false,
    },
    badgeColor: {
        type: String,
        default: 'bg-green-400', // Default badge color
    },
});
</script>

<template>
    <span :class="[customClass]" class="relative inline-block">
        <!-- Avatar Image or Placeholder -->
        <template v-if="imageUrl">
            <img :alt="alt" :src="imageUrl" :class="imageClass" />
        </template>
        <template v-else>
            <span class="inline-block overflow-hidden rounded-full bg-gray-100" :class="imageClass">
                <svg :class="imageClass" fill="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z"
                    />
                </svg>
            </span>
        </template>

        <!-- Badge (Fixed) -->
        <span
            v-if="showBadge"
            :class="[
                'absolute right-0 bottom-1 block w-3 h-3 rounded-full ring-2 ring-white',
                badgeColor,
            ]"
        ></span>
    </span>
</template>

<style scoped>
/* Scoped styles for custom adjustments, if needed */
</style>
