<script setup>
import { ref } from 'vue';
import PencilIcon from '@/Components/Icon/PencilIcon.vue';

const emits = defineEmits(['uploaded']);

const props = defineProps({
    imageUrl: {
        type: String,
        default: null,
    },
    alt: {
        type: String,
        default: 'Avatar',
    },
    customClass: {
        type: String,
        default: '',
    },
    imageClass: {
        type: String,
        default: 'w-24 h-24 rounded-full', // 100px size
    },
    showBadge: {
        type: Boolean,
        default: false,
    },
    badgeColor: {
        type: String,
        default: 'bg-green-400',
    },
});

const imageSrc = ref(props.imageUrl);
const fileInput = ref(null);

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            imageSrc.value = e.target.result;
            emits('uploaded', file);
        };
        reader.readAsDataURL(file);
    }
};

const triggerFileUpload = () => {
    fileInput.value.click();
};
</script>

<template>
    <div :class="['relative inline-block', customClass]">
        <!-- Avatar Image or Placeholder -->
        <template v-if="imageSrc">
            <img :alt="alt" :src="imageSrc" :class="imageClass" />
        </template>
        <template v-else>
            <span class="inline-block overflow-hidden rounded-full bg-gray-100" :class="imageClass">
                <svg :class="imageClass" fill="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z"
                    />
                </svg>
            </span>
        </template>

        <!-- Badge (Fixed) -->
        <span
            v-if="showBadge"
            :class="[
                'absolute right-0 bottom-1 block w-3 h-3 rounded-full ring-2 ring-white',
                badgeColor,
            ]"
        ></span>

        <!-- Edit Button -->
        <button
            @click="triggerFileUpload"
            class="absolute bottom-0 right-0 p-1 bg-gray-200 border rounded-full shadow-md hover:bg-gray-200"
        >
            <PencilIcon class="w-4 h-4 text-gray-700" />
        </button>

        <!-- Hidden File Input -->
        <input
            ref="fileInput"
            type="file"
            accept="image/*"
            class="hidden"
            @change="handleFileChange"
        />
    </div>
</template>

<style scoped>
/* Additional styles if needed */
</style>
