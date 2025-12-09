<script setup>
import { computed, reactive, ref, onMounted } from 'vue';
import { debounce } from 'lodash';
import { router, usePage } from '@inertiajs/vue3';
import { vMaska } from 'maska/vue';
import NumberInput from '@/Components/Common/Input/NumberInput.vue';
import TextInput from '@/Components/Common/Input/TextInput.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import UpdateAddressModal from '@/Components/Modal/UpdateAddressModal.vue';
import SearchInputDropdown from '@/Components/Common/Select/SearchInputDropdown.vue';

const props = defineProps({
    form: Object,
    store: Object,
    withHeader: { type: Boolean, default: true },
    page: { type: String, default: 'create' },
});

const locationsData = ref({});

function getLocations() {
    let url = route('enums.getDataList', { key: 'locations-dropdown' });
    axios.get(url).then((response) => {
        locationsData.value = response.data[0].value;
    });
}

onMounted(() => {
    getLocations();
});

const postal_code = reactive({
    mask: '####',
    eager: true,
});

const tel = reactive({
    mask: '## ### ####',
    eager: true,
});

const cel = reactive({
    mask: '### ### ####',
    eager: true,
});

const editField = ref(null);

const updateAddressModalOpen = ref(false);

let storeAddressForm = reactive({
    current_step: 'contact-info',
    errors: null,
});

const provinceOptions = computed(() => {
    return Object.keys(locationsData.value).map((province) => ({
        label: province,
        value: province,
    }));
});

const cityOptions = computed(() => {
    const selectedProvince = props.form.store_province;
    if (selectedProvince && locationsData.value[selectedProvince]) {
        return Object.keys(locationsData.value[selectedProvince].cities).map((city) => ({
            label: city,
            value: city,
        }));
    }
    return [];
});

const barangayOptions = computed(() => {
    const selectedProvince = props.form.store_province;
    const selectedCity = props.form.store_city;
    if (
        selectedProvince &&
        selectedCity &&
        locationsData.value[selectedProvince] &&
        locationsData.value[selectedProvince].cities[selectedCity]
    ) {
        return locationsData.value[selectedProvince].cities[selectedCity].barangays.map(
            (barangay) => ({
                label: barangay,
                value: barangay,
            })
        );
    }
    return [];
});

function handleUpdateData(field, selectedOption) {
    const valueChanged = props.form[field] !== selectedOption.value;

    props.form[field] = selectedOption.value;

    // Only clear dependent fields if the value actually changed
    if (valueChanged) {
        if (field === 'store_province') {
            props.form.store_city = '';
            props.form.store_barangay = '';
        }
        if (field === 'store_city') {
            props.form.store_barangay = '';
        }
    }
}

function handleEdit(field, clear) {
    editField.value = clear ? null : field;
    if (clear) {
        delete storeAddressForm[field]; // Remove field if clearing
    } else {
        storeAddressForm[field] = props.store[field]; // Set field value
    }
    storeAddressForm.errors = null;
}

