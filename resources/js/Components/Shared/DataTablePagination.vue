<template>
    <div class="py-6 flex items-center justify-between">
        <div class="flex-1">
            <p v-if="pagination.total > 0" class="text-sm leading-5 text-gray-700">
                Showing
                <span class="font-medium" v-text="info.page"></span>
                —
                <span class="font-medium" v-text="info.showing"></span>
            </p>
            <p v-else class="text-base leading-5 text-gray-700">
                <span class="font-medium" v-text="pagination.message"></span>
            </p>
        </div>
        <div class="flex-1 flex justify-between sm:hidden">
            <a
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm leading-5 font-medium rounded-md text-gray-700 bg-white hover:text-gray-500 focus:outline-none focus:ring-blue-200 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 cursor-pointer"
                @click="previousPage($parent)"
            >
                Previous
            </a>
            <a
                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm leading-5 font-medium rounded-md text-gray-700 bg-white hover:text-gray-500 focus:outline-none focus:ring-blue-200 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 cursor-pointer"
                @click="nextPage($parent)"
            >
                Next
            </a>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:justify-end">
            <div>
                <nav class="relative z-0 inline-flex shadow-sm">
                    <a :class="pager.class.thumb" class="rounded-l-md" @click="firstPage($parent)">
                        <svg
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                d="M11 19l-7-7 7-7m8 14l-7-7 7-7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                            ></path>
                        </svg>
                    </a>
                    <a :class="pager.class.distributable" @click="previousPage($parent)">
                        <svg
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg"
                        >
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
                        :class="[
                            pager.class.distributable,
                            page === pagination.current_page ? '!bg-rose-50' : '',
                        ]"
                        @click="toPage($parent, page)"
                    >
                        <span v-text="page"></span>
                    </a>
                    <a
                        :class="
                            pager.class.distributable +
                            (pagination.total_pages > 6 ? ' inline-block' : ' hidden')
                        "
                    >
                        <span>...</span>
                    </a>
                    <a
                        v-for="page in group.right"
                        :class="[
                            pager.class.distributable,
                            page === pagination.current_page ? '!bg-rose-50' : '',
                        ]"
                        @click="toPage($parent, page)"
                    >
                        <span v-text="page"></span>
                    </a>
                    <a :class="pager.class.distributable" @click="nextPage($parent)">
                        <svg
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                d="M9 5l7 7-7 7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                            ></path>
                        </svg>
                    </a>
                    <a :class="pager.class.thumb" class="rounded-r-md" @click="lastPage($parent)">
                        <svg
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg"
                        >
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
import { usePagination } from '@/Composables/Pagination';

export default {
    name: 'DataTablePagination',

    props: {
        info: Object,
        pagination: Object,
    },

    setup() {
        const usePaginate = usePagination();
        const firstPage = usePaginate.firstPage;
        const lastPage = usePaginate.lastPage;
        const nextPage = usePaginate.nextPage;
        const pageInformation = usePaginate.pageInformation;
        const previousPage = usePaginate.previousPage;
        const toPage = usePaginate.toPage;

        return { nextPage, previousPage, firstPage, lastPage, pageInformation, toPage };
    },

    data: function () {
        return {
            pager: {
                class: {
                    distributable:
                        '-ml-px relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm leading-5 font-medium text-gray-700 hover:text-gray-500 focus:z-10 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 cursor-pointer',
                    thumb: '-ml-px relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm leading-5 font-medium text-gray-700 hover:text-gray-400 focus:z-10 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue active:bg-gray-100 active:text-gray-500 transition ease-in-out duration-150 cursor-pointer',
                },
            },
            boundary: 3,
            group: { left: [], right: [] },
        };
    },

    watch: {
        pagination: {
            handler: function () {
                let that = this;
                that.group.left = [];
                that.group.right = [];

                if (that.pagination.total_pages > 6) {
                    let half = 0;
                    if (that.pagination.total_pages % 2 === 0) {
                        half = that.pagination.total_pages / 2;
                    } else {
                        half = (that.pagination.total_pages - 1) / 2;
                        half += 1;
                    }

                    let index = that.pagination.current_page;
                    if (index <= half) {
                        let total = that.pagination.total_pages;
                        for (let i = 1; i <= that.boundary; i++) {
                            that.group.right.unshift(total);
                            total -= 1;
                        }

                        if (that.pagination.current_page > 1) {
                            index -= 1;
                            if (half - 1 === index) {
                                index -= 1;
                            }
                        }

                        for (let i = 1; i <= that.boundary; i++) {
                            if (index <= half) {
                                that.group.left.push(index);
                                index += 1;
                            }
                        }
                    } else {
                        that.group.left = [];
                        that.group.right = [];

                        for (let i = 1; i <= that.boundary; i++) {
                            that.group.left.push(i);
                        }

                        if (that.pagination.current_page > half + 1) {
                            index -= 1;
                            if (half - 1 === index) {
                                index -= 1;
                            }
                        }

                        for (let i = 1; i <= that.boundary; i++) {
                            if (index <= that.pagination.total_pages) {
                                that.group.right.push(index);
                                index += 1;
                            }
                        }
                    }
                } else {
                    for (let i = 1; i <= that.pagination.total_pages; i++) {
                        that.group.left.push(i);
                    }
                }
            },
            deep: true,
        },
    },
};
</script>
