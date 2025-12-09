<script setup>
import { v4 as uuid } from 'uuid';
import { ref } from 'vue';

const emits = defineEmits(['uploaded']);

defineProps({
    attachments: { type: Array, default: () => [] },
    id: {
        type: String,
        default() {
            return `multiple-file-upload-${uuid()}`;
        },
    },
    disabled: { type: Boolean, default: false },
    error: { type: String, default: null },
    fileTypes: { type: String, default: '.jpg,.jpeg,.png' },
    helpText: { type: String, default: null },
    label: { type: String, default: 'Upload' },
    labelClass: { type: String, default: 'text-sm/6 font-medium text-gray-900' },
    required: { type: Boolean, default: false },
    inputClass: {
        type: String,
        default: 'w-full text-sm text-gray-900 outline-gray-300 placeholder:text-gray-400',
    },
});

const files = ref(null);

function change(file) {
    files.value = file;
    emits('uploaded', files.value);
}
</script>
<template>
    <div>
        <label :class="labelClass">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        <div class="relative">
            <input
                :id="id"
                :accept="fileTypes"
                class="absolute w-full h-full opacity-0 cursor-pointer disabled:cursor-not-allowed"
                :multiple="true"
                :disabled="disabled"
                :required="required"
                type="file"
                @change="change($event.target.files)"
            />

            <label
                class="leading-6 mt-1 block py-2 px-3 mr-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent sm:text-sm sm:leading-5"
                :class="[
                    disabled ? 'opacity-70 cursor-not-allowed text-gray-500 outline-gray-200' : '',
                    error ? 'text-red-900 border border-red-300 placeholder:text-red-300' : '',
                    inputClass,
                ]"
                :for="id"
            >
                <span v-if="attachments.length <= 0">No file chosen</span>
                <span v-else v-for="(attachment, index) in attachments">
                    {{ attachment.name }} {{ index !== attachments.length - 1 ? ', ' : '' }}
                </span>
            </label>
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="2"
                    stroke="currentColor"
                    class="size-5"
                    :class="[
                        error ? 'text-red-700' : 'text-gray-900',
                        disabled ? 'opacity-70' : 'opacity-100',
                    ]"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"
                    />
                </svg>
            </div>
        </div>
        <p v-if="helpText" class="mt-2 text-sm text-gray-500">{{ helpText }}</p>
        <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>
    </div>
</template>
