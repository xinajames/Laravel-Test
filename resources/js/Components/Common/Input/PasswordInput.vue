<script setup>
import { v4 as uuid } from 'uuid';
import { ref } from 'vue';

defineEmits(['update:modelValue', 'blur', 'keyup']);

defineProps({
    id: {
        type: String,
        default() {
            return `password-input-${uuid()}`;
        },
    },
    disabled: { type: Boolean, default: false },
    error: { type: String, default: null },
    inputClass: { type: String, default: '' },
    label: { type: String, default: null },
    maxLength: { type: String, default: null },
    minLength: { type: String, default: null },
    placeholder: { type: String, default: null },
    required: { type: Boolean, default: false },
    modelValue: [String, Number],
});

const type = ref('password');

function updateType(value) {
    type.value = value;
}
</script>
<template>
    <div>
        <div class="flex justify-between">
            <label v-if="label" :for="id" class="block text-sm/6 font-medium text-gray-900">
                {{ label }}
                <span v-if="required" class="text-red-500">*</span>
            </label>
        </div>
        <div class="mt-1 relative">
            <input
                class="block w-full rounded-md bg-white px-3 py-1.5 text-base outline-none focus:ring-0 sm:text-sm/6 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500"
                :class="[
                    error
                        ? 'text-red-900  focus:ring-red-600 focus:border-red-600 placeholder:text-red-300'
                        : 'text-gray-900 focus:ring-primary focus:border-primary placeholder:text-gray-400',
                    inputClass,
                ]"
                :id="id"
                :disabled="disabled"
                :maxlength="maxLength"
                :minlength="minLength"
                :placeholder="placeholder"
                :required="required"
                :type="type"
                :value="modelValue"
                @input="$emit('update:modelValue', $event.target.value)"
                @blur="$emit('blur', $event.target.value)"
                @keyup="$emit('keyup', $event.target.value)"
            />
            <div class="absolute right-2 top-2 pl-1">
                <div v-if="type === 'password'" class="cursor-pointer" @click="updateType('text')">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        class="size-6"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                        />
                    </svg>
                </div>
                <div v-else class="cursor-pointer" @click="updateType('password')">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        class="size-6"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"
                        />
                    </svg>
                </div>
            </div>
        </div>
        <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>
    </div>
</template>
