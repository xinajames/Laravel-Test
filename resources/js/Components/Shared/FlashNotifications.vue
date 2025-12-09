<script setup>
import { XCircleIcon } from '@heroicons/vue/24/solid/index.js';
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import CheckCircleIcon from '@/Components/Icon/CheckCircleIcon.vue';
import XCloseIcon from '@/Components/Icon/XCloseIcon.vue';

const show = ref(false);

watch(
    () => usePage().props.flash,
    () => {
        show.value = true;
        setTimeout(() => {
            if (show.value && (usePage().props.flash.success || usePage().props.flash.error)) {
                show.value = !show.value;
            }
        }, 4000);
    },
    { immediate: true, deep: true }
);
</script>
<template>
    <!-- Global notification live region, render this permanently at the end of the document -->
    <div
        aria-live="assertive"
        class="fixed inset-0 z-50 flex items-end px-4 py-6 pointer-events-none sm:p-6 sm:items-start"
    >
        <div class="w-full flex flex-col items-end space-y-4">
            <!-- Notification panel, dynamically insert this into the live region when it needs to be displayed -->
            <transition
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="($page.props.flash.success || $page.props.flash.error) && show"
                    class="max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden"
                >
                    <div class="p-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <CheckCircleIcon
                                    v-if="$page.props.flash.success"
                                    aria-hidden="true"
                                    class="h-6 w-6 text-green-400"
                                />
                                <XCircleIcon
                                    v-else
                                    aria-hidden="true"
                                    class="h-6 w-6 text-red-error"
                                />
                            </div>
                            <div class="ml-3 w-0 flex-1 pt-0.5">
                                <p class="font-semibold text-tenant-dark">
                                    {{
                                        $page.props.flash.success
                                            ? $page.props.flash.success
                                            : $page.props.flash.error
                                    }}
                                </p>
                            </div>
                            <div class="flex-shrink-0 flex">
                                <button class="cursor-pointer" type="button" @click="show = false">
                                    <span class="sr-only">Close</span>
                                    <XCloseIcon
                                        aria-hidden="true"
                                        class="text-tenant-medium-dark"
                                    />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </div>
    </div>
</template>
