<script setup>
const emit = defineEmits(['update:modelValue']);

defineProps({
    modelValue: String,
    placeholder: { type: String, default: '' },
    size: { type: String, default: '' },
    customClass: { type: String, default: '' },
});
</script>

<template>
    <div>
        <label class="sr-only">Search</label>
        <div class="relative flex items-center">
            <div
                aria-hidden="true"
                class="absolute inset-y-0 left-3 flex items-center pointer-events-none"
            >
                <svg
                    class="w-5 h-5 text-gray-400"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.5"
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </div>
            <input
                :class="[
                    size === 'large' ? 'pl-10 py-3' : 'pl-10 py-2',
                    'w-full bg-white text-sm border border-gray-300 rounded-full shadow-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary sm:text-sm',
                    $attrs.class,
                    customClass,
                ]"
                v-bind="$attrs"
                :placeholder="placeholder"
                autocomplete="off"
                type="search"
                @input="emit('update:modelValue', $event.target.value)"
            />
        </div>
    </div>
</template>
