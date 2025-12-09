<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import TextInput from '@/Components/Common/Input/TextInput.vue';
import DropdownSelect from '@/Components/Common/Select/DropdownSelect.vue';
import StatusBadge from '@/Components/Common/Badge/StatusBadge.vue';
import StoreHistoryModal from '@/Components/Modal/StoreHistoryModal.vue';
import EditFranchiseeCodeModal from '@/Components/Modal/EditFranchiseeCodeModal.vue';
import EditClusterJbmisModal from '@/Components/Modal/EditClusterJbmisModal.vue';

const props = defineProps({
    form: Object,
    store: Object,
    withHeader: { type: Boolean, default: true },
    page: { type: String, default: 'create' },
});

const storeId = props.store?.id || props.form.store?.id;

const editField = ref(null);

const franchiseeCodeDisplay = computed(() => {
    return props.store.franchisee?.franchisee_code || '';
});

const regionsData = ref({});

function getRegionsData() {
    let url = route('enums.getDataList', { key: 'region-dropdown' });
    axios.get(url).then((response) => {
        regionsData.value = response.data;
    });
}

let generalInformationForm = reactive({
    application_step: 'basic-details',
    errors: null,
});

const statuses = ref(null);

const storeGroups = ref(null);

const storeTypes = ref(null);

const historyModalOpen = ref(false);
const editFranchiseeCodeModalOpen = ref(false);
const editClusterJbmisModalOpen = ref(false);

const selectedHistoryField = ref(null);

const openHistoryModal = (field) => {
    selectedHistoryField.value = field;
    historyModalOpen.value = true;
};

const openEditFranchiseeCodeModal = () => {
    editFranchiseeCodeModalOpen.value = true;
};

const openEditClusterJbmisModal = () => {
    editClusterJbmisModalOpen.value = true;
};

const handleFranchiseeCodeUpdateSuccess = () => {
    // Refresh the page to get updated data
    window.location.reload();
};

const handleClusterJbmisUpdateSuccess = () => {
    // Refresh the page to get updated data
    window.location.reload();
};

const handleFranchiseeUpdate = (franchisee) => {
    // Update the form with the selected franchisee data
    props.form.franchisee_id = franchisee.id;
    
    // Update the store data to reflect the change immediately
    if (props.store.franchisee) {
        props.store.franchisee.id = franchisee.id;
        props.store.franchisee.franchisee_code = franchisee.franchisee_code;
        props.store.franchisee.full_name = franchisee.franchisee_name;
        props.store.franchisee.corporation_name = franchisee.corporation_name;
    }
};

function handleEdit(field, clear) {
    editField.value = clear ? null : field;
    if (clear) {
        delete generalInformationForm[field]; // Remove field if clearing
    } else {
        generalInformationForm[field] = props.store[field]; // Set field value
    }
    generalInformationForm.errors = null;
}

function getStoreGroups() {
    let url = route('enums.getDataList', { key: 'store-group-enum' });
    axios.get(url).then((response) => {
        storeGroups.value = response.data;
    });
}

function getStoreStatuses() {
    let url = route('enums.getDataList', { key: 'store-status-enum' });
    axios.get(url).then((response) => {
        statuses.value = response.data;
    });
}

function getStoreTypes() {
    let url = route('enums.getDataList', { key: 'store-type-enum' });
    axios.get(url).then((response) => {
        storeTypes.value = response.data;
    });
}

const handleUpdate = debounce(function (field) {
    if (props.page !== 'edit') {
        if (field) {
            generalInformationForm[field] = props.form[field];
            generalInformationForm.application_step = 'basic-details';
        }
        router.post(route('stores.update', props.store.id), generalInformationForm, {
            preserveScroll: true,
            onSuccess: () => {
                editField.value = null;
                generalInformationForm = {
                    application_step: 'basic-details',
                    errors: null,
                };
            },
            onError: (errors) => {
                generalInformationForm.errors = errors;
            },
        });
    }
}, 500);

