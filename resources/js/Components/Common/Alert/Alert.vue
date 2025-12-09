<script setup>
import { ref, computed } from 'vue';
import {
    CheckCircleIcon,
    XCircleIcon,
    XMarkIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
} from '@heroicons/vue/20/solid';

// Define the props
const props = defineProps({
    type: {
        type: String,
        default: 'info', // or "success", "error", "warning"
        validator: (value) => ['success', 'error', 'warning', 'info'].includes(value),
    },
    title: {
        type: String,
        default: '',
    },
    message: {
        type: String,
        default: '',
    },
    actions: {
        type: Array,
        default: () => [], // e.g. [{ label: 'View status', handler: () => {...} }]
    },
    dismissible: {
        type: Boolean,
        default: false,
    },
});

// Setup a local state for whether the alert is visible
const visible = ref(true);
const close = () => {
    visible.value = false;
};

// Map each alert "type" to a color scheme & icon
const alertTypes = {
    success: {
        icon: CheckCircleIcon,
        bgColor: 'bg-green-50',
        iconColor: 'text-green-400',
        textColor: 'text-green-800',
        textColorSecondary: 'text-green-700',
        actionBgColor: 'bg-green-50',
        actionTextColor: 'text-green-800',
        dismissIconColor: 'text-green-500',
        hoverBgColor: 'hover:bg-green-100',
        ringColor: 'focus:ring-green-600',
        ringOffsetColor: 'focus:ring-offset-green-50',
    },
    error: {
        icon: XCircleIcon,
        bgColor: 'bg-red-50',
        iconColor: 'text-red-400',
        textColor: 'text-red-800',
        textColorSecondary: 'text-red-700',
        actionBgColor: 'bg-red-50',
        actionTextColor: 'text-red-800',
        dismissIconColor: 'text-red-500',
        hoverBgColor: 'hover:bg-red-100',
        ringColor: 'focus:ring-red-600',
        ringOffsetColor: 'focus:ring-offset-red-50',
    },
    warning: {
        icon: ExclamationTriangleIcon,
        bgColor: 'bg-yellow-50',
        iconColor: 'text-yellow-400',
        textColor: 'text-yellow-800',
        textColorSecondary: 'text-yellow-700',
        actionBgColor: 'bg-yellow-50',
        actionTextColor: 'text-yellow-800',
        dismissIconColor: 'text-yellow-500',
        hoverBgColor: 'hover:bg-yellow-100',
        ringColor: 'focus:ring-yellow-600',
        ringOffsetColor: 'focus:ring-offset-yellow-50',
    },
    info: {
        icon: InformationCircleIcon,
        bgColor: 'bg-blue-50',
        iconColor: 'text-blue-400',
        textColor: 'text-blue-800',
        textColorSecondary: 'text-blue-700',
        actionBgColor: 'bg-blue-50',
        actionTextColor: 'text-blue-800',
        dismissIconColor: 'text-blue-500',
        hoverBgColor: 'hover:bg-blue-100',
        ringColor: 'focus:ring-blue-600',
        ringOffsetColor: 'focus:ring-offset-blue-50',
    },
};

// Compute the style object based on the current alert "type"
const alertStyle = computed(() => {
    return alertTypes[props.type] || alertTypes.info;
});
</script>

<template>
    <div v-if="visible" :class="['rounded-md p-4', alertStyle.bgColor]">
        <div class="flex items-start">
            <!-- Icon Section -->
            <div class="shrink-0 flex-none">
                <component
                    :is="alertStyle.icon"
                    class="size-5 mt-0.5"
                    :class="alertStyle.iconColor"
                    aria-hidden="true"
                />
            </div>

            <!-- Text, Title, Message, Slot -->
            <div class="ml-3 flex-1 min-w-0">
                <!-- Title -->
                <h3
                    v-if="title"
                    class="text-sm font-medium leading-5"
                    :class="alertStyle.textColor"
                >
                    {{ title }}
                </h3>

                <!-- Description/Message -->
                <div
                    v-if="message"
                    :class="[
                        title ? 'mt-2' : 'mt-0',
                        'text-sm leading-5',
                        alertStyle.textColorSecondary,
                    ]"
                >
                    <p>{{ message }}</p>
                </div>

                <!-- Default slot for custom content -->
                <div v-if="$slots.default" :class="[title || message ? 'mt-2' : 'mt-0']">
                    <slot />
                </div>

                <!-- Actions (e.g. buttons) -->
                <div v-if="actions && actions.length" class="mt-4">
                    <div class="-mx-2 -my-1.5 flex">
                        <button
                            v-for="(action, index) in actions"
                            :key="index"
                            type="button"
                            @click="action.handler"
                            :class="[
                                'rounded-md px-2 py-1.5 text-sm font-medium focus:outline-none focus:ring-2',
                                alertStyle.actionBgColor,
                                alertStyle.actionTextColor,
                                alertStyle.hoverBgColor,
                                alertStyle.ringColor,
                                alertStyle.ringOffsetColor,
                            ]"
                        >
                            {{ action.label }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Dismiss Button -->
            <div v-if="dismissible" class="ml-auto pl-3 shrink-0 flex-none">
                <div class="-mx-1.5 -my-1.5">
                    <button
                        type="button"
                        @click="close"
                        :class="[
                            'inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2',
                            alertStyle.bgColor,
                            alertStyle.dismissIconColor,
                            alertStyle.hoverBgColor,
                            alertStyle.ringColor,
                            alertStyle.ringOffsetColor,
                        ]"
                    >
                        <span class="sr-only">Dismiss</span>
                        <XMarkIcon class="size-5" aria-hidden="true" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.size-5 {
    height: 1.25rem; /* h-5 */
    width: 1.25rem; /* w-5 */
}
</style>
