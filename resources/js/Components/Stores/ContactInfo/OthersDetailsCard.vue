<script setup>
import { debounce } from 'lodash';
import { computed, onMounted, reactive, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { STORE_WAREHOUSE } from '@/Composables/Enums.js';
import DropdownSelect from '@/Components/Common/Select/DropdownSelect.vue';
import TextArea from '@/Components/Common/Input/TextArea.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import TextInput from '@/Components/Common/Input/TextInput.vue';

const props = defineProps({
    form: Object,
    store: Object,
    withHeader: { type: Boolean, default: true },
    page: { type: String, default: 'create' },
});

const editField = ref(null);

const warehouses = ref(null);

const isOtherWarehouseSelected = computed(() => {
    return props.form.warehouse === 'Others';
});

let otherDetailsForm = reactive({
    current_step: 'contact-info',
    errors: null,
});

function handleEdit(field, clear) {
    editField.value = clear ? null : field;
    if (clear) {
        delete otherDetailsForm[field];
    } else {
        otherDetailsForm[field] = props.store[field];

        if (field === 'warehouse' && props.store.warehouse === 'Others') {
            otherDetailsForm.custom_warehouse_name = props.store.custom_warehouse_name || '';
        }
    }
    otherDetailsForm.errors = null;
}

function getStoreWarehouses() {
    let url = route('enums.getDataList', { key: 'store-warehouse-enum' });
    axios.get(url).then((response) => {
        warehouses.value = [...response.data, { value: 'Others', label: 'Others' }];
    });
}

const handleUpdate = debounce(function (field) {
    if (props.page !== 'edit') {
        if (field) {
            otherDetailsForm[field] = props.form[field];
            otherDetailsForm.application_step = 'contact-info';
        }

        const payload = { ...otherDetailsForm };

        if (props.page === 'show' && otherDetailsForm.warehouse !== 'Others') {
            delete payload.custom_warehouse_name;
        }

        props.page !== 'create'
            ? router.post(route('stores.update', props.store.id), payload, {
                  preserveScroll: true,
                  onSuccess: clearForm,
                  onError: (errors) => {
                      otherDetailsForm.errors = errors;
                  },
              })
            : axios
                  .post(route('stores.update', props.store.id), payload)
                  .then(clearForm)
                  .catch((err) => {
                      otherDetailsForm.errors = err.response?.data?.errors || null;
                  });

        function clearForm() {
            editField.value = null;

            otherDetailsForm.custom_warehouse_name = null;

            otherDetailsForm.errors = null;
            otherDetailsForm.current_step = 'contact-info';

            if (
                props.page === 'create' &&
                field === 'warehouse' &&
                payload.warehouse !== 'Others'
            ) {
                props.form.custom_warehouse_name = '';
            }
        }
    }
}, 500);

onMounted(() => {
    getStoreWarehouses();
});

const canUpdateStores = computed(() => {
    return usePage().props.auth.permissions.includes('update-stores');
});
</script>

<template>
    <div class="bg-white shadow sm:rounded-2xl p-6">
        <h4 v-if="withHeader" class="font-sans font-semibold">Other Details</h4>
        <!--Create & Edit Pages-->
        <div
            v-if="page === 'create' || page === 'edit'"
            :class="withHeader ? 'border-t mt-5 pt-5' : ''"
            class="space-y-5"
        >
            <div class="bg-white rounded-2xl">
                <h2
                    v-if="withHeader && page === 'update'"
                    class="text-xl font-sans font-bold pb-4 border-b"
                >
                    Other Details
                </h2>
                <div class="space-y-5">
                    <div class="flex items-start gap-4">
                        <DropdownSelect
                            v-model="form.warehouse"
                            :value="form.warehouse"
                            custom-class="border-gray-300 !w-40"
                            label="Warehouse"
                            :required="true"
                            @update:modelValue="handleUpdate('warehouse')"
                        >
                            <option
                                v-for="(warehouseType, index) in warehouses"
                                :key="index"
                                :value="warehouseType.value"
                            >
                                {{ warehouseType.label }}
                            </option>
                        </DropdownSelect>

                        <div v-if="isOtherWarehouseSelected" class="flex-1">
                            <TextInput
                                v-model="form.custom_warehouse_name"
                                label="Warehouse (Others)"
                                input-class="border-gray-300"
                                :required="true"
                                @blur="handleUpdate('custom_warehouse_name')"
                            />
                        </div>
                    </div>
                    <TextArea
                        v-model="form.warehouse_remarks"
                        input-class="border-gray-300"
                        label="Remarks"
                        @blur="handleUpdate('warehouse_remarks')"
                    />
                </div>
            </div>
        </div>
        <!--Show Page-->
        <div v-else class="space-y-5 mt-5">
            <div class="space-y-3 divide-y divide-gray-200">
                <!-- Warehouse -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Warehouse</h5>
                    <div class="col-span-2">
                        <div v-if="editField === 'warehouse'" class="flex items-center gap-4">
                            <div
                                :class="[
                                    'grid w-full gap-2',
                                    otherDetailsForm.warehouse === 'Others'
                                        ? 'grid-cols-5'
                                        : 'grid-cols-1',
                                ]"
                            >
                                <div class="col-span-2">
                                    <DropdownSelect
                                        v-model="otherDetailsForm.warehouse"
                                        :error="otherDetailsForm.errors?.warehouse"
                                        :value="otherDetailsForm.warehouse"
                                        :custom-class="'border-gray-300 w-full'"
                                    >
                                        <option
                                            v-for="(warehouse, idx) in warehouses"
                                            :key="idx"
                                            :value="warehouse.value"
                                        >
                                            {{ warehouse.label }}
                                        </option>
                                    </DropdownSelect>
                                </div>
                                <TextInput
                                    v-if="otherDetailsForm.warehouse === 'Others'"
                                    v-model="otherDetailsForm.custom_warehouse_name"
                                    placeholder="Warehouse (Others)"
                                    :input-class="'border-gray-300 block w-full'"
                                    class="col-span-3"
                                />
                            </div>

                            <!-- inline action buttons -->
                            <SecondaryButton @click="handleEdit('warehouse', true)">
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                        </div>

                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{
                                    store.warehouse === 'Others'
                                        ? store.custom_warehouse_name || 'N/A'
                                        : store.warehouse || 'N/A'
                                }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('warehouse', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
                <!-- Remarks -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Remarks</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'warehouse_remarks'"
                            class="flex gap-4 items-center"
                        >
                            <TextArea
                                v-model="otherDetailsForm.warehouse_remarks"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('warehouse_remarks', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{ store.warehouse_remarks || 'N/A' }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('warehouse_remarks', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
