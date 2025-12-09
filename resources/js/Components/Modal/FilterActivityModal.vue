<script setup>
import { onMounted, ref } from 'vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import FilterModal from '@/Components/Shared/FilterModal.vue';
import { useForm } from '@inertiajs/vue3';
import SearchInputDropdown from '@/Components/Common/Select/SearchInputDropdown.vue';
import DatePicker from '@/Components/Common/DatePicker/DatePicker.vue';
import Moment from 'moment';

const emits = defineEmits(['close', 'applyFilters', 'resetFilters']);

const props = defineProps({
    open: Boolean,
});

const activeFilters = ref([]);

// data
const form = useForm({
    causer: null,
    date_created_from: null,
    date_created_till: null,
});

const causers = ref(null);

function handleSelect(data, type) {
    if (type === 'causer') {
        form.causer = data.label;
        updateFilterValue('activity_log.causer_id', '=', data.id, form.causer);
    }
}

function dateFromSelected() {
    let date_created_from = Moment(form.date_created_from, 'MM/DD/YYYY').format(
        'YYYY-MM-DD 00:00:00'
    );
    updateFilterValue(
        'activity_log.created_at',
        '>=',
        date_created_from,
        'Date created from ' + date_created_from
    );
}

function dateTillSelected() {
    let date_created_till = Moment(form.date_created_till, 'MM/DD/YYYY').format(
        'YYYY-MM-DD 23:59:59'
    );
    updateFilterValue(
        'activity_log.created_at',
        '<=',
        date_created_till,
        'Date created till ' + date_created_till
    );
}

function updateFilterValue(column, operator, val, label) {
    let existingFilterIndex = null;
    for (let i = 0; i < activeFilters.value.length; i++) {
        if (
            activeFilters.value[i].column === column &&
            activeFilters.value[i].operator === operator
        ) {
            existingFilterIndex = i;
            break;
        }
    }
    if (existingFilterIndex != null) {
        activeFilters.value[existingFilterIndex].value = val;
        activeFilters.value[existingFilterIndex].label = label;
    } else {
        activeFilters.value.push({
            column: column,
            operator: operator,
            value: val,
            label: label,
        });
    }
    // Remove filters with a null value
    for (let i = 0; i < activeFilters.value.length; i++) {
        if (activeFilters.value[i].value == null) {
            activeFilters.value.splice(i, 1);
        }
    }
}

function handleReset() {
    form.reset();
    activeFilters.value = [];
    emits('resetFilters');
}

function getCausers() {
    let url = route('activities.getDataList');
    axios
        .get(url, {
            params: {
                field: 'name',
            },
        })
        .then((response) => {
            causers.value = response.data;
        });
}

onMounted(() => {
    getCausers();
});

defineExpose({
    activeFilters,
    form,
    handleReset,
});
</script>

<template>
    <FilterModal :open="open" @close="emits('close')">
        <template #content>
            <!-- Form Fields -->
            <div class="border-t border-light-gray mt-2 p-6 space-y-6">
                <SearchInputDropdown
                    v-model="form.causer"
                    :dataList="causers"
                    :modelValue="form.causer || ''"
                    :with-image="false"
                    label="Causer Name"
                    @update-data="handleSelect($event, 'causer')"
                />

                <!-- Date Range Selection -->
                <div>
                    <h6 class="font-medium text-sm text-gray-900">Date Range</h6>
                    <div class="flex flex-col sm:flex-row gap-4 mt-3">
                        <div class="w-full">
                            <DatePicker
                                v-model="form.date_created_from"
                                :text-input="true"
                                label="From"
                                placeholder="MM/DD/YYYY"
                                @update:modelValue="dateFromSelected"
                            />
                        </div>
                        <div class="w-full">
                            <DatePicker
                                v-model="form.date_created_till"
                                :text-input="true"
                                label="To"
                                placeholder="MM/DD/YYYY"
                                @update:modelValue="dateTillSelected"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="border-t border-light-gray p-6 bg-gray-50">
                <div class="flex flex-col sm:flex-row gap-4 justify-between">
                    <SecondaryButton class="!font-medium w-full sm:w-auto" @click="handleReset">
                        Clear All
                    </SecondaryButton>
                    <div class="flex gap-2 justify-end w-full sm:w-auto">
                        <SecondaryButton
                            class="!font-medium w-full sm:w-auto"
                            @click="emits('close')"
                        >
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton
                            class="!font-medium w-full sm:w-auto"
                            @click="emits('applyFilters')"
                        >
                            Apply
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </template>
    </FilterModal>
</template>
