<script setup>
import FilterRemoveIcon from '@/Components/Icon/FilterRemoveIcon.vue';
import TertiaryButton from '@/Components/Common/Button/TertiaryButton.vue';

const props = defineProps({
    activeFilters: Array,
    getFilterText: Function,
});

const emit = defineEmits(['removeFilter', 'clearFilters']);
</script>

<template>
    <div class="flex flex-wrap items-center gap-2 mt-4">
        <span
            v-for="(activeFilter, index) in activeFilters"
            :key="index"
            class="inline-flex items-center border border-light-gray bg-gray-200 rounded-full py-1 pl-2.5 pr-1 text-sm font-medium text-gray-800"
        >
            <span>
                {{ getFilterText(activeFilter) }}
            </span>
            <button
                class="ml-1 inline-flex h-4 w-4 flex-shrink-0 rounded-full p-1 text-gray-400 hover:bg-gray-200 hover:text-gray-500"
                type="button"
                @click="emit('removeFilter', index, activeFilter.column, activeFilter.operator)"
            >
                <span class="sr-only">Remove filter for {{ activeFilter.value }}</span>
                <FilterRemoveIcon class="text-medium-gray" />
            </button>
        </span>
    </div>
</template>
