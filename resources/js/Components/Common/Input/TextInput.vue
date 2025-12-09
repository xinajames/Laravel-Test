<script setup>
import { v4 as uuid } from 'uuid';

defineEmits(['update:modelValue', 'blur', 'keyup']);

defineProps({
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
    labelClass: { type: String, default: '' },
    maxLength: { type: String, default: null },
    minLength: { type: String, default: null },
    optional: { type: Boolean, default: false },
    placeholder: { type: String, default: null },
    required: { type: Boolean, default: false },
    type: {
        type: String,
        default: 'text',
    },
    modelValue: [String, Number],
});
</script>

<template>
    <div>
        <div class="flex justify-between">
            <label
                v-if="label"
                :for="id"
                class="block text-sm/6 font-medium text-gray-900"
                :class="labelClass"
            >
                {{ label }}
                <span v-if="required" class="text-red-500">*</span>
            </label>
            <span v-if="optional" class="text-sm/6 text-gray-500">Optional</span>
        </div>
        <div class="mt-1 relative">
            <input
                :id="id"
                :class="[
                    error
                        ? 'text-red-900  focus:ring-red-600 focus:border-red-600 placeholder:text-red-300'
                        : 'text-gray-900 focus:ring-primary focus:border-primary placeholder:text-gray-400',
                    inputClass,
                ]"
                :disabled="disabled"
                :maxlength="maxLength"
                :minlength="minLength"
                :placeholder="placeholder"
                :required="required"
                :type="type"
                :value="modelValue"
                class="block w-full rounded-md bg-white outline-none focus:ring-0 px-3 py-1.5 text-base sm:text-sm/6 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500"
                @blur="$emit('blur', $event.target.value)"
                @input="$emit('update:modelValue', $event.target.value)"
                @keyup="$emit('keyup', $event.target.value)"
            />
            <div class="absolute right-2 top-2 pl-1">
                <slot name="icon" />
                <svg
                    v-if="error"
                    class="size-6 text-red-500"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.5"
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </div>
        </div>
        <p v-if="helpText" class="mt-2 text-sm text-gray-500">{{ helpText }}</p>
        <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>
    </div>
</template>
