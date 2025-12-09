<script setup>
import { computed } from 'vue';

const props = defineProps({
    id: { type: String, required: true },
    name: { type: String, required: true },
    value: { type: [String, Number, Object, Boolean] },
    modelValue: { type: [String, Number, Object, Boolean] },
    disabled: { type: Boolean, default: false },
    color: { type: String, default: '#4F46E5' },
});

const emit = defineEmits(['update:modelValue']);

function handleChange(event) {
    emit('update:modelValue', event.target.value);
    event.target.blur();
}

const radioStyles = computed(() => {
    return {
        '--radio-checked-border-color': props.color,
        '--radio-checked-bg-color': props.color,
        '--radio-focus-ring-color': props.color,
    };
});
</script>
<template>
    <input
        :id="id"
        :name="name"
        type="radio"
        :value="value"
        :checked="modelValue === value"
        :disabled="disabled"
        @change="handleChange"
        class="mt-1 w-4 h-4appearance-none rounded-full border bg-white focus:ring-0 disabled:cursor-not-allowed transition-colors duration-200"
        :style="radioStyles"
    />
</template>
<style scoped>
input[type='radio'] {
    border-color: var(--radio-border-color, #d1d5db); /* default border-gray-300 */
}

input[type='radio']:checked {
    border-color: var(--radio-checked-border-color);
    background-color: var(--radio-checked-bg-color);
}

input[type='radio']:focus {
    box-shadow: 0 0 0 3px rgba(66, 66, 66, 0.5);
    /* default indigo-500, make this dynamic next time */
}

input[type='radio']:disabled {
    border-color: #d1d5db; /* border-gray-300 */
    background-color: #f3f4f6; /* bg-gray-100 */
    cursor: not-allowed;
}
</style>
