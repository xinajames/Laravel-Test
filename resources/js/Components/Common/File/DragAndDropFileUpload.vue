<script setup>
import { v4 as uuid } from 'uuid';
import { ref } from 'vue';

const emits = defineEmits(['uploaded']);

const props = defineProps({
    attachments: { type: Array, default: () => [] },
    id: {
        type: String,
        default() {
            return `drag-and-drop-file-upload-${uuid()}`;
        },
    },
    disabled: { type: Boolean, default: false },
    error: { type: String, default: null },
    fileTypes: { type: String, default: '.jpg,.jpeg,.png,.pdf,.xlsx,.xls,.csv,.txt' },
    helpText: { type: String, default: null },
    label: { type: String, default: 'Upload' },
    labelClass: { type: String, default: 'text-sm/6 font-medium text-gray-900' },
    required: { type: Boolean, default: false },
    customClass: { type: String, default: 'py-6' },
    type: { type: String, default: '' },
    iconType: { type: String, default: 'photo' },
    iconClass: { type: String, default: '' },
    textLabel: { type: String, default: '' },
    multiple: { type: Boolean, default: true },
});

const file = ref(null);
const fileList = ref([]);

function dragleave(event) {
    event.currentTarget.classList.add('bg-accent');
    event.currentTarget.classList.remove('bg-purple-200');
}

function dragover(event) {
    event.preventDefault();
    if (!event.currentTarget.classList.contains('bg-purple-200')) {
        event.currentTarget.classList.remove('bg-accent');
        event.currentTarget.classList.add('bg-purple-50');
    }
}

function drop(event) {
    if (!props.disabled) {
        event.preventDefault();

        const droppedFiles = Array.from(event.dataTransfer.files);
        const selected = props.multiple ? droppedFiles : droppedFiles.slice(0, 1);

        fileList.value = selected;
        emits('uploaded', props.multiple ? selected : selected[0]);

        event.currentTarget.classList.add('bg-accent');
        event.currentTarget.classList.remove('bg-purple-200');
    }
}

function onChange(event) {
    const filesArray = Array.from(file.value.files);
    fileList.value = props.multiple ? filesArray : filesArray.slice(0, 1);
    emits('uploaded', props.multiple ? fileList.value : fileList.value[0]);

    if (event) event.target.value = null;
}

function resetFileInput() {
    file.value = null;
    fileList.value = [];
}

defineExpose({
    fileList,
    resetFileInput,
});
</script>

