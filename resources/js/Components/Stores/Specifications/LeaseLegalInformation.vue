<script setup>
import { computed, reactive, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import Moment from 'moment';
import DatePicker from '@/Components/Common/DatePicker/DatePicker.vue';
import TextInput from '@/Components/Common/Input/TextInput.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import StoreHistoryModal from '@/Components/Modal/StoreHistoryModal.vue';

const props = defineProps({
    form: Object,
    withHeader: { type: Boolean, default: true },
    store: Object,
    page: { type: String, default: 'create' },
});

const storeId = props.store?.id || props.form.store?.id;

const editField = ref(null);

let leaseLegalInformationForm = reactive({
    current_step: 'specifications',
    errors: null,
});

const historyModalOpen = ref(false);

const selectedHistoryField = ref(null);

const modalHistoryTitle = ref(null);

const openHistoryModal = (field, historyTitle) => {
    selectedHistoryField.value = field;
    modalHistoryTitle.value = historyTitle;
    historyModalOpen.value = true;
};

function handleEdit(field, clear) {
    editField.value = clear ? null : field;
    if (clear) {
        delete leaseLegalInformationForm[field]; // Remove field if clearing
    } else {
        leaseLegalInformationForm[field] = props.store[field]; // Set field value
    }
    leaseLegalInformationForm.errors = null;
}

const handleUpdate = debounce(function (field) {
    if (props.page !== 'edit') {
        if (field) {
            leaseLegalInformationForm[field] = props.form[field];
            leaseLegalInformationForm.application_step = 'specifications';
        }
        router.post(route('stores.update', props.store.id), leaseLegalInformationForm, {
            preserveScroll: true,
            onSuccess: () => {
                editField.value = null;
                leaseLegalInformationForm = {
                    current_step: 'specifications',
                    errors: null,
                };
            },
            onError: (errors) => {
                leaseLegalInformationForm.errors = errors;
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
        <h4 v-if="withHeader" class="font-sans font-semibold">Lease & Legal Information</h4>
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
                    Lease & Legal Information
                </h2>
                <div class="space-y-5">
                    <DatePicker
                        v-model="form.contract_of_lease_start_date"
                        :text-input="true"
                        label="Contract of Lease Start Date"
                        placeholder="MM/DD/YYY"
                        @update:modelValue="handleUpdate('contract_of_lease_start_date')"
                    />
                    <DatePicker
                        v-model="form.contract_of_lease_end_date"
                        :text-input="true"
                        label="Contract of Lease Start Date"
                        placeholder="MM/DD/YYY"
                        @update:modelValue="handleUpdate('contract_of_lease_end_date')"
                    />
                    <TextInput
                        v-model="form.escalation"
                        input-class="border-gray-300"
                        label="Escalation"
                        @blur="handleUpdate('escalation')"
                    />
                    <TextInput
                        v-model="form.lessor_name"
                        input-class="border-gray-300"
                        label="Lessor's Name"
                        @blur="handleUpdate('lessor_name')"
                    />
                    <DatePicker
                        v-model="form.lease_payment_date"
                        :text-input="true"
                        class="md:w-1/3"
                        label="Payment Date"
                        placeholder="MM/DD/YYY"
                        @update:modelValue="handleUpdate('lease_payment_date')"
                    />
                    <TextInput
                        v-model="form.notarized_stamp_payment_receipt_number"
                        input-class="border-gray-300"
                        label="Notarized Stamp Payment (Receipt Number)"
                        @blur="handleUpdate('notarized_stamp_payment_receipt_number')"
                    />
                    <DatePicker
                        v-model="form.col_notarized_date"
                        :text-input="true"
                        class="md:w-1/3"
                        label="Date of Notarization of COL"
                        placeholder="MM/DD/YYY"
                        @update:modelValue="handleUpdate('col_notarized_date')"
                    />
                    <TextInput
                        v-model="form.col_notarized_by"
                        input-class="border-gray-300"
                        label="Name of the Attorney who notarized"
                        @blur="handleUpdate('col_notarized_by')"
                    />
                </div>
            </div>
        </div>
        <!--Show Page-->
        <div v-else class="space-y-5 mt-5">
            <div class="space-y-3 divide-y divide-gray-200">
                <!-- Contract of Lease Start Date -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Contract of Lease Start Date</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'contract_of_lease_start_date'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <DatePicker
                                    v-model="leaseLegalInformationForm.contract_of_lease_start_date"
                                    :text-input="true"
                                    placeholder="MM/DD/YYY"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('contract_of_lease_start_date', true)"
                                >
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton class="!font-medium" @click="handleUpdate(null)">
                                    Save
                                </PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{
                                    store.contract_of_lease_start_date
                                        ? Moment(store.contract_of_lease_start_date).format(
                                              'MM/DD/YYYY'
                                          )
                                        : 'N/A'
                                }}
                            </h5>
                            <div class="flex gap-6">
                                <SecondaryButton
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="
                                        openHistoryModal(
                                            'contract_of_lease_start_date',
                                            'Contract of Lease Start Date'
                                        )
                                    "
                                >
                                    History
                                </SecondaryButton>
                                <SecondaryButton
                                    v-if="canUpdateStores"
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="handleEdit('contract_of_lease_start_date', false)"
                                >
                                    Edit
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Contract of Lease End Date (Expiry Date) -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">
                        Contract of Lease End Date (Expiry Date)
                    </h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'contract_of_lease_end_date'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <DatePicker
                                    v-model="leaseLegalInformationForm.contract_of_lease_end_date"
                                    :text-input="true"
                                    placeholder="MM/DD/YYY"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('contract_of_lease_end_date', true)"
                                >
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton class="!font-medium" @click="handleUpdate(null)">
                                    Save
                                </PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{
                                    store.contract_of_lease_end_date
                                        ? Moment(store.contract_of_lease_end_date).format(
                                              'MM/DD/YYYY'
                                          )
                                        : 'N/A'
                                }}
                            </h5>
                            <div class="flex gap-6">
                                <SecondaryButton
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="
                                        openHistoryModal(
                                            'contract_of_lease_end_date',
                                            'Contract of Lease End Date (Expiry Date)'
                                        )
                                    "
                                >
                                    History
                                </SecondaryButton>
                                <SecondaryButton
                                    v-if="canUpdateStores"
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="handleEdit('contract_of_lease_end_date', false)"
                                >
                                    Edit
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Escalation -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Escalation</h5>
                    <div class="col-span-2">
                        <div v-if="editField === 'escalation'" class="flex gap-4 items-center">
                            <TextInput
                                v-model="leaseLegalInformationForm.escalation"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('escalation', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{ store.escalation || 'N/A' }}
                            </h5>
                            <div class="flex gap-6">
                                <SecondaryButton
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="openHistoryModal('escalation')"
                                >
                                    History
                                </SecondaryButton>
                                <SecondaryButton
                                    v-if="canUpdateStores"
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="handleEdit('escalation', false)"
                                >
                                    Edit
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Lessor's Name -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Lessor's Name</h5>
                    <div class="col-span-2">
                        <div v-if="editField === 'lessor_name'" class="flex gap-4 items-center">
                            <TextInput
                                v-model="leaseLegalInformationForm.lessor_name"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('lessor_name', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{ store.lessor_name || 'N/A' }}
                            </h5>
                            <div class="flex gap-6">
                                <SecondaryButton
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="openHistoryModal('lessor_name', 'Lessor\'s Name')"
                                >
                                    History
                                </SecondaryButton>
                                <SecondaryButton
                                    v-if="canUpdateStores"
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="handleEdit('lessor_name', false)"
                                >
                                    Edit
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Payment Date -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Payment Date</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'lease_payment_date'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <DatePicker
                                    v-model="leaseLegalInformationForm.lease_payment_date"
                                    :text-input="true"
                                    placeholder="MM/DD/YYY"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('lease_payment_date', true)"
                                >
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton class="!font-medium" @click="handleUpdate(null)">
                                    Save
                                </PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{
                                    store.lease_payment_date
                                        ? Moment(store.lease_payment_date).format('MM/DD/YYYY')
                                        : 'N/A'
                                }}
                            </h5>
                            <div class="flex gap-6">
                                <SecondaryButton
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="openHistoryModal('lease_payment_date')"
                                >
                                    History
                                </SecondaryButton>
                                <SecondaryButton
                                    v-if="canUpdateStores"
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="handleEdit('lease_payment_date', false)"
                                >
                                    Edit
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Notarized Stamp Payment (Receipt Number) -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">
                        Notarized Stamp Payment (Receipt Number)
                    </h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'notarized_stamp_payment_receipt_number'"
                            class="flex gap-4 items-center"
                        >
                            <TextInput
                                v-model="
                                    leaseLegalInformationForm.notarized_stamp_payment_receipt_number
                                "
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    @click="
                                        handleEdit('notarized_stamp_payment_receipt_number', true)
                                    "
                                >
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{ store.notarized_stamp_payment_receipt_number || 'N/A' }}
                            </h5>
                            <div class="flex gap-6">
                                <SecondaryButton
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="
                                        openHistoryModal(
                                            'notarized_stamp_payment_receipt_number',
                                            'Notarized Stamp Payment (Receipt Number)'
                                        )
                                    "
                                >
                                    History
                                </SecondaryButton>
                                <SecondaryButton
                                    v-if="canUpdateStores"
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="
                                        handleEdit('notarized_stamp_payment_receipt_number', false)
                                    "
                                >
                                    Edit
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Date of Notarization of COL -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Date of Notarization of COL</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'col_notarized_date'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <DatePicker
                                    v-model="leaseLegalInformationForm.col_notarized_date"
                                    placeholder="MM/DD/YYY"
                                    :text-input="true"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('col_notarized_date', true)"
                                >
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton class="!font-medium" @click="handleUpdate(null)">
                                    Save
                                </PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{
                                    store.col_notarized_date
                                        ? Moment(store.col_notarized_date).format('MM/DD/YYYY')
                                        : 'N/A'
                                }}
                            </h5>
                            <div class="flex gap-6">
                                <SecondaryButton
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="
                                        openHistoryModal(
                                            'col_notarized_date',
                                            'Date of Notarization of COL'
                                        )
                                    "
                                >
                                    History
                                </SecondaryButton>
                                <SecondaryButton
                                    v-if="canUpdateStores"
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="handleEdit('col_notarized_date', false)"
                                >
                                    Edit
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Name of the Attorney who notarized -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Name of the Attorney who notarized</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'col_notarized_by'"
                            class="flex gap-4 items-center"
                        >
                            <TextInput
                                v-model="leaseLegalInformationForm.col_notarized_by"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('col_notarized_by', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{ store.col_notarized_by || 'N/A' }}
                            </h5>
                            <div class="flex gap-6">
                                <SecondaryButton
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="
                                        openHistoryModal(
                                            'col_notarized_by',
                                            'Name of the Attorney who notarized'
                                        )
                                    "
                                >
                                    History
                                </SecondaryButton>
                                <SecondaryButton
                                    v-if="canUpdateStores"
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="handleEdit('col_notarized_by', false)"
                                >
                                    Edit
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <StoreHistoryModal
            :open="historyModalOpen"
            @close="historyModalOpen = false"
            :store-id="storeId"
            :selectedType="selectedHistoryField"
            :custom-title="modalHistoryTitle"
        />
    </div>
</template>
