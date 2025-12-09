<script setup>
import { onMounted, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import FilterModal from '@/Components/Shared/FilterModal.vue';
import SearchInputDropdown from '@/Components/Common/Select/SearchInputDropdown.vue';
import DatePicker from '@/Components/Common/DatePicker/DatePicker.vue';
import Moment from 'moment';
import DropdownSelect from '@/Components/Common/Select/DropdownSelect.vue';

const emits = defineEmits(['close', 'applyFilters', 'resetFilters']);

const props = defineProps({
    open: Boolean,
});

const activeFilters = ref([]);

const form = useForm({
    corporation: null,
    status: null,
    region: null,
    date_created_from: null,
    date_created_till: null,
    has_missing_fields: false,
});

const corporations = ref(null);

const statuses = ref(null);

const regions = ref(null);

function handleSelect(data, type) {
    switch (type) {
        case 'corporation_name':
            form.corporation = data.label;
            updateFilterValue(
                'franchisees.corporation_name',
                '=',
                form.corporation,
                form.corporation
            );
            break;
        case 'region':
            form.region = data.label;
            updateFilterValue('franchisees.fm_region', '=', form.region, form.region);
            break;
        default:
            let status = statuses.value.find((status) => status.value === data);
            updateFilterValue('franchisees.status', '=', form.status, status?.label);
    }
}

function handleReset() {
    form.reset();
    activeFilters.value = [];
    emits('resetFilters');
}

function dateFromSelected() {
    let date_created_from = Moment(form.date_created_from).format('YYYY-MM-DD 00:00:00');
    updateFilterValue(
        'franchisees.created_at',
        '>=',
        date_created_from,
        'Date created from ' + date_created_from
    );
}

function dateTillSelected() {
    let date_created_till = Moment(form.date_created_till).format('YYYY-MM-DD 23:59:59');
    updateFilterValue(
        'franchisees.created_at',
        '<=',
        date_created_till,
        'Date created till ' + date_created_till
    );
}

function handleMissingFieldsCheckbox() {
    if (form.has_missing_fields) {
        updateFilterValue('has_missing_fields', '=', true, 'Has Missing Data');
    } else {
        // Remove the filter if unchecked
        const idx = activeFilters.value.findIndex(
            (f) => f.column === 'has_missing_fields' && f.operator === '='
        );
        if (idx !== -1) activeFilters.value.splice(idx, 1);
    }
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

function getCorporations() {
    let url = route('franchisees.getDataList');
    axios
        .get(url, {
            params: {
                field: 'corporation_name',
            },
        })
        .then((response) => {
            corporations.value = response.data;
        });
}

function getStatuses() {
    let url = route('enums.getDataList', { key: 'franchisee-status-enum' });
    axios.get(url).then((response) => {
        statuses.value = response.data;
    });
}

function getDataLists(field, target) {
    let url = route('stores.getDataList');
    axios.get(url, { params: { field } }).then((response) => {
        target.value = response.data;
    });
}

onMounted(() => {
    getDataLists('region', regions);
    getCorporations();
    getStatuses();
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
                    v-model="form.corporation"
                    :dataList="corporations"
                    :modelValue="form.corporation || ''"
                    :with-image="false"
                    label="Corporation Name"
                    @update-data="handleSelect($event, 'corporation_name')"
                />

                <SearchInputDropdown
                    v-model="form.region"
                    :dataList="regions"
                    :modelValue="form.region || ''"
                    label="FMC - Region"
                    @update-data="handleSelect($event, 'region')"
                />

                <DropdownSelect
                    v-model="form.status"
                    :value="form.status"
                    custom-class="!border-gray-300"
                    label="Status"
                    @update:modelValue="handleSelect($event, 'status')"
                >
                    <option v-for="(status, index) in statuses" :key="index" :value="status.value">
                        {{ status.label }}
                    </option>
                </DropdownSelect>

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

                <div class="flex items-center space-x-2">
                    <input
                        id="has-missing-fields"
                        type="checkbox"
                        v-model="form.has_missing_fields"
                        @change="handleMissingFieldsCheckbox"
                        class="form-checkbox h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                    />
                    <label for="has-missing-fields" class="text-sm text-gray-700 cursor-pointer">
                        Show only franchisees with missing data
                    </label>
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
