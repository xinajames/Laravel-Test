<script setup>
import { onMounted, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import FilterModal from '@/Components/Shared/FilterModal.vue';
import SearchInputDropdown from '@/Components/Common/Select/SearchInputDropdown.vue';

const emits = defineEmits(['close', 'applyFilters', 'resetFilters']);

const props = defineProps({
    open: Boolean,
});

const activeFilters = ref([]);

const form = useForm({
    store: null,
    franchisee: null,
    store_type: null,
    status: null,
    region: null,
    area: null,
    district: null,
});

const stores = ref(null);

const franchisees = ref(null);

const storeTypes = ref(null);

const statuses = ref([]);

const regions = ref(null);

function handleSelect(data, type) {
    switch (type) {
        case 'store':
            form.store = data.label;
            updateFilterValue('stores.jbs_name', '=', form.store, form.store);
            break;
        case 'franchisee':
            form.franchisee = data.label;
            updateFilterValue('stores.franchisee_id', '=', data.id, form.franchisee);
            break;
        case 'store_type':
            form.store_type = data.label;
            updateFilterValue('stores.store_type', '=', form.store_type, form.store_type);
            break;
        case 'status':
            form.status = data.label;
            updateFilterValue('stores.store_status', '=', form.status, form.status);
            break;
        case 'region':
            form.region = data.label;
            updateFilterValue('stores.region', '=', form.region, form.region);
            break;
    }
}

function handleReset() {
    form.reset();
    activeFilters.value = [];
    emits('resetFilters');
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

function getFranchisees() {
    let url = route('franchisees.getDataList');
    axios
        .get(url, {
            params: {
                field: 'full_name',
            },
        })
        .then((response) => {
            franchisees.value = response.data;
        });
}

function getEnums(enum_key, target) {
    let url = route('enums.getDataList', { key: enum_key });
    axios.get(url).then((response) => {
        target.value = response.data;
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
    getDataLists('jbs_name', stores);
    getEnums('store-status-enum', statuses);
    getEnums('store-type-enum', storeTypes);
    getFranchisees();
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
            <div class="border-t border-light-gray mt-2 p-6 space-y-6">
                <!-- Form Fields -->
                <SearchInputDropdown
                    v-model="form.store"
                    :dataList="stores"
                    :modelValue="form.store || ''"
                    label="Store Name"
                    @update-data="handleSelect($event, 'store')"
                />

                <SearchInputDropdown
                    v-model="form.franchisee"
                    :dataList="franchisees"
                    :modelValue="form.franchisee || ''"
                    label="Franchisee"
                    @update-data="handleSelect($event, 'franchisee')"
                />

                <SearchInputDropdown
                    v-model="form.store_type"
                    :dataList="storeTypes"
                    :modelValue="form.store_type || ''"
                    label="Store Type"
                    @update-data="handleSelect($event, 'store_type')"
                />

                <SearchInputDropdown
                    v-model="form.status"
                    :dataList="statuses"
                    :modelValue="form.status || ''"
                    label="Status"
                    @update-data="handleSelect($event, 'status')"
                />

                <SearchInputDropdown
                    v-model="form.region"
                    :dataList="regions"
                    :modelValue="form.region || ''"
                    label="Region"
                    @update-data="handleSelect($event, 'region')"
                />
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
