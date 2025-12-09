<script setup>
import { computed } from 'vue';
import { XMarkIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    // The layout style to use
    variant: {
        type: String,
        default: 'simple', // 'simple' | 'condensed' | 'actionsBelow' | 'splitButtons' | 'buttonsBelow'
        validator: (val) =>
            ['simple', 'condensed', 'actionsBelow', 'splitButtons', 'buttonsBelow'].includes(val),
    },
    // Text for the title
    title: {
        type: String,
        default: '',
    },
    // Text for the message/description
    message: {
        type: String,
        default: '',
    },
    // A leading icon component (e.g., CheckCircleIcon, InboxIcon, etc.) or null
    leadingIcon: {
        type: [Object, Function, null],
        default: null,
    },
    // A leading image path/url or empty
    leadingImage: {
        type: String,
        default: '',
    },
    /**
     * Array of action button objects, e.g.:
     * actions = [
     *   {
     *     label: "Undo",
     *     classes: "text-indigo-600 hover:text-indigo-500",
     *     onClick: () => console.log('Undo clicked'),
     *   },
     *   ...
     * ]
     */
    actions: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['update:show', 'action']);

// Check if we have an icon or image
const leadingExists = computed(() => {
    return !!(props.leadingIcon || props.leadingImage);
});

// Close (hide) the toast
function close() {
    emit('update:show', false);
}

// Handle an action's click
function actionClicked(action, index) {
    // If the action object has an onClick callback, call it
    if (typeof action.onClick === 'function') {
        action.onClick();
    }
    // Or you could emit an event with the action data or index
    emit('action', { action, index });
}

// Some default classes
const defaultButtonClass =
    'inline-flex rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 px-2 py-1 text-sm font-medium';
const linkButtonClass =
    'rounded-md bg-white text-sm font-medium text-indigo-600 hover:text-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2';
