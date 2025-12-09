<script setup>
import { v4 as uuid } from 'uuid';
import { computed } from 'vue';

defineEmits(['update:modelValue', 'blur', 'keyup']);

const props = defineProps({
    id: {
        type: String,
        default() {
            return `text-input-${uuid()}`;
        },
    },
    disabled: { type: Boolean, default: false },
    error: { type: String, default: null },
    helpText: { type: String, default: null },
    inputClass: { type: String, default: '' },
    label: { type: String, default: null },
    maxLength: { type: String, default: null },
    minLength: { type: String, default: null },
    optional: { type: Boolean, default: false },
    placeholder: { type: String, default: null },
    required: { type: Boolean, default: false },
    modelValue: [String, Number],
    showLeftSymbol: { type: Boolean, default: false },
    leftSymbol: { type: String, default: '' },
    showRightIcon: { type: Boolean, default: false },
    type: {
        type: String,
        default: 'number',
    },
});

// Dynamically calculate padding based on leftSymbol length
const leftPaddingClass = computed(() => {
    if (!props.showLeftSymbol || !props.leftSymbol) return '';

    const length = props.leftSymbol.length;
    if (length <= 1) return 'pl-8';
    if (length <= 3) return 'pl-10';
    if (length <= 5) return 'pl-12';
    return 'pl-14'; // Adjust for longer symbols
});
</script>

<template>
    <div>
        <div class="flex justify-between">
            <label v-if="label" :for="id" class="block text-sm/6 font-medium text-gray-900">
                {{ label }}
                <span v-if="required" class="text-red-500">*</span>
            </label>
            <span v-if="optional" class="text-sm/6 text-gray-500">Optional</span>
        </div>
        <div class="mt-1 relative flex items-center">
            <!-- Left Symbol -->
            <div
                v-if="showLeftSymbol && leftSymbol"
                class="absolute left-3 text-gray-900 whitespace-nowrap sm:text-sm/6"
            >
                {{ leftSymbol }}
            </div>

            <input
                class="block w-full rounded-md bg-white px-3 py-1.5 text-base outline-none focus:ring-0 sm:text-sm/6 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500"
                :class="[
                    error
                        ? 'text-red-900  focus:ring-red-600 focus:border-red-600 placeholder:text-red-300'
                        : 'text-gray-900 focus:ring-primary focus:border-primary placeholder:text-gray-400',
                    leftPaddingClass,
                    showRightIcon ? 'pr-8' : '',
                    inputClass,
                ]"
                :id="id"
                :disabled="disabled"
                :maxlength="maxLength"
                :minlength="minLength"
                :placeholder="placeholder"
                :required="required"
                :type="type"
                :value="modelValue"
                @input="$emit('update:modelValue', $event.target.value)"
                @blur="$emit('blur', $event.target.value)"
                @keyup="$emit('keyup', $event.target.value)"
            />

            <!-- Right Icon -->
            <div v-if="showRightIcon" class="absolute right-2 pl-1">
                <slot name="icon" />
                <svg
                    v-if="error"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="size-6 text-red-500"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"
                    />
                </svg>
            </div>
        </div>
        <p v-if="helpText" class="mt-2 text-sm text-gray-500">{{ helpText }}</p>
        <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>
    </div>
</template>
