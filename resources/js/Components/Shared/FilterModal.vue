<script setup>
import { Dialog, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue';
import { XMarkIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    closable: { type: Boolean, default: true },
    open: { type: Boolean, default: false },
    large: { type: Boolean, default: false },
    title: { type: String, default: 'Filter' },
});
</script>

<template>
    <TransitionRoot :show="open" as="template">
        <Dialog as="div" class="relative z-50" @close="$emit('close')">
            <TransitionChild
                as="template"
                enter="ease-out duration-300"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="ease-in duration-200"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
            </TransitionChild>

            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
                <TransitionChild
                    as="template"
                    enter="ease-out duration-300"
                    enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to="opacity-100 translate-y-0 sm:scale-100"
                    leave="ease-in duration-200"
                    leave-from="opacity-100 translate-y-0 sm:scale-100"
                    leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <DialogPanel
                        :class="large ? 'max-w-4xl w-full' : 'sm:max-w-lg sm:w-full'"
                        class="relative transform overflow-hidden rounded-xl bg-white shadow-xl transition-all"
                    >
                        <!-- Close Button -->
                        <div
                            v-if="closable"
                            class="absolute right-0 top-0 hidden pr-4 pt-4 sm:block"
                        >
                            <button
                                class="rounded-full p-1.5 text-gray-400 transition-colors duration-200 hover:bg-gray-100 hover:text-gray-600 focus:outline-none"
                                type="button"
                                @click="$emit('close')"
                            >
                                <span class="sr-only">Close</span>
                                <XMarkIcon aria-hidden="true" class="h-6 w-6" />
                            </button>
                        </div>

                        <!-- Modal Header -->
                        <div class="px-6 py-4">
                            <h5 class="text-lg font-bold text-gray-900">{{ title }}</h5>
                        </div>

                        <!-- Modal Content -->
                        <div class="space-y-4">
                            <slot name="content"></slot>
                        </div>

                        <!-- Modal Footer (if exists) -->
                        <div
                            v-if="$slots.footer"
                            class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end space-x-2"
                        >
                            <slot name="footer"></slot>
                        </div>
                    </DialogPanel>
                </TransitionChild>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
