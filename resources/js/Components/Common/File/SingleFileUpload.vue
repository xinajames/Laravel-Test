<script setup>
import { v4 as uuid } from 'uuid';
import { ref } from 'vue';

const emits = defineEmits(['uploaded']);

defineProps({
    attachment: { type: [Object, String], default: null },
    id: {
        type: String,
        default() {
            return `singe-file-upload-${uuid()}`;
        },
    },
    buttonText: { type: String, default: null },
    buttonClass: { type: String, default: '' },
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
    type: { type: String, default: 'input-type' },
    modelValue: { type: [File, String], default: null },
});

const fileData = ref(null);

function change(file) {
    fileData.value = file[0];
    emits('uploaded', fileData.value);
}
</script>
<template>
    <div>
        <div v-if="type === 'button'">
            <label
                class="inline-flex items-center gap-2.5 cursor-pointer text-xs font-semibold rounded-full px-3 mt-1 py-2.5 border border-medium-dark"
                :class="[
                    disabled
                        ? 'opacity-70 cursor-not-allowed text-gray-500 outline-gray-200'
                        : 'cursor-pointer',
                    buttonClass,
                ]"
                :for="id"
            >
                {{ buttonText }}
                <input
                    :id="id"
                    :accept="fileTypes"
                    class="absolute left-0 w-full h-full opacity-0 !cursor-pointer disabled:cursor-not-allowed"
                    :disabled="disabled"
                    :required="required"
                    type="file"
                    @change="change($event.target.files)"
                />
            </label>
        </div>
        <div v-else>
            <label :class="labelClass">
                {{ label }}
                <span v-if="required" class="text-red-500">*</span>
            </label>
            <div class="relative">
                <input
                    :id="id"
                    :accept="fileTypes"
                    class="absolute w-full h-full opacity-0 cursor-pointer disabled:cursor-not-allowed"
                    :disabled="disabled"
                    :required="required"
                    type="file"
                    @change="change($event.target.files)"
                />

                <label
                    class="leading-6 mt-1 block py-2 px-3 mr-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent sm:text-sm sm:leading-5"
                    :class="[
                        disabled
                            ? 'opacity-70 cursor-not-allowed text-gray-500 outline-gray-200'
                            : '',
                        error ? 'text-red-900 border border-red-300 placeholder:text-red-300' : '',
                        inputClass,
                    ]"
                    :for="id"
                >
                    {{ attachment === null ? 'No file chosen ' : attachment.name }}
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
        </div>
        <p v-if="helpText" class="mt-2 text-sm text-gray-500">{{ helpText }}</p>
        <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>
    </div>
</template>
