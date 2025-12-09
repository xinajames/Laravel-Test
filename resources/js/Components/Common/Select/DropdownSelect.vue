<script setup>
import { v4 as uuid } from 'uuid';
import { ref, watch } from 'vue';

defineOptions({
    inheritAttrs: false,
});

const props = defineProps({
    disabled: Boolean,
    error: { type: String, default: null },
    id: {
        type: String,
        default() {
            return `select-input-${uuid}`;
        },
    },
    helpText: { type: String, default: null },
    label: String,
    customClass: { type: String, default: '' },
    required: { type: Boolean, default: false },
    value: [String, Number, Boolean, Object],
});

const emit = defineEmits(['update:modelValue']);

const input = ref();
const selected = ref(props.value);

watch(selected, (selected) => {
    emit('update:modelValue', selected);
});

watch(
    () => props.value,
    (value) => {
        selected.value = value;
    }
);

function focus() {
    input.value.focus();
}

function select() {
    input.value.select();
}
</script>
<template>
    <div>
        <div class="flex justify-between">
            <label v-if="label" :for="id" class="block text-sm/6 font-medium text-gray-900">
                {{ label }}
                <span v-if="required" class="text-red-500">*</span>
            </label>
        </div>
        <div class="mt-1 relative rounded-md">
            <select
                :id="id"
                ref="input"
                v-model="selected"
                :class="[{ 'bg-gray-100 text-gray-500': disabled, error }, customClass]"
                :disabled="disabled"
                :required="required"
                class="block w-full rounded-md bg-white px-3 py-1.5 text-base outline-none focus:ring-0 focus:ring-primary focus:border-primary sm:text-sm/6 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500"
                v-bind="$attrs"
                @select="$emit('update:modelValue', selected)"
            >
                <slot />
            </select>
        </div>
        <p v-if="helpText" class="mt-2 text-sm text-gray-500">{{ helpText }}</p>
        <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>
    </div>
</template>
