<script setup>
import CheckboxTile from './Checkbox.vue';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    options: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['update:modelValue']);

function isChecked(value) {
    return props.modelValue.includes(value);
}

function onCheckedChange(checked, value) {
    let newValue = [...props.modelValue];

    if (checked) {
        // Add if not already in the array
        if (!newValue.includes(value)) {
            newValue.push(value);
        }
    } else {
        // Remove if present in the array
        newValue = newValue.filter((item) => item !== value);
    }

    emit('update:modelValue', newValue);
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <div v-for="(option, index) in options" :key="option.value ?? index">
            <CheckboxTile
                :id="option.id"
                :name="option.name"
                :label="option.label"
                :description="option.description"
                :inlineDescription="option.inlineDescription"
                :checked="isChecked(option.value)"
                :disabled="option.disabled"
                :checkboxPosition="option.checkboxPosition || 'left'"
                :checkedColor="option.checkedColor"
                @update:checked="(checked) => onCheckedChange(checked, option.value)"
            >
                <div
                    v-if="typeof option.slotContent === 'string'"
                    v-html="option.slotContent"
                    class="inline-block"
                ></div>
                <!-- Render Vue component dynamically -->
                <component :is="option.slotContent" v-else />
            </CheckboxTile>
        </div>
    </div>
</template>