</script>
<template>
    <div
        aria-live="assertive"
        class="pointer-events-none fixed inset-0 flex items-end px-4 py-6 sm:items-start sm:p-6"
    >
        <div class="flex w-full flex-col items-center space-y-4 sm:items-end">
            <transition
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="show"
                    :class="[
                        'pointer-events-auto bg-white shadow-lg ring-1 ring-black/5',
                        variant === 'splitButtons'
                            ? 'flex w-full max-w-md divide-x divide-gray-200 rounded-lg'
                            : 'w-full max-w-sm rounded-lg overflow-hidden',
                    ]"
                >
                    <!-- SIMPLE -->
                    <template v-if="variant === 'simple'">
                        <div class="p-4">
                            <div class="flex items-start">
                                <!-- Leading icon/image (optional) -->
                                <div v-if="leadingExists" class="shrink-0">
                                    <component
                                        v-if="leadingIcon"
                                        :is="leadingIcon"
                                        class="size-6 text-green-400"
                                        aria-hidden="true"
                                    />
                                    <img
                                        v-else-if="leadingImage"
                                        :src="leadingImage"
                                        alt="Leading image"
                                        class="size-10 rounded-full"
                                    />
                                </div>
                                <div :class="['ml-3', leadingExists ? 'w-0 flex-1 pt-0.5' : '']">
                                    <p v-if="title" class="text-sm font-medium text-gray-900">
                                        {{ title }}
                                    </p>
                                    <p v-if="message" class="mt-1 text-sm text-gray-500">
                                        {{ message }}
                                    </p>
                                </div>
                                <!-- Action buttons: typically none in simple, but we can allow them -->
                                <div class="ml-4 flex shrink-0">
                                    <button
                                        v-for="(action, index) in actions"
                                        :key="'simple-action-' + index"
                                        type="button"
                                        :class="action.classes || defaultButtonClass"
                                        @click="actionClicked(action, index)"
                                    >
                                        {{ action.label }}
                                    </button>
                                    <!-- Close button -->
                                    <button
                                        type="button"
                                        @click="close"
                                        class="inline-flex rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    >
                                        <span class="sr-only">Close</span>
                                        <XMarkIcon class="size-5" aria-hidden="true" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- CONDENSED -->
                    <template v-else-if="variant === 'condensed'">
                        <div class="p-4">
                            <div class="flex items-center">
                                <!-- TODO: add leading icon/image -->
                                <div class="flex w-0 flex-1 justify-between">
                                    <p
                                        v-if="title"
                                        class="w-0 flex-1 text-sm font-medium text-gray-900"
                                    >
                                        {{ title }}
                                    </p>
                                    <!-- Usually a single link in the example, but we support multiple actions if desired -->
                                    <button
                                        v-for="(action, index) in actions"
                                        :key="'cond-action-' + index"
                                        type="button"
                                        :class="action.classes || linkButtonClass"
                                        @click="actionClicked(action, index)"
                                    >
                                        {{ action.label }}
                                    </button>
                                </div>
                                <div class="ml-4 flex shrink-0">
                                    <button
                                        type="button"
                                        @click="close"
                                        class="inline-flex rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    >
                                        <span class="sr-only">Close</span>
                                        <XMarkIcon class="size-5" aria-hidden="true" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- WITH ACTIONS BELOW -->
                    <template v-else-if="variant === 'actionsBelow'">
                        <div class="p-4">
                            <div class="flex items-start">
                                <!-- Leading icon/image (optional) -->
                                <div v-if="leadingExists" class="shrink-0">
                                    <component
                                        v-if="leadingIcon"
                                        :is="leadingIcon"
                                        class="size-6 text-gray-400"
                                        aria-hidden="true"
                                    />
                                    <img
                                        v-else-if="leadingImage"
                                        :src="leadingImage"
                                        alt="Leading image"
                                        class="size-10 rounded-full"
                                    />
                                </div>
                                <div class="ml-3 w-0 flex-1 pt-0.5">
                                    <p v-if="title" class="text-sm font-medium text-gray-900">
                                        {{ title }}
                                    </p>
                                    <p v-if="message" class="mt-1 text-sm text-gray-500">
                                        {{ message }}
                                    </p>
                                    <!-- Buttons below the message, spaced horizontally -->
                                    <div class="mt-3 flex space-x-7">
                                        <button
                                            v-for="(action, index) in actions"
                                            :key="'below-action-' + index"
                                            type="button"
                                            :class="action.classes || linkButtonClass"
                                            @click="actionClicked(action, index)"
                                        >
                                            {{ action.label }}
                                        </button>
                                    </div>
                                </div>
                                <div class="ml-4 flex shrink-0">
                                    <button
                                        type="button"
                                        @click="close"
                                        class="inline-flex rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    >
                                        <span class="sr-only">Close</span>
                                        <XMarkIcon class="size-5" aria-hidden="true" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- WITH SPLIT BUTTONS -->
                    <template v-else-if="variant === 'splitButtons'">
                        <!-- Left side text -->
                        <div class="flex w-0 flex-1 items-center p-4">
                            <!-- TODO: add leading icon/image -->
                            <div class="w-full">
                                <p v-if="title" class="text-sm font-medium text-gray-900">
                                    {{ title }}
                                </p>
                                <p v-if="message" class="mt-1 text-sm text-gray-500">
                                    {{ message }}
                                </p>
                            </div>
                        </div>

                        <!-- Right side stacked buttons, top->bottom -->
                        <div class="flex">
                            <div class="flex flex-col divide-y divide-gray-200">
                                <!-- We expect 1 or 2 actions for the split style, but you can pass more if you like. -->
                                <div
                                    v-for="(action, index) in actions"
                                    :key="'split-action-' + index"
                                    class="flex h-0 flex-1"
                                >
                                    <button
                                        type="button"
                                        class="flex w-full items-center justify-center rounded-none border border-transparent px-4 py-3 text-sm font-medium focus:z-10 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        :class="
                                            action.classes ||
                                            'text-indigo-600 hover:text-indigo-500'
                                        "
                                        @click="actionClicked(action, index)"
                                    >
                                        {{ action.label }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- WITH BUTTONS BELOW -->
                    <template v-else-if="variant === 'buttonsBelow'">
                        <div class="p-4">
                            <div class="flex items-start">
                                <!-- Typically an avatar in the example -->
                                <div v-if="leadingExists" class="shrink-0 pt-0.5">
                                    <component
                                        v-if="leadingIcon"
                                        :is="leadingIcon"
                                        class="size-6 text-gray-400"
                                        aria-hidden="true"
                                    />
                                    <img
                                        v-else-if="leadingImage"
                                        :src="leadingImage"
                                        alt="Leading image"
                                        class="size-10 rounded-full"
                                    />
                                </div>
                                <div class="ml-3 w-0 flex-1">
                                    <p v-if="title" class="text-sm font-medium text-gray-900">
                                        {{ title }}
                                    </p>
                                    <p v-if="message" class="mt-1 text-sm text-gray-500">
                                        {{ message }}
                                    </p>
                                    <!-- Usually two buttons side by side -->
                                    <div class="mt-4 flex" v-if="actions.length">
                                        <button
                                            v-for="(action, index) in actions"
                                            :key="'below-btn-' + index"
                                            type="button"
                                            :class="[
                                                'inline-flex items-center rounded-md px-2.5 py-1.5 text-sm font-semibold shadow-sm',
                                                index === 0
                                                    ? 'bg-indigo-600 text-white hover:bg-indigo-500 focus-visible:outline-indigo-600'
                                                    : 'ml-3 bg-white text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50',
                                                action.classes || '',
                                            ]"
                                            @click="actionClicked(action, index)"
                                        >
                                            {{ action.label }}
                                        </button>
                                    </div>
                                </div>
                                <div class="ml-4 flex shrink-0">
                                    <button
                                        type="button"
                                        @click="close"
                                        class="inline-flex rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    >
                                        <span class="sr-only">Close</span>
                                        <XMarkIcon class="size-5" aria-hidden="true" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </transition>
        </div>
    </div>
</template>
