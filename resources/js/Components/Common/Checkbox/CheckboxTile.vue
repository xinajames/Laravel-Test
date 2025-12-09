<script setup>
import BaseCheckbox from './Checkbox.vue';

const props = defineProps({
    id: { type: String, default: 'comments' },
    name: { type: String, default: 'comments' },
    label: { type: String, default: null },
    description: { type: String, default: null },
    inlineDescription: { type: Boolean, default: false },
    checked: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    checkboxPosition: {
        type: String,
        default: 'left',
        validator: (val) => ['left', 'right'].includes(val),
    },
    checkedColor: {
        type: String,
        default: '#6366f1',
    },
});

const emit = defineEmits(['update:checked']);

function onChildCheckboxChange(newVal) {
    emit('update:checked', newVal);
}
</script>
<template>
    <div class="flex gap-3 hover:brightness-90 transition-all">
        <!-- Checkbox on the LEFT -->
        <BaseCheckbox
            v-if="checkboxPosition === 'left'"
            :name="name"
            :checked="checked"
            :disabled="disabled"
            :checkedColor="checkedColor"
            @update:checked="onChildCheckboxChange"
        />

        <!-- Label + optional description + slot -->
        <div class="text-sm/6 min-w-0 flex-1">
            <!-- Required label -->
            <label :for="id" class="font-medium text-gray-900 select-none">
                {{ label }}
            </label>

            <template v-if="description">
                <template v-if="inlineDescription">
                    {{ ' ' }}
                    <span :id="`${id}-description`" class="text-gray-500">
                        <span class="sr-only">{{ label }}</span>
                        {{ description }}
                    </span>
                </template>
                <template v-else>
                    <p :id="`${id}-description`" class="text-gray-500">
                        {{ description }}
                    </p>
                </template>
            </template>

            <slot />
        </div>

        <!-- Checkbox on the RIGHT -->
        <BaseCheckbox
            v-if="checkboxPosition === 'right'"
            :name="name"
            :checked="checked"
            :disabled="disabled"
            :checkedColor="checkedColor"
            @update:checked="onChildCheckboxChange"
        />
    </div>
</template>
