<script setup>
const {
    type,
    href,
    as,
    target,
    disabled,
    class: customClass,
} = defineProps({
    type: {
        type: String,
        default: 'button',
    },
    href: {
        type: String,
        default: null,
    },
    as: {
        type: String,
        default: 'button',
    },
    target: {
        type: String,
        default: '_self',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    class: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <component
        :is="href ? 'a' : as"
        :class="[
            'inline-flex items-center gap-x-1.5 rounded px-3 py-2 text-sm font-semibold shadow-sm text-gray-900 bg-white ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-300',
            disabled ? 'opacity-50 cursor-not-allowed' : '',
            customClass,
        ]"
        :disabled="disabled"
        :href="href"
        :target="href ? target : undefined"
        :type="!href && as === 'button' ? type : undefined"
    >
        <slot>Button Text</slot>
    </component>
</template>
