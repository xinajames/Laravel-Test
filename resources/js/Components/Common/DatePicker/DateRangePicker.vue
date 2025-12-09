<script setup>
import { ref, watch } from 'vue';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const props = defineProps({
    modelValue: {
        type: Array, // Array to store the start and end dates
        default: () => [], // Default to an empty array
    },
    id: {
        type: String,
        default: 'date-range-picker',
    },
    label: {
        type: String,
        default: 'Select a Date Range',
    },
    minDate: {
        type: String,
        default: null, // Minimum selectable date
    },
    maxDate: {
        type: String,
        default: null, // Maximum selectable date
    },
    enableTimePicker: {
        type: Boolean,
        default: false, // Enable or disable time picker
    },
    placeholder: {
        type: String,
        default: 'Select a date range', // Placeholder text
    },
    textInput: {
        type: Boolean,
        default: false, // Enable or disable text input
    },
});

const emit = defineEmits(['update:modelValue']);

// Internal state to manage the date range
const internalDateRange = ref([]); // No initial value

// Emit changes when the date range is updated
const updateModelValue = (value) => {
    emit('update:modelValue', value);
};

// Sync internal state with the prop `modelValue`
watch(
    () => props.modelValue,
    (newValue) => {
        // Ensure `newValue` is always an array
        internalDateRange.value = Array.isArray(newValue) ? newValue : [];
    }
);
</script>

<template>
    <div :class="['vue-date-range-picker']">
        <label :for="id" class="vue-date-picker__label">{{ label }}</label>
        <VueDatePicker
            v-model="internalDateRange"
            :id="id"
            range
            :min-date="minDate"
            :max-date="maxDate"
            :enable-time-picker="enableTimePicker"
            :placeholder="placeholder"
            :clearable="false"
            @update:modelValue="updateModelValue"
            :text-input="textInput"
        />
    </div>
</template>

<style scoped>
.vue-date-picker__label {
    font-size: 1rem;
    font-weight: bold;
}

:deep(.dp__active_date) {
    background-color: #a32130 !important;
    color: #fff !important;
}
:deep(.dp__today) {
    border-color: #a32130 !important;
}
:deep(.dp__pm_am_button) {
    background-color: #a32130 !important;
    color: #fff !important;
}
:deep(.dp__action_select[data-test-id='select-button']) {
    background-color: #a32130 !important;
    color: #fff !important;
}
:deep(.dp__range_start) {
    background-color: #a32130 !important;
    color: #fff !important;
}
:deep(.dp__range_end) {
    background-color: #a32130 !important;
    color: #fff !important;
}

:deep(.dp__action_button dp__action_cancel) {
    background-color: #a32130 !important;
    color: #fff !important;
}
:deep(.dp__action_button dp__action_apply) {
    background-color: #a32130 !important;
    color: #fff !important;
}

:deep(.dp__action_button.dp__action_cancel:hover) {
    border-color: #a32130 !important;
}
</style>
