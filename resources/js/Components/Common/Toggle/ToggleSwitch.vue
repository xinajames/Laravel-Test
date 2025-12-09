<script setup>
import { computed } from 'vue';
import { Switch, SwitchGroup, SwitchLabel, SwitchDescription } from '@headlessui/vue';

// Props for dynamic behavior
const props = defineProps({
    modelValue: {
        type: Boolean,
        required: true,
    },
    enabledColor: {
        type: String,
        default: 'bg-indigo-600', // Color when enabled
    },
    disabledColor: {
        type: String,
        default: 'bg-gray-200', // Color when disabled
    },
    label: {
        type: String,
        default: '', // Default label text
    },
    subText: {
        type: String, // Optional subtext
        default: '', // Empty by default, making it optional
    },
    description: {
        type: String, // Optional description
        default: '', // Empty by default, making it optional
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

// Emit updated value
const emit = defineEmits(['update:modelValue']);

// Function to toggle the switch
const toggleSwitch = () => {
    emit('update:modelValue', !props.modelValue);
};

// Compute classes dynamically
const switchClasses = computed(() => [
    props.modelValue ? props.enabledColor : props.disabledColor,
    'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2',
]);

const thumbClasses = computed(() => [
    props.modelValue ? 'translate-x-5' : 'translate-x-0',
    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
]);
</script>

<template>
    <SwitchGroup as="div" class="flex items-center justify-between gap-3">
        <!-- Render the label only if it's provided -->
        <template v-if="label || subText || description">
            <span class="flex grow flex-col">
                <SwitchLabel as="span" class="ml-3 text-sm">
                    <span class="font-medium text-gray-900">{{ label }}</span>
                    <template v-if="subText">
                        {{ ' ' }}
                        <span class="text-gray-500">{{ subText }}</span>
                    </template>
                </SwitchLabel>
                <template v-if="description">
                    <SwitchDescription as="span" class="text-sm text-gray-500">
                        {{ description }}
                    </SwitchDescription>
                </template>
            </span>
        </template>

        <!-- The actual toggle switch -->
        <Switch
            class="disabled:cursor-not-allowed disabled:opacity-70"
            :class="switchClasses"
            :aria-checked="modelValue"
            :disabled="disabled"
            role="switch"
            @click="toggleSwitch"
        >
            <span class="sr-only">Toggle setting</span>
            <span aria-hidden="true" :class="thumbClasses" />
        </Switch>
    </SwitchGroup>
</template>
