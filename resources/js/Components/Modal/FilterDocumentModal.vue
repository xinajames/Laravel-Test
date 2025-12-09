<script setup>
import { onMounted, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import FilterModal from '@/Components/Shared/FilterModal.vue';
import DatePicker from '@/Components/Common/DatePicker/DatePicker.vue';

const emits = defineEmits(['close', 'applyFilters', 'resetFilters']);

const props = defineProps({
    open: Boolean,
    activeFilters: {
        type: Array,
        default: () => [],
    },
});

const activeFilters = ref([]);
const temporaryFilters = ref([]);

const form = useForm({
    date_from: null,
    date_to: null,
});

const tempForm = useForm({
    date_from: null,
    date_to: null,
});

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            const from = props.activeFilters.find(
                (f) => f.column === 'documents.created_at' && f.operator === '>='
            );
            const to = props.activeFilters.find(
                (f) => f.column === 'documents.created_at' && f.operator === '<='
            );

            form.date_from = from ? from.value : null;
            form.date_to = to ? to.value : null;
            tempForm.date_from = form.date_from;
            tempForm.date_to = form.date_to;

            temporaryFilters.value = props.activeFilters.map((f) => ({ ...f }));
            activeFilters.value = props.activeFilters.map((f) => ({ ...f }));
        } else {
            tempForm.date_from = form.date_from;
            tempForm.date_to = form.date_to;
        }
    }
);

watch(
    () => props.activeFilters,
    (newFilters) => {
        const from = newFilters.find(
            (f) => f.column === 'documents.created_at' && f.operator === '>='
        );
        const to = newFilters.find(
            (f) => f.column === 'documents.created_at' && f.operator === '<='
        );

        form.date_from = from ? from.value : null;
        form.date_to = to ? to.value : null;
        tempForm.date_from = form.date_from;
        tempForm.date_to = form.date_to;
        temporaryFilters.value = newFilters.map((f) => ({ ...f }));
        activeFilters.value = newFilters.map((f) => ({ ...f }));
    },
    { immediate: true }
);

function handleReset() {
    form.reset();
    tempForm.reset();
    activeFilters.value = [];
    temporaryFilters.value = [];
    emits('resetFilters');
}

function handleUpdate(field, value) {
    tempForm[field] = value;

    const formattedDate = value ? formatDate(value) : null;

    if (field === 'date_from' && value) {
        updateTempFilterValue('documents.created_at', '>=', value, `From: ${formattedDate}`);
    } else if (field === 'date_to' && value) {
        updateTempFilterValue('documents.created_at', '<=', value, `To: ${formattedDate}`);
    }
}

function formatDate(dateString) {
    if (!dateString) return '';

    const date = new Date(dateString);
    const month = date.toLocaleString('default', { month: 'short' });
    const day = date.getDate();
    const year = date.getFullYear();

    return `${month} ${day}, ${year}`;
}

function updateTempFilterValue(column, operator, val, label) {
    let existingFilterIndex = null;
    for (let i = 0; i < temporaryFilters.value.length; i++) {
        if (
            temporaryFilters.value[i].column === column &&
            temporaryFilters.value[i].operator === operator
        ) {
            existingFilterIndex = i;
            break;
        }
    }
    if (existingFilterIndex != null) {
        temporaryFilters.value[existingFilterIndex].value = val;
        temporaryFilters.value[existingFilterIndex].label = label;
    } else {
        temporaryFilters.value.push({
            column: column,
            operator: operator,
            value: val,
            label: label,
        });
    }
    temporaryFilters.value = temporaryFilters.value.filter((filter) => filter.value != null);
}

function applyFilters() {
    form.date_from = tempForm.date_from;
    form.date_to = tempForm.date_to;
    activeFilters.value = JSON.parse(JSON.stringify(temporaryFilters.value));
    emits('applyFilters');
}

function handleCancel() {
    tempForm.date_from = form.date_from;
    tempForm.date_to = form.date_to;
    temporaryFilters.value = JSON.parse(JSON.stringify(activeFilters.value));
    emits('close');
}

defineExpose({
    activeFilters,
    form,
    handleReset,
});
</script>

<template>
    <FilterModal :open="open" @close="handleCancel">
        <template #content>
            <div class="border-t border-light-gray p-6 space-y-3">
                <p class="text-sm font-medium">Date Range</p>
                <div class="flex flex-row gap-4 justify-between">
                    <div class="w-1/2">
                        <DatePicker
                            v-model="tempForm.date_from"
                            label="From"
                            placeholder="MM/DD/YYYY"
                            @update:modelValue="(value) => handleUpdate('date_from', value)"
                        />
                    </div>
                    <div class="w-1/2">
                        <DatePicker
                            v-model="tempForm.date_to"
                            label="To"
                            placeholder="MM/DD/YYYY"
                            @update:modelValue="(value) => handleUpdate('date_to', value)"
                        />
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="border-t border-light-gray px-6 py-4 bg-gray-50">
                <div class="flex flex-col sm:flex-row gap-4 justify-between">
                    <SecondaryButton class="!font-medium w-full sm:w-auto" @click="handleReset">
                        Clear All
                    </SecondaryButton>
                    <div class="flex gap-2 justify-end w-full sm:w-auto">
                        <SecondaryButton
                            class="!font-medium w-full sm:w-auto"
                            @click="handleCancel"
                        >
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton class="!font-medium w-full sm:w-auto" @click="applyFilters">
                            Apply
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </template>
    </FilterModal>
</template>
