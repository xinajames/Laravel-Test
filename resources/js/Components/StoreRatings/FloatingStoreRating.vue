<script setup>
import { ref } from 'vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';

const emits = defineEmits(['dismiss', 'continue']);

const props = defineProps({
    storeRating: Object,
});

const isVisible = ref(true);

function handleDismiss() {
    isVisible.value = false;
    emits('dismiss');
}
</script>

<template>
    <div
        v-if="isVisible"
        class="fixed bottom-6 left-1/2 transform -translate-x-1/2 bg-white border border-gray-200 p-4 sm:p-5 flex flex-col sm:flex-row gap-4 sm:gap-6 rounded-2xl shadow-lg z-30 items-center w-[90%] sm:w-auto transition-all"
    >
        <!-- Dismiss Button -->
        <div class="flex items-center self-start sm:self-center">
            <button
                class="text-gray-400 hover:text-gray-600 flex items-center"
                @click="handleDismiss"
            >
                ✕
            </button>
        </div>

        <!-- Image and Title -->
        <div class="flex items-center gap-2 sm:gap-6 flex-1">
            <div class="h-[56px] w-[56px] relative">
                <div
                    class="bg-gray-100 w-full h-full flex items-center justify-center rounded-2xl overflow-hidden"
                >
                    <img
                        alt=""
                        class="absolute inset-0 w-full h-full object-cover rounded-2xl"
                        src="/img/placeholder/placeholder_bg.png"
                    />
                    <img
                        alt=""
                        class="relative h-[65%] w-auto object-cover"
                        src="/img/placeholder/placeholder_store.png"
                    />
                </div>
                <img
                    v-if="storeRating.store?.image"
                    :src="storeRating.store?.image"
                    alt=""
                    class="absolute inset-0 rounded-xl object-cover w-full h-full border-2 border-white overflow-hidden"
                    @error="handleImageError"
                />
            </div>
            <div>
                <p class="text-lg font-medium text-center sm:text-left">Ongoing Store Rating</p>
            </div>
        </div>

        <!-- Actions (Button Section) -->
        <div class="flex w-full sm:w-auto justify-center sm:justify-start">
            <PrimaryButton class="w-full sm:w-auto" @click="emits('continue')">
                Continue
            </PrimaryButton>
        </div>
    </div>
</template>
