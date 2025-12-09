<script setup>
import { ref, watch } from 'vue';
import { format as formatDate } from 'date-fns'; // Import date-fns for formatting
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    id: {
        type: String,
        default: 'date-picker',
    },
    label: {
        type: String,
        default: 'Select a Date',
    },
    minDate: {
        type: String,
        default: null,
    },
    maxDate: {
        type: String,
        default: null,
    },
    typeable: {
        type: Boolean,
        default: false,
    },
    placeholder: {
        type: String,
        default: 'Pick a date',
    },
    format: {
        type: String,
        default: 'yyyy-MM-dd hh:mm a', // Correct 12-hour time format
    },
    textInput: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);
const dateValue = ref(props.modelValue ? new Date(props.modelValue) : null);

const updateModelValue = (value) => {
    // Format the Date object into a String using the provided format
    const formattedValue = value ? formatDate(value, props.format) : '';
    emit('update:modelValue', formattedValue);
};

// Sync dateValue with props.modelValue
watch(
    () => props.modelValue,
    (newValue) => {
        dateValue.value = newValue ? new Date(newValue) : null;
    }
);
</script>
<template>
    <div :class="['vue-date-time-picker']">
        <label :for="id" class="vue-date-picker__label">{{ label }}</label>
        <VueDatePicker
            v-model="dateValue"
            :id="id"
            :min="minDate"
            :max="maxDate"
            :typeable="typeable"
            :placeholder="placeholder"
            :format="format"
            :is-24="false"
            :clearable="false"
            time-picker-inline
            @update:modelValue="updateModelValue"
            :text-input="textInput"
        />
    </div>
</template>

<style scoped>
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
