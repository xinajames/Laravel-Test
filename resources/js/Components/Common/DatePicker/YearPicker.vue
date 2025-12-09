<script setup>
import { ref, defineProps, defineEmits, watch } from 'vue';
import VueDatePicker from '@vuepic/vue-datepicker'; // VueDatePicker component
import '@vuepic/vue-datepicker/dist/main.css'; // VueDatePicker styles

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: new Date().getFullYear(),
    },
    placeholder: {
        type: String,
        default: 'Select Year',
    },
    minDate: {
        type: String,
        default: null,
    },
    maxDate: {
        type: String,
        default: null,
    },
    id: {
        type: String,
        default: 'year-picker',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    required: {
        type: Boolean,
        default: false,
    },
    textInput: {
        type: Boolean,
        default: false,
    },
    typeable: {
        type: Boolean,
        default: true,
    },
    label: {
        type: String,
        default: '',
    },
    yearRange: {
        type: Array,
        default: () => [1900, new Date().getFullYear()],
    },
    allYears: {
        type: Boolean,
        default: false,
    },
});

const emits = defineEmits(['update:modelValue']);

const internalYear = ref(props.modelValue);

// Watch for internalYear changes and emit updates to parent component
watch(internalYear, (newYear) => {
    emits('update:modelValue', parseInt(newYear));
});
</script>

<!-- YearPicker.vue -->
<template>
    <div :class="['vue-date-picker']">
        <label :for="id" class="vue-date-picker__label text-sm/6 font-medium text-gray-900">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        <div
            class="relative outline-none focus:ring-0 focus:ring-primary focus:border-primary text-sm text-black-darkest font-heading mt-1"
        >
            <VueDatePicker
                :id="id"
                v-model="internalYear"
                year-picker
                :placeholder="placeholder"
                :minDate="minDate"
                :maxDate="maxDate"
                required
                :max="maxDate"
                :min="minDate"
                :required="required"
                :text-input="true"
                :label="label"
                :clearable="false"
                hide-input-icon
                teleport="body"
                :year-range="allYears ? undefined : yearRange"
                :start-date="new Date()"
            />
            <div
                :class="disabled ? 'cursor-not-allowed' : 'cursor-pointer'"
                class="absolute inset-y-0 right-0 pr-3 flex items-center"
            >
                <svg
                    fill="none"
                    height="20"
                    viewBox="0 0 20 20"
                    width="20"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="M6.66667 5.83333V2.5M13.3333 5.83333V2.5M5.83333 9.16667H14.1667M4.16667 17.5H15.8333C16.7538 17.5 17.5 16.7538 17.5 15.8333V5.83333C17.5 4.91286 16.7538 4.16667 15.8333 4.16667H4.16667C3.24619 4.16667 2.5 4.91286 2.5 5.83333V15.8333C2.5 16.7538 3.24619 17.5 4.16667 17.5Z"
                        stroke="#9CA3AF"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                    />
                </svg>
            </div>
        </div>
    </div>
</template>

<style>
.dp__active_date {
    background-color: #a32130 !important;
    color: #fff !important;
}

.dp__today {
    border-color: #a32130 !important;
}

.dp__pm_am_button {
    background-color: #a32130 !important;
    color: #fff !important;
}

.dp__action_select[data-test-id='select-button'] {
    background-color: #a32130 !important;
    color: #fff !important;
}

.dp__range_start {
    background-color: #a32130 !important;
    color: #fff !important;
}

.dp__range_end {
    background-color: #a32130 !important;
    color: #fff !important;
}

.dp__action_button.dp__action_cancel {
    background-color: #a32130 !important;
    color: #fff !important;
}

.dp__action_button.dp__action_apply {
    background-color: #a32130 !important;
    color: #fff !important;
}

.dp__action_button.dp__action_cancel:hover {
    border-color: #a32130 !important;
}

.dp__overlay_cell_active {
    background: #a32130 !important;
}

input:focus {
    outline: none !important;
    border-color: #a32130 !important;
    box-shadow: 0 0 0 0.2px #a32130 !important;
}
</style>
