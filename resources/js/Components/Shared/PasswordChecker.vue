<script setup>
import { CheckIcon } from '@heroicons/vue/24/outline/index.js';

defineProps({
    contains_eight_characters: Boolean,
    contains_number: Boolean,
    contains_uppercase: Boolean,
    contains_lowercase: Boolean,
    contains_symbol: Boolean,
    passwords_match: Boolean,
});
</script>

<template>
    <div class="relative flex-col justify-center">
        <div :class="{ is_text_valid: contains_eight_characters }" class="text-gray-500 text-sm">
            <div class="flex items-center">
                <check-icon
                    :active="contains_eight_characters"
                    :class="{ is_check_valid: contains_eight_characters }"
                    class="w-5 h-5"
                />
                <p class="ml-3 text-dark">At least 8-32 characters long</p>
            </div>
        </div>
        <div :class="{ is_text_valid: contains_number }" class="text-gray-500 text-sm mt-2">
            <div class="flex items-center">
                <check-icon
                    :active="contains_number"
                    :class="{ is_check_valid: contains_number }"
                    class="w-5 h-5"
                />
                <p class="ml-3 text-dark">At least one number</p>
            </div>
        </div>
        <div :class="{ is_text_valid: contains_symbol }" class="text-gray-500 text-sm mt-2">
            <div class="flex items-center">
                <check-icon
                    :active="contains_symbol"
                    :class="{ is_check_valid: contains_symbol }"
                    class="w-5 h-5"
                />
                <p class="ml-3 text-dark">At least one symbol</p>
            </div>
        </div>
        <div :class="{ is_text_valid: contains_lowercase }" class="text-gray-500 text-sm mt-2">
            <div class="flex items-center">
                <check-icon
                    :active="contains_lowercase"
                    :class="{ is_check_valid: contains_lowercase }"
                    class="w-5 h-5"
                />
                <p class="ml-3 text-dark">At least one lowercase letter</p>
            </div>
        </div>
        <div :class="{ is_text_valid: contains_uppercase }" class="text-gray-500 text-sm mt-2">
            <div class="flex items-center">
                <check-icon
                    :active="contains_uppercase"
                    :class="{ is_check_valid: contains_uppercase }"
                    class="w-5 h-5"
                />
                <p class="ml-3 text-dark">At least one uppercase letter</p>
            </div>
        </div>
        <div :class="{ is_text_valid: passwords_match }" class="text-gray-500 text-sm mt-2">
            <div class="flex items-center">
                <check-icon
                    :active="passwords_match"
                    class="w-5 h-5"
                    :class="{ is_check_valid: passwords_match }"
                />
                <p class="ml-3 text-dark">New & Confirm Passwords match</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.is_check_valid {
    color: #187a22;
}

.is_text_valid {
    color: black;
}
</style>
