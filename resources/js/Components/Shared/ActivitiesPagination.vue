<template>
    <div class="py-6 flex items-center justify-between">
        <!-- Only show info if there is data -->
        <div class="flex-1">
            <p v-if="pagination.total > 0" class="text-sm leading-5 text-gray-700">
                Showing
                <span class="font-medium">{{ info.page }}</span>
                —
                <span class="font-medium">{{ info.showing }}</span>
            </p>
        </div>
        <div class="flex-1 flex justify-between sm:hidden">
            <a
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm leading-5 font-medium rounded-md text-gray-700 bg-white hover:text-gray-500 focus:outline-none focus:ring-blue-200 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 cursor-pointer"
                @click="previousPage"
            >
                Previous
            </a>
            <a
                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm leading-5 font-medium rounded-md text-gray-700 bg-white hover:text-gray-500 focus:outline-none focus:ring-blue-200 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 cursor-pointer"
                @click="nextPage"
            >
                Next
            </a>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:justify-end">
            <div>
                <nav class="relative z-0 inline-flex shadow-sm">
                    <a :class="pager.class.thumb" class="rounded-l-md" @click="firstPage">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M11 19l-7-7 7-7m8 14l-7-7 7-7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                            ></path>
                        </svg>
                    </a>
                    <a :class="pager.class.distributable" @click="previousPage">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M15 19l-7-7 7-7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                            ></path>
                        </svg>
                    </a>
                    <a
                        v-for="page in group.left"
                        :key="page"
                        :class="[
                            pager.class.distributable,
                            page === pagination.current_page ? '!bg-rose-50' : '',
                        ]"
                        @click="toPage(page)"
                    >
                        <span style="user-select: none">{{ page }}</span>
                    </a>
                    <a
                        :class="
                            pager.class.distributable +
                            (pagination.total_pages > 6 ? ' inline-block' : ' hidden')
                        "
                    >
                        <span style="user-select: none">...</span>
                    </a>
                    <a
                        v-for="page in group.right"
                        :key="page"
                        :class="[
                            pager.class.distributable,
                            page === pagination.current_page ? '!bg-rose-50' : '',
                        ]"
                        @click="toPage(page)"
                    >
                        <span style="user-select: none">{{ page }}</span>
                    </a>
                    <a :class="pager.class.distributable" @click="nextPage">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M9 5l7 7-7 7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                            ></path>
                        </svg>
                    </a>
                    <a :class="pager.class.thumb" class="rounded-r-md" @click="lastPage">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M13 5l7 7-7 7M5 5l7 7-7 7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                            ></path>
                        </svg>
                    </a>
                </nav>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ActivitiesPagination',
    props: {
        info: {
            type: Object,
            default: () => ({ page: 0, showing: 0 }),
        },
        pagination: {
            type: Object,
            required: true,
            // Expected structure: { total: Number, current_page: Number, total_pages: Number, message: String }
        },
    },
    data() {
        return {
            pager: {
                class: {
                    distributable:
                        '-ml-px relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm leading-5 font-medium text-gray-700 hover:text-gray-500 focus:outline-none focus:ring-blue-200 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 cursor-pointer',
                    thumb: '-ml-px relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm leading-5 font-medium text-gray-700 hover:text-gray-400 focus:outline-none focus:ring-blue-200 focus:border-blue-300 active:bg-gray-100 active:text-gray-500 transition ease-in-out duration-150 cursor-pointer',
                },
            },
            boundary: 3,
            group: { left: [], right: [] },
        };
    },
    watch: {
        pagination: {
            immediate: true,
            handler(newVal) {
                this.group.left = [];
                this.group.right = [];

                // When there is no data, show only 1 page in the actions.
                if (newVal.total === 0) {
                    this.group.left.push(1);
                    return;
                }

                if (newVal.total_pages > 6) {
                    const half =
                        newVal.total_pages % 2 === 0
                            ? newVal.total_pages / 2
                            : (newVal.total_pages + 1) / 2;
                    let index = newVal.current_page;
                    if (index <= half) {
                        let total = newVal.total_pages;
                        for (let i = 1; i <= this.boundary; i++) {
                            this.group.right.unshift(total);
                            total--;
                        }
                        if (newVal.current_page > 1) {
                            index--;
                            if (half - 1 === index) {
                                index--;
                            }
                        }
                        for (let i = 1; i <= this.boundary; i++) {
                            if (index <= half) {
                                this.group.left.push(index);
                                index++;
                            }
                        }
                    } else {
                        for (let i = 1; i <= this.boundary; i++) {
                            this.group.left.push(i);
                        }
                        if (newVal.current_page > half + 1) {
                            index--;
                            if (half - 1 === index) {
                                index--;
                            }
                        }
                        for (let i = 1; i <= this.boundary; i++) {
                            if (index <= newVal.total_pages) {
                                this.group.right.push(index);
                                index++;
                            }
                        }
                    }
                } else {
                    for (let i = 1; i <= newVal.total_pages; i++) {
                        this.group.left.push(i);
                    }
                }
            },
            deep: true,
        },
    },
    methods: {
        firstPage() {
            this.$emit('first-page');
        },
        previousPage() {
            this.$emit('previous-page');
        },
        toPage(page) {
            this.$emit('to-page', page);
        },
        nextPage() {
            this.$emit('next-page');
        },
        lastPage() {
            this.$emit('last-page');
        },
    },
};
</script>
