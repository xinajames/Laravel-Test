<script setup>
import { computed, reactive, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import TextInput from '@/Components/Common/Input/TextInput.vue';
import NumberInput from '@/Components/Common/Input/NumberInput.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import DatePicker from '@/Components/Common/DatePicker/DatePicker.vue';
import RadioGroup from '@/Components/Common/Radio/RadioGroup.vue';
import Moment from 'moment/moment.js';

const props = defineProps({
    form: Object,
    store: Object,
    withHeader: { type: Boolean, default: true },
    page: { type: String, default: 'create' },
});

const editField = ref(null);

let managementOperationForm = reactive({
    current_step: 'basic-details',
    errors: null,
});

const brfInEffect = [
    { id: '1', label: 'Yes', value: '1' },
    { id: '2', label: 'No', value: '0' },
];

function handleEdit(field, clear) {
    editField.value = clear ? null : field;
    if (clear) {
        delete managementOperationForm[field]; // Remove field if clearing
    } else {
        managementOperationForm[field] = props.store[field]; // Set field value
    }
    managementOperationForm.errors = null;
}

const handleUpdate = debounce(function (field) {
    if (props.page !== 'edit') {
        if (field) {
            managementOperationForm[field] = props.form[field];
            managementOperationForm.application_step = 'basic-details';
        }
        router.post(route('stores.update', props.store.id), managementOperationForm, {
            preserveScroll: true,
            onSuccess: () => {
                editField.value = null;
                managementOperationForm = {
                    current_step: 'basic-details',
                    errors: null,
                };
            },
            onError: (errors) => {
                managementOperationForm.errors = errors;
            },
        });
    }
}, 500);

const canUpdateStores = computed(() => {
    return usePage().props.auth.permissions.includes('update-stores');
});
</script>

<template>
    <div class="bg-white shadow sm:rounded-2xl p-6">
        <h4 v-if="withHeader" class="font-sans font-semibold">Management & Operations</h4>
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
                    Management & Operations
                </h2>
                <div class="space-y-5">
                    <div class="lg:w-1/4">
                        <TextInput
                            v-model="form.om_district_code"
                            input-class="border-gray-300"
                            label="District Code"
                            @blur="handleUpdate('om_district_code')"
                        />
                    </div>

                    <div class="space-y-4">
                        <TextInput
                            v-model="form.om_district_name"
                            input-class="border-gray-300"
                            label="District Name"
                            @blur="handleUpdate('om_district_name')"
                        />

                        <TextInput
                            v-model="form.om_district_manager"
                            input-class="border-gray-300"
                            label="District Manager / Area Manager"
                            @blur="handleUpdate('om_district_manager')"
                        />

                        <div class="lg:w-1/4">
                            <TextInput
                                v-model="form.om_cost_center_code"
                                input-class="border-gray-300"
                                label="Cost Center Code"
                                @blur="handleUpdate('om_cost_center_code')"
                            />
                        </div>
                    </div>

                    <div class="w-full pb-6 space-y-6">
                        <div class="space-y-4">
                            <NumberInput
                                v-model="form.old_continuing_license_fee"
                                input-class="border-gray-300"
                                label="Old Continuing License Fee %"
                                leftSymbol="%"
                                showLeftSymbol
                                @blur="handleUpdate('old_continuing_license_fee')"
                            />

                            <NumberInput
                                v-model="form.current_continuing_license_fee"
                                input-class="border-gray-300"
                                label="Current Continuing License Fee %"
                                leftSymbol="%"
                                showLeftSymbol
                                @blur="handleUpdate('current_continuing_license_fee')"
                            />

                            <DatePicker
                                v-model="form.continuing_license_fee_in_effect"
                                :text-input="true"
                                class="w-full"
                                label="Continuing License Fee % in Effect"
                                placeholder="MM/DD/YYYY"
                                @update:modelValue="
                                    handleUpdate('continuing_license_fee_in_effect')
                                "
                            />
                        </div>

                        <div class="space-y-4">
                            <RadioGroup
                                v-model="form.brf_in_effect"
                                :options="brfInEffect"
                                color="#A32130"
                                label="BRF in Effect"
                                name="brf_in_effect"
                                orientation="inline"
                                radioPosition="left"
                                @update:modelValue="handleUpdate('brf_in_effect')"
                            />

                            <TextInput
                                v-model="form.report_percent"
                                input-class="border-gray-300"
                                label="Report Percent"
                                @blur="handleUpdate('report_percent')"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="space-y-5 mt-5">
            <div class="space-y-3 divide-y divide-gray-200">
                <!-- District Code -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">District Code</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'om_district_code'"
                            class="flex gap-4 items-center"
                        >
                            <TextInput
                                v-model="managementOperationForm.om_district_code"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('om_district_code', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">{{ store.om_district_code }}</h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('om_district_code', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- District Name -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">District Name</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'om_district_name'"
                            class="flex gap-4 items-center"
                        >
                            <TextInput
                                v-model="managementOperationForm.om_district_name"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('om_district_name', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">{{ store.om_district_name }}</h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('om_district_name', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- District Manager / Area Manager -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">District Manager / Area Manager</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'om_district_manager'"
                            class="flex gap-4 items-center"
                        >
                            <TextInput
                                v-model="managementOperationForm.om_district_manager"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('om_district_manager', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">{{ store.om_district_manager }}</h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('om_district_manager', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- Cost Center Code -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Cost Center Code</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'om_cost_center_code'"
                            class="flex gap-4 items-center"
                        >
                            <TextInput
                                v-model="managementOperationForm.om_cost_center_code"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('om_cost_center_code', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">{{ store.om_cost_center_code }}</h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('om_cost_center_code', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- Old Continuing License Fee % -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Old Continuing License Fee %</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'old_continuing_license_fee'"
                            class="flex gap-4 items-center"
                        >
                            <TextInput
                                v-model="managementOperationForm.old_continuing_license_fee"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    @click="handleEdit('old_continuing_license_fee', true)"
                                >
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">{{ store.old_continuing_license_fee }}</h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('old_continuing_license_fee', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- Current Continuing License Fee % -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Current Continuing License Fee %</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'current_continuing_license_fee'"
                            class="flex gap-4 items-center"
                        >
                            <TextInput
                                v-model="managementOperationForm.current_continuing_license_fee"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    @click="handleEdit('current_continuing_license_fee', true)"
                                >
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{ store.current_continuing_license_fee }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('current_continuing_license_fee', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- Continuing License Fee % in Effect -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Continuing License Fee % in Effect</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'continuing_license_fee_in_effect'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <DatePicker
                                    v-model="
                                        managementOperationForm.continuing_license_fee_in_effect
                                    "
                                    :text-input="true"
                                    placeholder="MM/DD/YYY"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    @click="handleEdit('continuing_license_fee_in_effect', true)"
                                >
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{
                                    store.continuing_license_fee_in_effect
                                        ? Moment(store.continuing_license_fee_in_effect).format(
                                              'MM/DD/YYYY'
                                          )
                                        : 'N/A'
                                }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('continuing_license_fee_in_effect', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- BRF in Effect -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">BRF in Effect</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'brf_in_effect'"
                            class="flex gap-4 items-center justify-between"
                        >
                            <RadioGroup
                                v-model="managementOperationForm.brf_in_effect"
                                :model-value="managementOperationForm.brf_in_effect"
                                :options="brfInEffect"
                                color="#A32130"
                                name="brf_in_effect"
                                orientation="inline"
                                radioPosition="left"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('brf_in_effect', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{ store.brf_in_effect === '1' ? 'Yes' : 'No' }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('brf_in_effect', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- Report Percent -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Report Percent</h5>
                    <div class="col-span-2">
                        <div v-if="editField === 'report_percent'" class="flex gap-4 items-center">
                            <TextInput
                                v-model="managementOperationForm.report_percent"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('report_percent', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">{{ store.report_percent }}</h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('report_percent', false)"
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
