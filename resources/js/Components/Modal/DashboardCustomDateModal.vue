<script setup>
import { ref, watch } from 'vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import FilterModal from '@/Components/Shared/FilterModal.vue';
import DatePicker from '@/Components/Common/DatePicker/DatePicker.vue';

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    currentYear: {
        type: Number,
        default: null,
    },
});

const emits = defineEmits(['close', 'year-selected']);

const selectedYear = ref(props.currentYear);
const formError = ref(false);

watch(
    () => props.currentYear,
    (newValue) => {
        selectedYear.value = newValue;
    }
);

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            selectedYear.value = props.currentYear;
        }
    }
);

function applyYear() {
    if (!selectedYear.value) {
        formError.value = true;
        return;
    }

    const year = selectedYear.value;

    emits('year-selected', year);
    emits('close');
}

function handleUpdate(value) {
    selectedYear.value = value;
    if (value) {
        formError.value = false;
    }
}
</script>

<template>
    <FilterModal :open="open" :title="'Custom Year'" @close="emits('close')">
        <template #content>
            <form @submit.prevent="applyYear" class="border-t border-light-gray p-6 space-y-3">
                <div class="w-full">
                    <DatePicker
                        v-model="selectedYear"
                        label="Year"
                        placeholder="YYYY"
                        format="yyyy"
                        :required="true"
                        :enable-time-picker="false"
                        :year-picker="true"
                        @update:modelValue="handleUpdate"
                    />
                    <p v-if="formError" class="mt-1 text-sm text-red-600">Please select a year</p>
                </div>

                <!-- Footer Actions -->
                <div class="border-t border-light-gray pt-4 mt-4">
                    <div class="flex flex-col sm:flex-row gap-4 justify-end">
                        <div class="flex gap-2 justify-end w-full sm:w-auto">
                            <SecondaryButton
                                type="button"
                                class="!font-medium w-full sm:w-auto"
                                @click="emits('close')"
                            >
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton type="submit" class="!font-medium w-full sm:w-auto">
                                Apply
                            </PrimaryButton>
                        </div>
                    </div>
                </div>
            </form>
        </template>
    </FilterModal>
</template>