onMounted(() => {
    getStoreGroups();
    getStoreStatuses();
    getStoreTypes();
    getRegionsData();
});

const canUpdateStores = computed(() => {
    return usePage().props.auth.permissions.includes('update-stores');
});
</script>

<template>
    <div class="bg-white shadow sm:rounded-2xl p-6">
        <h4 v-if="withHeader" class="font-sans font-semibold">General Information</h4>
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
                    Store Overview
                </h2>
                <div class="space-y-5">
                    <div class="w-1/4">
                        <TextInput
                            v-model="form.store_code"
                            :disabled="true"
                            input-class="border-gray-300"
                            label="Branch Code"
                            @update:modelValue="handleUpdate('store_code')"
                        />
                    </div>

                    <TextInput
                        v-model="form.jbs_name"
                        input-class="border-gray-300"
                        label="JBS Name"
                        @blur="handleUpdate('jbs_name')"
                    />

                    <div class="grid lg:grid-cols-2 gap-4 pb-5 border-b border-gray-200">
                        <DropdownSelect
                            v-model="form.store_type"
                            :value="form.store_type"
                            custom-class="!border-gray-300"
                            label="Store Type"
                            @update:modelValue="handleUpdate('store_type')"
                        >
                            <option
                                v-for="(type, index) in storeTypes"
                                :key="index"
                                :value="type.value"
                            >
                                {{ type.label }}
                            </option>
                        </DropdownSelect>

                        <DropdownSelect
                            v-model="form.store_group"
                            :value="form.store_group"
                            custom-class="!border-gray-300"
                            label="Store Group"
                            @update:modelValue="handleUpdate('store_group')"
                        >
                            <option
                                v-for="(group, index) in storeGroups"
                                :key="index"
                                :value="group.value"
                            >
                                {{ group.label }}
                            </option>
                        </DropdownSelect>
                    </div>

                    <div class="grid lg:grid-cols-2 gap-4 pb-5 border-b border-gray-200">
                        <div class="w-full">
                            <TextInput
                                v-model="form.cluster_code"
                                input-class="border-gray-300"
                                label="Cluster Code"
                                @update:modelValue="handleUpdate('cluster_code')"
                            />
                        </div>
                        <div class="w-full">
                            <div class="flex items-end gap-2">
                                <div class="flex-1">
                                    <TextInput
                                        :model-value="franchiseeCodeDisplay"
                                        :disabled="true"
                                        input-class="border-gray-300"
                                        label="Franchisee Code"
                                    />
                                </div>
                                <SecondaryButton
                                    v-if="canUpdateStores && page !== 'create'"
                                    class="!px-3 !py-2 !text-sm"
                                    @click="openEditFranchiseeCodeModal"
                                >
                                    Change
                                </SecondaryButton>
                            </div>
                        </div>
                        <div class="w-full">
                            <TextInput
                                v-model="form.sales_point_code"
                                input-class="border-gray-300"
                                label="Sales Point Code"
                                @update:modelValue="handleUpdate('sales_point_code')"
                            />
                        </div>
                        <div class="w-full">
                            <TextInput
                                v-model="form.jbmis_code"
                                input-class="border-gray-300"
                                label="JBMIS Code"
                                @update:modelValue="handleUpdate('jbmis_code')"
                            />
                        </div>
                    </div>

                    <div class="grid lg:grid-cols-3">
                        <DropdownSelect
                            v-model="form.store_status"
                            :value="form.store_status"
                            custom-class="border-gray-300"
                            label="Status"
                            @update:modelValue="handleUpdate('store_status')"
                        >
                            <option
                                v-for="(status, index) in statuses"
                                :key="index"
                                :value="status.value"
                            >
                                {{ status.label }}
                            </option>
                        </DropdownSelect>
                    </div>

                    <DropdownSelect
                        v-model="form.region"
                        :value="form.region"
                        custom-class="!border-gray-300"
                        label="Region"
                        @update:modelValue="handleUpdate('region')"
                    >
                        <option
                            v-for="(region, index) in regionsData"
                            :key="index"
                            :value="region.value"
                        >
                            {{ region.label }}
                        </option>
                    </DropdownSelect>

                    <TextInput
                        v-model="form.area"
                        input-class="border-gray-300"
                        label="Area"
                        @blur="handleUpdate('area')"
                    />

                    <TextInput
                        v-model="form.district"
                        input-class="border-gray-300"
                        label="District"
                        @blur="handleUpdate('district')"
                    />

                    <div class="border-t border-gray-200 pt-5">
                        <TextInput
                            v-model="form.google_maps_link"
                            input-class="!border-gray-300"
                            label="Google Maps Link"
                            @blur="handleUpdate('google_maps_link')"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="space-y-5 mt-5">
            <div class="space-y-3 divide-y divide-gray-200">
                <!-- Branch Code -->
                <div class="grid grid-cols-3 gap-4 items-center mt-5">
                    <h5 class="font-medium text-gray-500">Branch Code</h5>
                    <h5 class="text-gray-900">{{ store.store_code }}</h5>
                </div>

                <!-- JBS Name -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">JBS Name</h5>
                    <div class="col-span-2">
                        <div v-if="editField === 'jbs_name'" class="flex gap-4 items-center">
                            <TextInput
                                v-model="generalInformationForm.jbs_name"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('jbs_name', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">{{ store.jbs_name }}</h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('jbs_name', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- Store Type -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Store Type</h5>
                    <div class="col-span-2">
                        <div v-if="editField === 'store_type'" class="flex gap-4 items-center">
                            <div class="w-full gap-4">
                                <DropdownSelect
                                    v-model="generalInformationForm.store_type"
                                    :value="generalInformationForm.store_type"
                                    custom-class="border-gray-300"
                                >
                                    <option
                                        v-for="(type, index) in storeTypes"
                                        :key="index"
                                        :value="type.value"
                                    >
                                        {{ type.label }}
                                    </option>
                                </DropdownSelect>
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('store_type', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{ store.store_type_label || 'N/A' }}
                            </h5>
                            <div class="flex gap-6">
                                <SecondaryButton
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="openHistoryModal('store_type')"
                                >
                                    History
                                </SecondaryButton>
                                <SecondaryButton
                                    v-if="canUpdateStores"
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="handleEdit('store_type', false)"
                                >
                                    Edit
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Store Group -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Store Group</h5>
                    <div class="col-span-2">
                        <div v-if="editField === 'store_group'" class="flex gap-4 items-center">
                            <div class="w-full gap-4">
                                <DropdownSelect
                                    v-model="generalInformationForm.store_group"
                                    :error="generalInformationForm.errors?.store_group"
                                    custom-class="border-gray-300"
                                >
                                    <option
                                        v-for="(store_group, index) in storeGroups"
                                        :key="index"
                                        :value="store_group.value"
                                    >
                                        {{ store_group.label }}
                                    </option>
                                </DropdownSelect>
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('store_group', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{ store.store_group_label || 'N/A' }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('store_group', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- Cluster Code & JBMIS Code (Coordinated) -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Cluster & JBMIS Codes</h5>
                    <div class="col-span-2">
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-gray-700">Cluster:</span>
                                        <span class="text-gray-900">{{ store.cluster_code || 'Not set' }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-gray-700">JBMIS:</span>
                                        <span class="text-gray-900">{{ store.jbmis_code || 'Not set' }}</span>
                                    </div>
                                </div>
                                <div class="flex gap-6">
                                    <SecondaryButton
                                        class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                        @click="openHistoryModal('cluster_jbmis_codes')"
                                    >
                                        History
                                    </SecondaryButton>
                                    <SecondaryButton
                                        v-if="canUpdateStores"
                                        class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                        @click="openEditClusterJbmisModal"
                                    >
                                        Edit Codes
                                    </SecondaryButton>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 italic">
                                These codes are linked and must be updated together for royalty processing.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Franchisee Code -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Franchisee Code</h5>
                    <div class="col-span-2">
                        <div class="flex justify-between items-center">
                            <h5 class="text-gray-900">{{ store.franchisee?.franchisee_code || '—' }}</h5>
                            <div class="flex gap-6">
                                <SecondaryButton
                                    v-if="canUpdateStores"
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="openHistoryModal('franchisee_id')"
                                >
                                    History
                                </SecondaryButton>
                                <SecondaryButton
                                    v-if="canUpdateStores"
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="openEditFranchiseeCodeModal"
                                >
                                    Edit
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales Point Code -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Sales Point Code</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'sales_point_code'"
                            class="flex gap-4 items-center"
                        >
                            <TextInput
                                v-model="generalInformationForm.sales_point_code"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('sales_point_code', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">{{ store.sales_point_code }}</h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('sales_point_code', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>


                <!-- Status -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Status</h5>
                    <div class="col-span-2">
                        <div v-if="editField === 'store_status'" class="flex gap-4 items-center">
                            <div class="flex-1">
                                <DropdownSelect
                                    v-model="generalInformationForm.store_status"
                                    :error="generalInformationForm.errors?.store_status"
                                    :value="generalInformationForm.store_status"
                                    custom-class="border-gray-300"
                                >
                                    <option
                                        v-for="(status, index) in statuses"
                                        :key="index"
                                        :value="status.value"
                                    >
                                        {{ status.label }}
                                    </option>
                                </DropdownSelect>
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('store_status', true)"
                                >
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton class="!font-medium" @click="handleUpdate(null)">
                                    Save
                                </PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <StatusBadge
                                :type="store.store_status"
                                category="storeStatus"
                                class="!rounded-full [&_svg]:hidden"
                            >
                                {{ store.store_status }}
                            </StatusBadge>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('store_status', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- Region -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Region</h5>
                    <div class="col-span-2">
                        <div v-if="editField === 'region'" class="flex gap-4 items-center">
                            <div class="w-full gap-4">
                                <DropdownSelect
                                    v-model="generalInformationForm.region"
                                    :value="generalInformationForm.region"
                                    custom-class="border-gray-300"
                                >
                                    <option
                                        v-for="(region, index) in regionsData"
                                        :key="index"
                                        :value="region.value"
                                    >
                                        {{ region.label }}
                                    </option>
                                </DropdownSelect>
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('region', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">{{ store.region }}</h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('region', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- Area -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Area</h5>
                    <div class="col-span-2">
                        <div v-if="editField === 'area'" class="flex gap-4 items-center">
                            <TextInput
                                v-model="generalInformationForm.area"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('area', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">{{ store.area }}</h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('area', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- District -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">District</h5>
                    <div class="col-span-2">
                        <div v-if="editField === 'district'" class="flex gap-4 items-center">
                            <TextInput
                                v-model="generalInformationForm.district"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('district', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">{{ store.district }}</h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('district', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- Google Maps Link -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Google Maps Link</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'google_maps_link'"
                            class="flex gap-4 items-center"
                        >
                            <TextInput
                                v-model="generalInformationForm.google_maps_link"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('google_maps_link', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">{{ store.google_maps_link }}</h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('google_maps_link', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <StoreHistoryModal
            :open="historyModalOpen"
            :selectedType="selectedHistoryField"
            :store-id="storeId"
            @close="historyModalOpen = false"
        />

        <EditFranchiseeCodeModal
            :open="editFranchiseeCodeModalOpen"
            :store="store"
            :page="page"
            :current-franchisee-code="store.franchisee?.franchisee_code"
            @close="editFranchiseeCodeModalOpen = false"
            @success="handleFranchiseeCodeUpdateSuccess"
            @update-franchisee="handleFranchiseeUpdate"
        />

        <EditClusterJbmisModal
            :open="editClusterJbmisModalOpen"
            :store="store"
            @close="editClusterJbmisModalOpen = false"
            @success="handleClusterJbmisUpdateSuccess"
        />
    </div>
</template>
