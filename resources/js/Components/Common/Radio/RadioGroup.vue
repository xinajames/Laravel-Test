<script setup>
import RadioTile from './RadioTile.vue';

const props = defineProps({
    name: { type: String, required: true },
    options: {
        type: Array,
        required: true,
        validator: (val) =>
            val.every(
                (item) =>
                    typeof item.id !== 'undefined' &&
                    typeof item.label !== 'undefined' &&
                    typeof item.value !== 'undefined'
            ),
    },
    modelValue: { type: [String, Number, Object, Boolean] },
    orientation: {
        type: String,
        default: 'vertical',
        validator: (val) => ['vertical', 'inline'].includes(val),
    },
    radioPosition: {
        type: String,
        default: 'left', // or 'right'
        validator: (val) => ['left', 'right'].includes(val),
    },
    color: { type: String, default: '#4F46E5' },
    label: { type: String, default: null },
});

const emit = defineEmits(['update:modelValue']);

// Handle updating the model value
function handleUpdate(val) {
    emit('update:modelValue', val);
}
</script>

<!-- RadioGroup.vue -->
<template>
    <fieldset>
        <div class="flex justify-between mb-1">
            <label v-if="label" class="block text-sm/6 font-medium text-gray-900">
                {{ label }}
            </label>
        </div>
        <div
            :class="[
                orientation === 'inline'
                    ? 'flex flex-wrap items-center gap-x-6 gap-y-4'
                    : 'space-y-4',
            ]"
        >
            <RadioTile
                v-for="option in options"
                :key="option.id"
                :id="option.id"
                :name="name"
                :value="option.value"
                :label="option.label"
                :description="option.description"
                :inlineDescription="option.inlineDescription"
                :modelValue="modelValue"
                :disabled="option.disabled"
                :radioPosition="radioPosition"
                @update:modelValue="handleUpdate"
                :color="color"
            >
                <div
                    v-if="typeof option.slotContent === 'string'"
                    v-html="option.slotContent"
                    class="inline-block"
                ></div>
                <!-- Render Vue component dynamically -->
                <component :is="option.slotContent" v-else />
            </RadioTile>
        </div>
    </fieldset>
</template>
