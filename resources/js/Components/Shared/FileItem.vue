<script setup>
import { XMarkIcon } from '@heroicons/vue/20/solid';

const emits = defineEmits(['remove']);
const props = defineProps({
    file: Object,
});
</script>
<template>
    <div class="border border-gray-300 p-2 rounded-md flex justify-between">
        <p class="text-gray-600 font-heading font-medium">
            {{ file.name }}
        </p>
        <XMarkIcon class="text-gray-600 size-5 cursor-pointer" @click="emits('remove')" />
    </div>
</template>
