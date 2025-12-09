<script setup>
import { computed } from 'vue';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    totalItems: { type: Number, required: true },
    itemsPerPage: { type: Number, required: true },
    currentPage: { type: Number, required: true },
});
const emit = defineEmits(['update:currentPage']);

const totalPages = computed(() => Math.ceil(props.totalItems / props.itemsPerPage));
const startItem = computed(() => (props.currentPage - 1) * props.itemsPerPage + 1);
const endItem = computed(() => Math.min(props.currentPage * props.itemsPerPage, props.totalItems));

const pages = computed(() => {
    let pagesArray = [];
    for (let i = 1; i <= totalPages.value; i++) {
        pagesArray.push(i);
    }
    return pagesArray;
});

const changePage = (page) => {
    emit('update:currentPage', page);
};

const prevPage = () => {
    if (props.currentPage > 1) {
        emit('update:currentPage', props.currentPage - 1);
    }
};

const nextPage = () => {
    if (props.currentPage < totalPages.value) {
        emit('update:currentPage', props.currentPage + 1);
    }
};
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <p class="text-sm text-gray-700">
                Showing
                <span class="font-medium">{{ startItem }}</span>
                to
                <span class="font-medium">{{ endItem }}</span>
                of
                <span class="font-medium">{{ totalItems }}</span>
                results
            </p>
            <nav
                class="isolate inline-flex bg-white -space-x-px rounded-md shadow-sm"
                aria-label="Pagination"
            >
                <button
                    @click="prevPage"
                    :disabled="currentPage === 1"
                    class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                >
                    <ChevronLeftIcon class="size-5" aria-hidden="true" />
                </button>
                <button
                    v-for="page in pages"
                    :key="page"
                    @click="changePage(page)"
                    :class="[
                        page === currentPage
                            ? 'z-10 bg-rose-50 text-primary border border-primary'
                            : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50',
                    ]"
                    class="relative inline-flex items-center px-4 py-2 text-sm font-medium focus:z-20"
                >
                    {{ page }}
                </button>
                <button
                    @click="nextPage"
                    :disabled="currentPage === totalPages"
                    class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                >
                    <ChevronRightIcon class="size-5" aria-hidden="true" />
                </button>
            </nav>
        </div>
    </div>
</template>
