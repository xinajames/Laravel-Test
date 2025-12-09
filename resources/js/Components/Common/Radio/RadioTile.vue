<script setup>
import Radio from './Radio.vue';

const props = defineProps({
    id: { type: String, required: true },
    name: { type: String, required: true },
    value: { type: [String, Number, Object, Boolean] },
    label: { type: String, required: true },
    description: { type: String, default: null },
    inlineDescription: { type: Boolean, default: false },
    modelValue: { type: [String, Number, Object, Boolean] },
    disabled: { type: Boolean, default: false },
    radioPosition: {
        type: String,
        default: 'left',
        validator: (val) => ['left', 'right'].includes(val),
    },
    color: { type: String, default: '#4F46E5' },
});

const emit = defineEmits(['update:modelValue']);

function onRadioChange(newVal) {
    emit('update:modelValue', newVal);
}
</script>

<!-- RadioTile.vue -->
<template>
    <div class="flex gap-3 hover:brightness-90 transition-all">
        <!-- Radio Button on Left -->
        <Radio
            v-if="radioPosition === 'left'"
            :id="id"
            :name="name"
            :value="value"
            :modelValue="modelValue"
            :disabled="disabled"
            @update:modelValue="onRadioChange"
            :color="color"
        />

        <!-- Label, Description, and Slot Content -->
        <div class="text-sm/6 min-w-0 flex-1">
            <!-- Required label -->
            <label :for="id" class="font-medium text-gray-900 select-none">
                {{ label }}
            </label>

            <!-- Description -->
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

            <!-- Slot Content (Stacked Below) -->
            <div>
                <slot />
            </div>
        </div>

        <!-- Radio Button on Right -->
        <Radio
            v-if="radioPosition === 'right'"
            :id="id"
            :name="name"
            :value="value"
            :modelValue="modelValue"
            :disabled="disabled"
            @update:modelValue="onRadioChange"
            :color="color"
        />
    </div>
</template>
