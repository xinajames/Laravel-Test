<script setup>
import Modal from '@/Components/Shared/Modal.vue';

const emits = defineEmits(['close']);

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    missingFields: {
        type: Array,
        required: true,
    },
    title: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Modal :open="open" max-width="md" @close="emits('close')">
        <template #content>
            <div class="relative px-6 py-5 space-y-4 text-center">
                <h4 class="font-semibold font-sans">{{ title }}</h4>

                <p>
                    Missing required fields detected. Please complete the data to ensure
                    completeness and accuracy.
                </p>

                <div class="space-y-2 border-t border-gray-200 pt-4">
                    <ul class="text-sm list-disc list-inside space-y-2">
                        <li v-for="field in missingFields" :key="field" class="text-left">
                            <span>{{ field }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </template>
    </Modal>
</template>
