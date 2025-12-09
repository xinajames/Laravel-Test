<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import Moment from 'moment';
import DatePicker from '@/Components/Common/DatePicker/DatePicker.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import TextInput from '@/Components/Common/Input/TextInput.vue';
import StoreHistoryModal from '@/Components/Modal/StoreHistoryModal.vue';

const props = defineProps({
    form: { type: Object, default: () => ({}) },
    store: Object,
    withHeader: { type: Boolean, default: true },
    page: { type: String, default: 'create' },
});

const storeId = props.store?.id || props.form.store?.id;

const editField = ref(null);

let financialDocumentsForm = reactive({
    current_step: 'specifications',
    application_step: 'specifications',
});

const historyModalOpen = ref(false);

const selectedHistoryField = ref(null);

const openHistoryModal = (field) => {
    selectedHistoryField.value = field;
    historyModalOpen.value = true;
};

function handleEdit(field, clear) {
    editField.value = clear ? null : field;
    if (clear) {
        delete financialDocumentsForm[field]; // Remove field if clearing
    } else {
        financialDocumentsForm[field] = props.store[field]; // Set field value
    }
    financialDocumentsForm.errors = null;
}

const handleUpdate = debounce(function (field) {
    if (props.page !== 'edit') {
        if (field) {
            financialDocumentsForm[field] = props.form[field];
            financialDocumentsForm.application_step = 'specifications';
        }
        router.post(route('stores.update', props.store.id), financialDocumentsForm, {
            preserveScroll: true,
            onSuccess: () => {
                editField.value = null;
                financialDocumentsForm = {
                    current_step: 'specifications',
                    errors: null,
                };
            },
            onError: (errors) => {
                financialDocumentsForm.errors = errors;
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
        <h4 v-if="withHeader" class="font-sans font-semibold">Financial & Legal Documents</h4>
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
                    Financial & Legal Documents
                </h2>
                <div class="space-y-5">
                    <!-- BIR 2303 -->
                    <TextInput
                        v-model="form.bir_2303"
                        class="lg:w-1/4 md:w-1/2"
                        input-class="border-gray-300"
                        label="BIR 2303"
                        @blur="handleUpdate('bir_2303')"
                    />

                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- CGL Insurance Policy Number -->
                        <TextInput
                            v-model="form.cgl_insurance_policy_number"
                            input-class="border-gray-300"
                            label="CGL Insurance Policy Number"
                            @blur="handleUpdate('cgl_insurance_policy_number')"
                        />

                        <!-- CGL Expiry Date -->
                        <DatePicker
                            v-model="form.cgl_expiry_date"
                            :text-input="true"
                            label="CGL Expiry Date"
                            placeholder="MM/DD/YYYY"
                            @update:modelValue="handleUpdate('cgl_expiry_date')"
                        />
                    </div>

                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Fire Insurance Policy Number -->
                        <TextInput
                            v-model="form.fire_insurance_policy_number"
                            input-class="border-gray-300"
                            label="Fire Insurance Policy Number"
                            @blur="handleUpdate('fire_insurance_policy_number')"
                        />

                        <!-- Fire Expiry Date -->
                        <DatePicker
                            v-model="form.fire_expiry_date"
                            :text-input="true"
                            label="Fire Expiry Date"
                            placeholder="MM/DD/YYYY"
                            @update:modelValue="handleUpdate('fire_expiry_date')"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="space-y-5 mt-5">
            <div class="space-y-3 divide-y divide-gray-200">
                <!-- BIR 2303 -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">BIR 2303</h5>
                    <div class="col-span-2">
                        <div v-if="editField === 'bir_2303'" class="flex gap-4 items-center">
                            <TextInput
                                v-model="financialDocumentsForm.bir_2303"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('bir_2303', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">{{ store.bir_2303 ?? 'N/A' }}</h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('bir_2303', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- CGL Insurance Policy Number -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">CGL Insurance Policy Number</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'cgl_insurance_policy_number'"
                            class="flex gap-4 items-center"
                        >
                            <TextInput
                                v-model="financialDocumentsForm.cgl_insurance_policy_number"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    @click="handleEdit('cgl_insurance_policy_number', true)"
                                >
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{ store.cgl_insurance_policy_number ?? 'N/A' }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('cgl_insurance_policy_number', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- CGL Expiry Date -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">CGL Expiry Date</h5>
                    <div class="col-span-2">
                        <div v-if="editField === 'cgl_expiry_date'" class="flex gap-4 items-center">
                            <div class="flex-1">
                                <DatePicker
                                    v-model="financialDocumentsForm.cgl_expiry_date"
                                    :text-input="true"
                                    placeholder="MM/DD/YYYY"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('cgl_expiry_date', true)"
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
                                    store.cgl_expiry_date
                                        ? Moment(store.cgl_expiry_date).format('MM/DD/YYYY')
                                        : 'N/A'
                                }}
                            </h5>
                            <div class="flex gap-6">
                                <SecondaryButton
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="openHistoryModal('cgl_expiry_date')"
                                >
                                    History
                                </SecondaryButton>
                                <SecondaryButton
                                    v-if="canUpdateStores"
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="handleEdit('cgl_expiry_date', false)"
                                >
                                    Edit
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fire Insurance Policy Number -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Fire Insurance Policy Number</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'fire_insurance_policy_number'"
                            class="flex gap-4 items-center"
                        >
                            <TextInput
                                v-model="financialDocumentsForm.fire_insurance_policy_number"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    @click="handleEdit('fire_insurance_policy_number', true)"
                                >
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{ store.fire_insurance_policy_number ?? 'N/A' }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('fire_insurance_policy_number', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- Fire Expiry Date -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Fire Expiry Date</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'fire_expiry_date'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <DatePicker
                                    v-model="financialDocumentsForm.fire_expiry_date"
                                    :text-input="true"
                                    placeholder="MM/DD/YYYY"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('fire_expiry_date', true)"
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
                                    store.fire_expiry_date
                                        ? Moment(store.fire_expiry_date).format('MM/DD/YYYY')
                                        : 'N/A'
                                }}
                            </h5>
                            <div class="flex gap-6">
                                <SecondaryButton
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="openHistoryModal('fire_expiry_date')"
                                >
                                    History
                                </SecondaryButton>
                                <SecondaryButton
                                    v-if="canUpdateStores"
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="handleEdit('fire_expiry_date', false)"
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
        />
    </div>
</template>