<template>
    <div>
        <label v-if="textLabel" :for="id" class="block text-sm/6 font-medium text-gray-900">
            {{ textLabel }}
            <span v-if="required" class="text-red-500">*</span>
        </label>

        <!-- Single Line Type -->
        <div
            v-if="type === 'single_line'"
            :class="[disabled ? 'opacity-70 cursor-not-allowed' : 'opacity-100', customClass]"
            class="flex justify-between items-center rounded-2xl border border-dashed border-gray-300"
            @dragleave="dragleave"
            @dragover="dragover"
            @drop="drop"
        >
            <div>
                <label
                    :class="[disabled ? 'cursor-not-allowed' : 'cursor-pointer', labelClass]"
                    :for="id"
                    class="font-medium text-sm"
                >
                    <span>{{ label }}&nbsp;</span>
                    <input
                        :id="id"
                        ref="file"
                        :accept="fileTypes"
                        :disabled="disabled"
                        :multiple="multiple"
                        :name="`fields[${id}][]`"
                        :required="required ? fileList.length < 1 && attachments.length < 1 : false"
                        class="w-px h-px opacity-0 overflow-hidden absolute disabled:cursor-not-allowed"
                        type="file"
                        @change="onChange($event)"
                    />
                </label>
                <span class="text-gray-600 font-medium text-sm">or drag and drop</span>
            </div>
            <svg
                v-if="iconType === 'document'"
                fill="none"
                height="48"
                viewBox="0 0 48 48"
                width="48"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                    d="M18 26H30M24 20L24 32M34 42H14C11.7909 42 10 40.2091 10 38V10C10 7.79086 11.7909 6 14 6H25.1716C25.702 6 26.2107 6.21071 26.5858 6.58579L37.4142 17.4142C37.7893 17.7893 38 18.298 38 18.8284V38C38 40.2091 36.2091 42 34 42Z"
                    stroke="#9CA3AF"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                />
            </svg>

            <svg
                v-else
                :class="iconClass"
                fill="none"
                height="20"
                viewBox="0 0 20 20"
                width="20"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                    d="M3.33325 13.3334L3.33325 14.1667C3.33325 15.5475 4.45254 16.6667 5.83325 16.6667L14.1666 16.6667C15.5473 16.6667 16.6666 15.5475 16.6666 14.1667L16.6666 13.3334M13.3333 6.66675L9.99992 3.33342M9.99992 3.33342L6.66658 6.66675M9.99992 3.33342L9.99992 13.3334"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                />
            </svg>
        </div>

        <!-- Default Type -->
        <div
            v-else
            :class="disabled ? 'opacity-70 cursor-not-allowed' : 'opacity-100'"
            class="flex justify-center items-center content-stretch rounded-2xl border-2 border-dashed border-gray-300"
            @dragleave="dragleave"
            @dragover="dragover"
            @drop="drop"
        >
            <div :class="customClass" class="flex flex-col items-center mx-auto gap-1">
                <svg
                    v-if="iconType === 'document'"
                    fill="none"
                    height="48"
                    viewBox="0 0 48 48"
                    width="48"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="M18 26H30M24 20L24 32M34 42H14C11.7909 42 10 40.2091 10 38V10C10 7.79086 11.7909 6 14 6H25.1716C25.702 6 26.2107 6.21071 26.5858 6.58579L37.4142 17.4142C37.7893 17.7893 38 18.298 38 18.8284V38C38 40.2091 36.2091 42 34 42Z"
                        stroke="#9CA3AF"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                    />
                </svg>

                <svg
                    v-else
                    :class="iconClass"
                    fill="none"
                    height="48"
                    viewBox="0 0 48 48"
                    width="48"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="M28 8H12C10.9391 8 9.92172 8.42143 9.17157 9.17157C8.42143 9.92172 8 10.9391 8 12V32M8 32V36C8 37.0609 8.42143 38.0783 9.17157 38.8284C9.92172 39.5786 10.9391 40 12 40H36C37.0609 40 38.0783 39.5786 38.8284 38.8284C39.5786 38.0783 40 37.0609 40 36V28M8 32L17.172 22.828C17.9221 22.0781 18.9393 21.6569 20 21.6569C21.0607 21.6569 22.0779 22.0781 22.828 22.828L28 28M40 20V28M40 28L36.828 24.828C36.0779 24.0781 35.0607 23.6569 34 23.6569C32.9393 23.6569 31.9221 24.0781 31.172 24.828L28 28M28 28L32 32M36 8H44M40 4V12M28 16H28.02"
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                    />
                </svg>
                <div class="flex items-center gap-1 text-sm">
                    <label
                        :class="[disabled ? 'cursor-not-allowed' : 'cursor-pointer', labelClass]"
                        :for="id"
                        class="relative underline font-medium text-sm"
                    >
                        <span>{{ label }}&nbsp;</span>
                        <input
                            :id="id"
                            ref="file"
                            :accept="fileTypes"
                            :disabled="disabled"
                            :multiple="multiple"
                            :name="`fields[${id}][]`"
                            :required="
                                required ? fileList.length < 1 && attachments.length < 1 : false
                            "
                            class="w-px h-px opacity-0 overflow-hidden absolute disabled:cursor-not-allowed"
                            multiple
                            type="file"
                            @change="onChange($event)"
                        />
                    </label>
                    <p class="text-gray-600 font-medium">or drag and drop</p>
                </div>
                <p v-if="helpText" class="text-xs text-gray-500">{{ helpText }}</p>
            </div>
        </div>
    </div>
</template>
