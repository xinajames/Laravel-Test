<script setup>
const props = defineProps({
    id: {
        type: String,
        default: 'my-checkbox',
    },
    name: {
        type: String,
        default: 'my-checkbox',
    },
    checked: {
        type: Boolean,
        required: true,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    checkedColor: {
        type: String,
        default: '#4caf50',
    },
});

const emit = defineEmits(['update:checked']);

function onChange(e) {
    emit('update:checked', e.target.checked);
    e.target.blur();
}
</script>
<template>
    <div class="flex h-6 shrink-0 items-center hover:brightness-90 transition-all">
        <div class="group grid size-4 grid-cols-1">
            <input
                :id="id"
                :name="name"
                type="checkbox"
                :checked="checked"
                :disabled="disabled"
                @change="onChange"
                :class="[
                    'col-start-1 row-start-1 cursor-pointer transition-all appearance-none rounded-sm border',
                    'disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto',
                ]"
                :style="{
                    backgroundColor: checked ? checkedColor : '',
                    borderColor: checked ? checkedColor : 'rgba(209, 213, 219, 1)', // Default border color
                }"
            />
            <svg
                class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center"
                viewBox="0 0 14 14"
            >
                <path
                    class="opacity-0 group-has-checked:opacity-100"
                    d="M3 8L6 11L11 3.5"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
                <path
                    class="opacity-0 group-has-indeterminate:opacity-100"
                    d="M3 7H11"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>
        </div>
    </div>
</template>

<style>
.hover\:brightness-90:hover {
    filter: brightness(90%);
}
</style>
