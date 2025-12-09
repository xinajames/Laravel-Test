<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { Dialog, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue';
import { Bars3Icon } from '@heroicons/vue/24/outline';
import MainMenu from '@/Layouts/Admin/MainMenu.vue';
import FlashNotifications from '@/Components/Shared/FlashNotifications.vue';

const sidebarOpen = ref(false);

defineProps({
    hasLeftNav: {
        type: Boolean,
        default: false,
    },
    contentNoPadding: {
        type: Boolean,
        default: false,
    },
    showTopBar: {
        type: Boolean,
        default: true, // Makes the top bar optional
    },
});

const isMobile = ref(false);

function checkWidth() {
    isMobile.value = window.innerWidth < 1024;
}

onMounted(() => {
    checkWidth();
    window.addEventListener('resize', checkWidth);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', checkWidth);
});
</script>

<template>
    <div>
        <!-- Mobile Sidebar -->
        <TransitionRoot :show="sidebarOpen" as="template">
            <Dialog class="relative z-50 lg:hidden" @close="sidebarOpen = false">
                <TransitionChild
                    as="template"
                    enter="transition-opacity ease-linear duration-300"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="transition-opacity ease-linear duration-300"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-gray-900/80" />
                </TransitionChild>
                <div class="fixed inset-0 flex">
                    <TransitionChild
                        as="template"
                        enter="transition ease-in-out duration-300 transform"
                        enter-from="-translate-x-full"
                        enter-to="translate-x-0"
                        leave="transition ease-in-out duration-300 transform"
                        leave-from="translate-x-0"
                        leave-to="-translate-x-full"
                    >
                        <DialogPanel
                            class="relative mr-16 flex w-full max-w-xs flex-1 z-50 border-r border-gray-200"
                        >
                            <MainMenu />
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Static Sidebar for Desktop -->
        <div class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-72 lg:flex-col">
            <MainMenu />
        </div>

        <!-- Main Content Area -->
        <div class="lg:pl-72">
            <!-- Top Bar (Optional) -->
            <div
                v-if="isMobile || showTopBar"
                class="sticky top-0 z-40 flex h-[44px] shrink-0 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8"
            >
                <button
                    class="-m-2.5 p-2.5 text-gray-700 lg:hidden"
                    type="button"
                    @click="sidebarOpen = true"
                >
                    <span class="sr-only">Open sidebar</span>
                    <Bars3Icon aria-hidden="true" class="size-6" />
                </button>
                <div aria-hidden="true" class="h-6 w-px bg-gray-900/10 lg:hidden" />
                <div id="portal-breadcrumb" class="hidden md:block"></div>
            </div>

            <div class="flex flex-col min-h-[calc(100vh-44px)] w-full bg-gray-50">
                <!-- Full-Width Header -->
                <slot class="w-full" name="header" />

                <div :class="hasLeftNav ? 'flex flex-1' : ''">
                    <!-- Left Navigation (Hidden on Mobile) -->
                    <aside
                        v-if="hasLeftNav"
                        class="hidden md:flex md:flex-col md:w-64 lg:w-72 bg-white sticky top-0 min-h-[calc(100vh-44px)] md:py-6 md:px-4 shadow-md"
                    >
                        <slot name="left-nav" />
                    </aside>

                    <!-- Main Content -->
                    <main class="flex-1">
                        <div class="w-full flex justify-center">
                            <div
                                :class="contentNoPadding ? 'p-0' : 'px-4 sm:px-6 lg:px-8'"
                                class="w-full"
                            >
                                <slot />
                            </div>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </div>

    <FlashNotifications />
</template>
