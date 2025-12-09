<script setup>
import { computed, reactive, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import Moment from 'moment';
import TextInput from '@/Components/Common/Input/TextInput.vue';
import DatePicker from '@/Components/Common/DatePicker/DatePicker.vue';
import TextArea from '@/Components/Common/Input/TextArea.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import StoreHistoryModal from '@/Components/Modal/StoreHistoryModal.vue';

const props = defineProps({
    form: Object,
    store: Object,
    withHeader: { type: Boolean, default: true },
    page: { type: String, default: 'create' },
});

const storeId = props.store?.id || props.form.store?.id;

const editField = ref(null);

let storeMaintenanceUpdateForm = reactive({
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
        delete storeMaintenanceUpdateForm[field]; // Remove field if clearing
    } else {
        storeMaintenanceUpdateForm[field] = props.store[field]; // Set field value
    }
    storeMaintenanceUpdateForm.errors = null;
}

const handleUpdate = debounce(function (field) {
    if (props.page !== 'edit') {
        if (field) {
            storeMaintenanceUpdateForm[field] = props.form[field];
            storeMaintenanceUpdateForm.application_step = 'specifications';
        }
        router.post(route('stores.update', props.store.id), storeMaintenanceUpdateForm, {
            preserveScroll: true,
            onSuccess: () => {
                editField.value = null;
                storeMaintenanceUpdateForm = {
                    current_step: 'specifications',
                    errors: null,
                };
            },
            onError: (errors) => {
                storeMaintenanceUpdateForm.errors = errors;
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
        <h4 v-if="withHeader" class="font-sans font-semibold">Store Maintenance & Updates</h4>
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
                    Store Maintenance & Updates
                </h2>
                <div class="space-y-5">
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <DatePicker
                            v-model="form.maintenance_last_repaint_at"
                            :text-input="true"
                            label="Last Repaint"
                            placeholder="MM/DD/YYY"
                            @update:modelValue="handleUpdate('maintenance_last_repaint_at')"
                        />
                    </div>
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <DatePicker
                            v-model="form.maintenance_last_renovation_at"
                            :text-input="true"
                            label="Last Renovation"
                            placeholder="MM/DD/YYY"
                            @update:modelValue="handleUpdate('maintenance_last_renovation_at')"
                        />
                    </div>
                    <div class="grid md:grid-cols-2 gap-6">
                        <DatePicker
                            v-model="form.maintenance_temporary_closed_at"
                            :text-input="true"
                            label="Temporary Closed"
                            placeholder="MM/DD/YYY"
                            @update:modelValue="handleUpdate('maintenance_temporary_closed_at')"
                        />
                        <TextInput
                            v-model="form.maintenance_temporary_closed_reason"
                            input-class="border-gray-300"
                            label="Reason"
                            @blur="handleUpdate('maintenance_temporary_closed_reason')"
                        />
                    </div>
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <DatePicker
                            v-model="form.maintenance_reopening_date"
                            :text-input="true"
                            label="Re-opening Date"
                            placeholder="MM/DD/YYY"
                            @update:modelValue="handleUpdate('maintenance_reopening_date')"
                        />
                    </div>
                    <div class="grid md:grid-cols-2 gap-6">
                        <DatePicker
                            v-model="form.maintenance_permanent_closure_date"
                            :text-input="true"
                            label="Permanent Closure"
                            placeholder="MM/DD/YYY"
                            @update:modelValue="handleUpdate('maintenance_permanent_closure_date')"
                        />
                        <TextInput
                            v-model="form.maintenance_permanent_closure_reason"
                            input-class="border-gray-300"
                            label="Reason"
                            @blur="handleUpdate('maintenance_permanent_closure_reason')"
                        />
                    </div>
                    <div class="grid md:grid-cols-2 gap-6">
                        <DatePicker
                            v-model="form.maintenance_upgrade_date"
                            :text-input="true"
                            label="Upgrade Date"
                            placeholder="MM/DD/YYY"
                            @update:modelValue="handleUpdate('maintenance_upgrade_date')"
                        />
                        <DatePicker
                            v-model="form.maintenance_downgrade_date"
                            :text-input="true"
                            label="Downgrade Date"
                            placeholder="MM/DD/YYY"
                            @update:modelValue="handleUpdate('maintenance_downgrade_date')"
                        />
                    </div>
                    <TextArea
                        v-model="form.maintenance_remarks"
                        input-class="border-gray-300"
                        label="Remarks"
                        @blur="handleUpdate('maintenance_remarks')"
                    />
                    <div class="grid md:grid-cols-2 gap-6">
                        <DatePicker
                            v-model="form.maintenance_store_acquired_at"
                            :text-input="true"
                            label="Store Acquisition Date"
                            placeholder="MM/DD/YYY"
                            @update:modelValue="handleUpdate('maintenance_store_acquired_at')"
                        />
                        <DatePicker
                            v-model="form.maintenance_store_transferred_at"
                            :text-input="true"
                            label="Store Transfer Date"
                            placeholder="MM/DD/YYY"
                            @update:modelValue="handleUpdate('maintenance_store_transferred_at')"
                        />
                    </div>
                    <div class="grid md:grid-cols-2 gap-6">
                        <TextInput
                            v-model="form.maintenance_old_franchisee_code"
                            input-class="border-gray-300"
                            label="Old Franchisee Code"
                            @update:modelValue="handleUpdate('maintenance_old_franchisee_code')"
                        />
                        <TextInput
                            v-model="form.maintenance_old_branch_code"
                            input-class="border-gray-300"
                            label="Old Branch Code"
                            @blur="handleUpdate('maintenance_old_branch_code')"
                        />
                    </div>
                </div>
            </div>
        </div>
        <!--Show Page-->
        <div v-else class="space-y-5 mt-5">
            <div class="space-y-3 divide-y divide-gray-200">
                <!-- Last Repaint -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Last Repaint</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'maintenance_last_repaint_at'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <DatePicker
                                    v-model="storeMaintenanceUpdateForm.maintenance_last_repaint_at"
                                    :text-input="true"
                                    placeholder="MM/DD/YYY"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('maintenance_last_repaint_at', true)"
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
                                    store.maintenance_last_repaint_at
                                        ? Moment(store.maintenance_last_repaint_at).format(
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
                                            'maintenance_last_repaint_at',
                                            'Last Repaint'
                                        )
                                    "
                                >
                                    History
                                </SecondaryButton>
                                <SecondaryButton
                                    v-if="canUpdateStores"
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="handleEdit('maintenance_last_repaint_at', false)"
                                >
                                    Edit
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Last Renovation -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Last Renovation</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'maintenance_last_renovation_at'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <DatePicker
                                    v-model="
                                        storeMaintenanceUpdateForm.maintenance_last_renovation_at
                                    "
                                    :text-input="true"
                                    placeholder="MM/DD/YYY"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('maintenance_last_renovation_at', true)"
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
                                    store.maintenance_last_renovation_at
                                        ? Moment(store.maintenance_last_renovation_at).format(
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
                                            'maintenance_last_renovation_at',
                                            'Last Renovation'
                                        )
                                    "
                                >
                                    History
                                </SecondaryButton>
                                <SecondaryButton
                                    v-if="canUpdateStores"
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="handleEdit('maintenance_last_renovation_at', false)"
                                >
                                    Edit
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Temporary Closed -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Temporary Closed</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'maintenance_temporary_closed_at'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <DatePicker
                                    v-model="
                                        storeMaintenanceUpdateForm.maintenance_temporary_closed_at
                                    "
                                    :text-input="true"
                                    placeholder="MM/DD/YYY"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('maintenance_temporary_closed_at', true)"
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
                                    store.maintenance_temporary_closed_at
                                        ? Moment(store.maintenance_temporary_closed_at).format(
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
                                            'maintenance_temporary_closed_at',
                                            'Temporary Closed'
                                        )
                                    "
                                >
                                    History
                                </SecondaryButton>
                                <SecondaryButton
                                    v-if="canUpdateStores"
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="handleEdit('maintenance_temporary_closed_at', false)"
                                >
                                    Edit
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Temporary Closed - Reason -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Reason</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'maintenance_temporary_closed_reason'"
                            class="flex gap-4 items-center"
                        >
                            <TextInput
                                v-model="
                                    storeMaintenanceUpdateForm.maintenance_temporary_closed_reason
                                "
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    @click="handleEdit('maintenance_temporary_closed_reason', true)"
                                >
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{ store.maintenance_temporary_closed_reason || 'N/A' }}
                            </h5>
                            <div class="flex gap-6">
                                <SecondaryButton
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="
                                        openHistoryModal(
                                            'maintenance_temporary_closed_reason',
                                            'Reason'
                                        )
                                    "
                                >
                                    History
                                </SecondaryButton>
                                <SecondaryButton
                                    v-if="canUpdateStores"
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="
                                        handleEdit('maintenance_temporary_closed_reason', false)
                                    "
                                >
                                    Edit
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Re-opening Date -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Re-opening Date</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'maintenance_reopening_date'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <DatePicker
                                    v-model="storeMaintenanceUpdateForm.maintenance_reopening_date"
                                    :text-input="true"
                                    placeholder="MM/DD/YYY"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('maintenance_reopening_date', true)"
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
                                    store.maintenance_reopening_date
                                        ? Moment(store.maintenance_reopening_date).format(
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
                                            'maintenance_reopening_date',
                                            'Re-opening Date'
                                        )
                                    "
                                >
                                    History
                                </SecondaryButton>
                                <SecondaryButton
                                    v-if="canUpdateStores"
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="handleEdit('maintenance_reopening_date', false)"
                                >
                                    Edit
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Permanent Closure -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Permanent Closure</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'maintenance_permanent_closure_date'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <DatePicker
                                    v-model="
                                        storeMaintenanceUpdateForm.maintenance_permanent_closure_date
                                    "
                                    :text-input="true"
                                    placeholder="MM/DD/YYY"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('maintenance_permanent_closure_date', true)"
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
                                    store.maintenance_permanent_closure_date
                                        ? Moment(store.maintenance_permanent_closure_date).format(
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
                                            'maintenance_permanent_closure_date',
                                            'Permanent Closure'
                                        )
                                    "
                                >
                                    History
                                </SecondaryButton>
                                <SecondaryButton
                                    v-if="canUpdateStores"
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="handleEdit('maintenance_permanent_closure_date', false)"
                                >
                                    Edit
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Permanent Closure - Reason -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Reason</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'maintenance_permanent_closure_reason'"
                            class="flex gap-4 items-center"
                        >
                            <TextInput
                                v-model="
                                    storeMaintenanceUpdateForm.maintenance_permanent_closure_reason
                                "
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    @click="
                                        handleEdit('maintenance_permanent_closure_reason', true)
                                    "
                                >
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{ store.maintenance_permanent_closure_reason || 'N/A' }}
                            </h5>
                            <div class="flex gap-6">
                                <SecondaryButton
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="
                                        openHistoryModal(
                                            'maintenance_permanent_closure_reason',
                                            'Reason'
                                        )
                                    "
                                >
                                    History
                                </SecondaryButton>
                                <SecondaryButton
                                    v-if="canUpdateStores"
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="
                                        handleEdit('maintenance_permanent_closure_reason', false)
                                    "
                                >
                                    Edit
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Upgrade Date -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Upgrade Date</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'maintenance_upgrade_date'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <DatePicker
                                    v-model="storeMaintenanceUpdateForm.maintenance_upgrade_date"
                                    :text-input="true"
                                    placeholder="MM/DD/YYY"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('maintenance_upgrade_date', true)"
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
                                    store.maintenance_upgrade_date
                                        ? Moment(store.maintenance_upgrade_date).format(
                                              'MM/DD/YYYY'
                                          )
                                        : 'N/A'
                                }}
                            </h5>
                            <div class="flex gap-6">
                                <SecondaryButton
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="
                                        openHistoryModal('maintenance_upgrade_date', 'Upgrade Date')
                                    "
                                >
                                    History
                                </SecondaryButton>
                                <SecondaryButton
                                    v-if="canUpdateStores"
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="handleEdit('maintenance_upgrade_date', false)"
                                >
                                    Edit
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Downgrade Date -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Downgrade Date</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'maintenance_downgrade_date'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <DatePicker
                                    v-model="storeMaintenanceUpdateForm.maintenance_downgrade_date"
                                    :text-input="true"
                                    placeholder="MM/DD/YYY"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('maintenance_downgrade_date', true)"
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
                                    store.maintenance_downgrade_date
                                        ? Moment(store.maintenance_downgrade_date).format(
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
                                            'maintenance_downgrade_date',
                                            'Downgrade Date'
                                        )
                                    "
                                >
                                    History
                                </SecondaryButton>
                                <SecondaryButton
                                    v-if="canUpdateStores"
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="handleEdit('maintenance_downgrade_date', false)"
                                >
                                    Edit
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Remarks -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Remarks</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'maintenance_remarks'"
                            class="flex gap-4 items-center"
                        >
                            <TextArea
                                v-model="storeMaintenanceUpdateForm.maintenance_remarks"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('maintenance_remarks', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{ store.maintenance_remarks || 'N/A' }}
                            </h5>
                            <div class="flex gap-6">
                                <SecondaryButton
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="openHistoryModal('maintenance_remarks', 'Remarks')"
                                >
                                    History
                                </SecondaryButton>
                                <SecondaryButton
                                    v-if="canUpdateStores"
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="handleEdit('maintenance_remarks', false)"
                                >
                                    Edit
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Store Acquisition Date -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Store Acquisition Date</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'maintenance_store_acquired_at'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <DatePicker
                                    v-model="
                                        storeMaintenanceUpdateForm.maintenance_store_acquired_at
                                    "
                                    :text-input="true"
                                    placeholder="MM/DD/YYY"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('maintenance_store_acquired_at', true)"
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
                                    store.maintenance_store_acquired_at
                                        ? Moment(store.maintenance_store_acquired_at).format(
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
                                            'maintenance_store_acquired_at',
                                            'Store Acquisition Date'
                                        )
                                    "
                                >
                                    History
                                </SecondaryButton>
                                <SecondaryButton
                                    v-if="canUpdateStores"
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="handleEdit('maintenance_store_acquired_at', false)"
                                >
                                    Edit
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Store Transfer Date -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Store Transfer Date</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'maintenance_store_transferred_at'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <DatePicker
                                    v-model="
                                        storeMaintenanceUpdateForm.maintenance_store_transferred_at
                                    "
                                    :text-input="true"
                                    placeholder="MM/DD/YYY"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('maintenance_store_transferred_at', true)"
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
                                    store.maintenance_store_transferred_at
                                        ? Moment(store.maintenance_store_transferred_at).format(
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
                                            'maintenance_store_transferred_at',
                                            'Store Transfer Date'
                                        )
                                    "
                                >
                                    History
                                </SecondaryButton>
                                <SecondaryButton
                                    v-if="canUpdateStores"
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="handleEdit('maintenance_store_transferred_at', false)"
                                >
                                    Edit
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Old Franchisee Code -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Old Franchisee Code</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'maintenance_old_franchisee_code'"
                            class="flex gap-4 items-center"
                        >
                            <TextInput
                                v-model="storeMaintenanceUpdateForm.maintenance_old_franchisee_code"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    @click="handleEdit('maintenance_old_franchisee_code', true)"
                                >
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{ store.maintenance_old_franchisee_code || 'N/A' }}
                            </h5>
                            <div class="flex gap-6">
                                <SecondaryButton
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="
                                        openHistoryModal(
                                            'maintenance_old_franchisee_code',
                                            'Old Franchisee Code'
                                        )
                                    "
                                >
                                    History
                                </SecondaryButton>
                                <SecondaryButton
                                    v-if="canUpdateStores"
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="handleEdit('maintenance_old_franchisee_code', false)"
                                >
                                    Edit
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Old Branch Code -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Old Branch Code</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'maintenance_old_branch_code'"
                            class="flex gap-4 items-center"
                        >
                            <TextInput
                                v-model="storeMaintenanceUpdateForm.maintenance_old_branch_code"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    @click="handleEdit('maintenance_old_branch_code', true)"
                                >
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{ store.maintenance_old_branch_code || 'N/A' }}
                            </h5>
                            <div class="flex gap-6">
                                <SecondaryButton
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="
                                        openHistoryModal(
                                            'maintenance_old_branch_code',
                                            'Old Branch Code'
                                        )
                                    "
                                >
                                    History
                                </SecondaryButton>
                                <SecondaryButton
                                    v-if="canUpdateStores"
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="handleEdit('maintenance_old_branch_code', false)"
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
