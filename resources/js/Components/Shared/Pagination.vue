<template>
    <div
        class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6"
    >
        <div class="flex flex-1 justify-between sm:hidden">
            <button
                :disabled="currentPage === 1"
                @click="changePage(currentPage - 1)"
                class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Previous
            </button>
            <button
                :disabled="currentPage === totalPages"
                @click="changePage(currentPage + 1)"
                class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Next
            </button>
        </div>
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700">
                    Showing
                    <span class="font-medium">{{ startPage }}</span>
                    to
                    <span class="font-medium">{{ endPage }}</span>
                    of
                    <span class="font-medium">{{ totalResults }}</span>
                    results
                </p>
            </div>
            <div>
                <nav
                    class="isolate inline-flex -space-x-px rounded-md shadow-sm"
                    aria-label="Pagination"
                >
                    <button
                        :disabled="currentPage === 1"
                        @click="changePage(currentPage - 1)"
                        class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0"
                    >
                        <span class="sr-only">Previous</span>
                        <ChevronLeftIcon class="size-5" aria-hidden="true" />
                    </button>

                    <button
                        v-for="page in pageNumbers"
                        :key="page"
                        @click="changePage(page)"
                        :class="{
                            'z-10 bg-indigo-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600':
                                currentPage === page,
                            'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-offset-0':
                                currentPage !== page,
                        }"
                        class="relative inline-flex items-center px-4 py-2 text-sm font-semibold"
                    >
                        {{ page }}
                    </button>

                    <button
                        :disabled="currentPage === totalPages"
                        @click="changePage(currentPage + 1)"
                        class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0"
                    >
                        <span class="sr-only">Next</span>
                        <ChevronRightIcon class="size-5" aria-hidden="true" />
                    </button>
                </nav>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/20/solid';

// Props passed from parent component
const props = defineProps({
    currentPage: {
        type: Number,
        required: true,
    },
    totalResults: {
        type: Number,
        required: true,
    },
    pageSize: {
        type: Number,
        required: true,
    },
});

// Compute total pages based on the number of results and page size
const totalPages = computed(() => Math.ceil(props.totalResults / props.pageSize));

// Compute page numbers to display
const pageNumbers = computed(() => {
    const totalPagesToShow = 5;
    let startPage = Math.max(1, props.currentPage - Math.floor(totalPagesToShow / 2));
    let endPage = Math.min(totalPages.value, startPage + totalPagesToShow - 1);

    if (endPage - startPage + 1 < totalPagesToShow) {
        startPage = Math.max(1, endPage - totalPagesToShow + 1);
    }

    return Array.from({ length: endPage - startPage + 1 }, (_, i) => startPage + i);
});

// Calculate start and end page for display
const startPage = computed(() => (props.currentPage - 1) * props.pageSize + 1);
const endPage = computed(() => Math.min(props.currentPage * props.pageSize, props.totalResults));

// Change page function
const changePage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        emit('update:currentPage', page);
    }
};
</script>
