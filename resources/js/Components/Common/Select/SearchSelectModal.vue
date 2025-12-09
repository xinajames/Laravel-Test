<script setup>
import VSelect from 'vue3-select';
import 'vue3-select/dist/vue3-select.css';
import { ref, watch } from 'vue';

const emits = defineEmits(['update:modelValue', 'option:selected']);

const props = defineProps({
    options: Array,
    required: { type: Boolean, default: true },
    clear: { type: Boolean, default: false },
    value: { type: [String, Number, Boolean], default: null },
    errors: {
        type: Array,
        default: () => [],
    },
    selectable: { type: Function, default: null },
    disabled: { type: Boolean, default: false },
    label: { type: String, default: '' },
});

const selected = ref(props.value);

// Default selectable function if not provided
const defaultSelectable = (option) => true;

watch(selected, (selected) => {
    emits('update:modelValue', selected);
});

watch(
    () => props.value,
    (newValue) => {
        selected.value = newValue;
    }
);

function handleOptionSelected(selectedOption) {
    emits('option:selected', selectedOption);
}

function clearValue() {
    selected.value = null;
}

defineExpose({
    clearValue,
});
</script>
<template>
    <div>
        <label v-if="label" class="block text-sm/6 font-medium text-gray-900">
            {{ label }}
        </label>
        <v-select
            :disabled="disabled"
            :options="options"
            v-model="selected"
            :selectable="selectable || defaultSelectable"
            :reduce="(selected) => selected.id"
            @option:selected="handleOptionSelected"
            append-to-body
            placeholder="Select an option"
        >
            <template #search="{ attributes, events }">
                <input
                    class="vs__search"
                    :required="required && !selected"
                    v-bind="attributes"
                    v-on="events"
                    placeholder=""
                />
            </template>
        </v-select>
        <p v-if="errors.length" class="text-red-error mt-1">{{ errors[0] }}</p>
    </div>
</template>

<style>
.vs__dropdown-toggle {
    border-width: 1px !important;
    border-color: #d1d5db;
    border-radius: 0.375rem;
    height: 42px;
    line-height: 1.6;
    font-size: 1rem;
}

.vs__dropdown-option--selected {
    background: #a32130 !important;
    color: white !important;
}

.vs__dropdown-option--highlight {
    background: #a32130 !important;
    color: white !important;
}

.vs__dropdown-option {
    padding: 0.25rem 0.5rem 0.25rem 0.5rem;
    /* Top, Right, Bottom, Left */
}
</style>
