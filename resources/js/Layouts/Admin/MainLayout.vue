<script setup>
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import FlashNotifications from '@/Components/Shared/FlashNotifications.vue';

const emits = defineEmits(['action', 'delete']);

defineProps({
    title: String,
    subTitle: String,
    subText: String,
    buttonText: String,
    showDelete: { type: Boolean, default: false },
    deleteText: String,
    showButton: { type: Boolean, default: false },
    showLocationDetails: { type: Boolean, default: false },
    showSecondButton: { type: Boolean, default: false },
});
</script>

<template>
    <div class="bg-gray-50">
        <div class="align-center">
            <!-- Top Bar -->
            <div
                :class="{
                    'flex items-center': !showLocationDetails,
                    'mt-6': showLocationDetails,
                }"
                class="sticky top-0 z-20 h-[120px] shrink-0 gap-x-4 border-b border-gray-200 bg-white px-4 sm:gap-x-6 sm:px-6 lg:px-8"
            >
                <div class="flex items-center justify-between max-w-7xl bg-white w-full mx-auto">
                    <div id="header" class="justify-between w-full items-center gap-x-4 flex">
                        <div class="flex items-center gap-4">
                            <img
                                alt="Julie's Logo"
                                class="h-14"
                                src="/img/julies_bakeshop_logo.png"
                            />
                            <div class="sm:block hidden gap-4 items-center">
                                <p class="text-base leading-6 font-bold text-primary">
                                    {{ subTitle }}
                                </p>
                                <h1 class="text-2xl font-bold text-gray-900">
                                    {{ title }}
                                </h1>
                                <p class="text-sm text-gray-500">
                                    {{ subText }}
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <PrimaryButton
                                v-if="showDelete"
                                class="!font-medium !bg-rose-50 !text-primary"
                                @click="emits('delete')"
                            >
                                {{ deleteText }}
                            </PrimaryButton>

                            <PrimaryButton
                                v-if="showButton"
                                class="!font-medium"
                                :class="
                                    subTitle !== 'RATE STORE' ? '!bg-rose-50 !text-primary' : ''
                                "
                                @click="emits('action')"
                            >
                                {{ buttonText }}
                            </PrimaryButton>
                        </div>
                    </div>
                </div>
                <!-- Conditionally render Teleport inside MainLayout -->
                <div v-if="showLocationDetails">
                    <div class="flex items-center max-w-7xl bg-white w-full mx-auto mt-4 gap-4">
                        <div class="w-12"></div>
                        <div class="flex" id="teleport-location"></div>
                    </div>
                </div>
            </div>

            <!-- Grid Layout for Modules that Use Left & Right Slots -->
            <div
                v-if="$slots['left-slot'] || $slots['right-slot']"
                class="flex max-w-7xl bg-gray-50 w-full mx-auto"
            >
                <main class="flex-1 py-10 overflow-y-auto min-h-screen">
                    <div class="max-w-7xl mx-auto md:px-6 px-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-4">
                            <!-- Left Slot -->
                            <div class="col-span-1">
                                <slot name="left-slot" />
                            </div>

                            <!-- Right Slot -->
                            <div class="col-span-1 md:col-span-2">
                                <slot name="right-slot">
                                    <slot />
                                </slot>
                            </div>
                        </div>
                    </div>
                </main>
            </div>

            <!-- Default Full-Width Slot When No Left/Right Slot is Used -->
            <div v-else class="w-full mx-auto bg-white">
                <slot />
            </div>
        </div>
    </div>

    <FlashNotifications />
</template>