const handleUpdate = debounce(function (field) {
    if (props.page !== 'edit') {
        if (field) {
            storeAddressForm[field] = props.form[field];
            storeAddressForm.application_step = 'contact-info';
        }
        router.post(route('stores.update', props.store.id), storeAddressForm, {
            preserveScroll: true,
            onSuccess: () => {
                editField.value = null;
                storeAddressForm = {
                    current_step: 'contact-info',
                    errors: null,
                };
            },
            onError: (errors) => {
                storeAddressForm.errors = errors;
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
        <h4 v-if="withHeader" class="font-sans font-semibold">
            Store Address & Communication Details
        </h4>
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
                    Store Address & Communication Details
                </h2>
                <div class="space-y-5">
                    <SearchInputDropdown
                        :dataList="provinceOptions"
                        v-model="form.store_province"
                        :with-image="false"
                        :required="true"
                        label="Province"
                        @update-data="
                            (selectedOption) => handleUpdateData('store_province', selectedOption)
                        "
                        @blur="handleUpdate('store_province')"
                    />
                    <SearchInputDropdown
                        :dataList="cityOptions"
                        v-model="form.store_city"
                        :with-image="false"
                        :required="true"
                        label="City"
                        @update-data="
                            (selectedOption) => handleUpdateData('store_city', selectedOption)
                        "
                        @blur="handleUpdate('store_city')"
                    />
                    <SearchInputDropdown
                        :dataList="barangayOptions"
                        v-model="form.store_barangay"
                        :with-image="false"
                        :required="true"
                        label="Barangay"
                        @update-data="
                            (selectedOption) => handleUpdateData('store_barangay', selectedOption)
                        "
                        @blur="handleUpdate('store_barangay')"
                    />
                    <TextInput
                        v-model="form.store_street"
                        input-class="border-gray-300"
                        label="Street"
                        @blur="handleUpdate('store_street')"
                    />
                    <TextInput
                        v-model="form.store_postal_code"
                        v-maska="postal_code"
                        input-class="border-gray-300 !w-40"
                        label="Postal Code"
                        :required="true"
                        @blur="handleUpdate('store_postal_code')"
                    />
                    <div class="grid md:grid-cols-2 gap-4">
                        <NumberInput
                            v-model="form.store_phone_number"
                            v-maska="tel"
                            class="w-full"
                            input-class="border-gray-300"
                            label="Telephone Number"
                            showLeftSymbol
                            type="text"
                            @blur="handleUpdate('store_phone_number')"
                        />
                        <NumberInput
                            v-model="form.store_mobile_number"
                            v-maska="cel"
                            class="w-full"
                            input-class="border-gray-300"
                            label="Cellphone Number"
                            leftSymbol="+63"
                            showLeftSymbol
                            type="text"
                            @blur="handleUpdate('store_mobile_number')"
                        />
                    </div>
                    <TextInput
                        v-model="form.store_email"
                        input-class="border-gray-300"
                        label="Email"
                        type="email"
                        @blur="handleUpdate('store_email')"
                    />
                </div>
            </div>
        </div>

        <div v-else class="space-y-5 mt-5">
            <div class="space-y-3 divide-y divide-gray-200">
                <!-- Store Address -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Store Address</h5>
                    <div class="col-span-2">
                        <div class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{ store.full_store_address || 'N/A' }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="updateAddressModalOpen = true"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
                <!-- Telephone Number -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Telephone Number</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'store_phone_number'"
                            class="flex gap-4 items-center"
                        >
                            <NumberInput
                                v-model="storeAddressForm.store_phone_number"
                                v-maska="tel"
                                class="flex-1"
                                input-class="border-gray-300"
                                type="text"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('store_phone_number', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{ store.store_phone_number || 'N/A' }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('store_phone_number', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
                <!-- Cellphone Number -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Cellphone Number</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'store_mobile_number'"
                            class="flex gap-4 items-center"
                        >
                            <NumberInput
                                v-model="storeAddressForm.store_mobile_number"
                                v-maska="tel"
                                class="flex-1"
                                input-class="border-gray-300"
                                leftSymbol="+63"
                                showLeftSymbol
                                type="text"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('store_mobile_number', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{ store.store_mobile_number || 'N/A' }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('store_mobile_number', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
                <!-- Email -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Email</h5>
                    <div class="col-span-2">
                        <div v-if="editField === 'store_email'" class="flex gap-4 items-center">
                            <TextInput
                                v-model="storeAddressForm.store_email"
                                class="flex-1"
                                input-class="border-gray-300"
                                type="email"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('store_email', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{ store.store_email || 'N/A' }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('store_email', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <UpdateAddressModal
        :store="store"
        :open="updateAddressModalOpen"
        @close="updateAddressModalOpen = false"
    />
</template>
