<script setup>
import { v4 as uuid } from 'uuid';

defineEmits(['update:modelValue', 'blur', 'keyup']);

defineProps({
    id: {
        type: String,
        default() {
            return `text-area-${uuid()}`;
        },
    },
    disabled: { type: Boolean, default: false },
    error: { type: String, default: null },
    inputClass: { type: String, default: '' },
    label: { type: String, default: null },
    placeholder: { type: String, default: null },
    rows: { type: String, default: '3' },
    required: { type: Boolean, default: false },
    modelValue: [String, Number],
});
</script>
<template>
    <div>
        <label v-if="label" :for="id" class="block text-sm/6 font-medium text-gray-900">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        <div class="mt-1">
            <textarea
                class="block w-full rounded-md bg-white px-3 py-1.5 text-base outline-none focus:ring-0 sm:text-sm/6 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500"
                :class="[
                    error
                        ? 'text-red-900  focus:ring-red-600 focus:border-red-600 placeholder:text-red-300'
                        : 'text-gray-900 focus:ring-primary focus:border-primary placeholder:text-gray-400',
                    inputClass,
                ]"
                :disabled="disabled"
                :id="id"
                :placeholder="placeholder"
                :required="required"
                :rows="rows"
                :value="modelValue"
                @input="$emit('update:modelValue', $event.target.value)"
                @blur="$emit('blur', $event.target.value)"
                @keyup="$emit('keyup', $event.target.value)"
            />
        </div>
        <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>
    </div>
</template>
