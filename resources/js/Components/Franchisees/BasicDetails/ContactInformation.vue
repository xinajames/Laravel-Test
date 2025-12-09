<script setup>
import { debounce } from 'lodash';
import { computed, reactive, ref, onMounted, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { vMaska } from 'maska/vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import TextInput from '@/Components/Common/Input/TextInput.vue';
import UpdateAddressModal from '@/Components/Modal/UpdateAddressModal.vue';
import SearchInputDropdown from '@/Components/Common/Select/SearchInputDropdown.vue';

const props = defineProps({
    form: Object,
    franchisee: Object,
    withHeader: { type: Boolean, default: true },
    page: { type: String, default: 'create' },
});

const editField = ref(null);
const locationsData = ref({});

let contactForm = reactive({
    current_step: 'basic-details',
    errors: null,
});

const cel = reactive({
    mask: '### ### ####',
    eager: true,
});

const postal_code = reactive({
    mask: '####',
    eager: true,
});

const updateAddressModalOpen = ref(false);

// New function to fetch location data
function getLocationsData() {
    let url = route('enums.getDataList', { key: 'locations-dropdown' });
    axios.get(url).then((response) => {
        locationsData.value = response.data[0].value;
    });
}

onMounted(() => {
    getLocationsData();
});

// New computed properties for dropdown options
const provinceOptions = computed(() => {
    return Object.keys(locationsData.value).map((province) => ({
        label: province,
        value: province,
    }));
});

const cityOptions = computed(() => {
    const selectedProvince = props.form.residential_address_province;
    if (selectedProvince && locationsData.value[selectedProvince]) {
        return Object.keys(locationsData.value[selectedProvince].cities).map((city) => ({
            label: city,
            value: city,
        }));
    }
    return [];
});

const barangayOptions = computed(() => {
    const selectedProvince = props.form.residential_address_province;
    const selectedCity = props.form.residential_address_city;
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

function handleDropdownUpdate(field, selectedOption) {
    if (selectedOption && selectedOption.value) {
        const valueChanged = props.form[field] !== selectedOption.value;

        props.form[field] = selectedOption.value;

        // Only clear dependent fields if the value actually changed
        if (valueChanged) {
            if (field === 'residential_address_province') {
                props.form.residential_address_city = '';
                props.form.residential_address_barangay = '';
            } else if (field === 'residential_address_city') {
                props.form.residential_address_barangay = '';
            }
        }
    }
}

function handleEdit(field, clear) {
    editField.value = clear ? null : field;
    if (clear) {
        delete contactForm[field]; // Remove field if clearing
    } else {
        contactForm[field] = props.franchisee[field]; // Set field value
    }
    contactForm.errors = null;
}

function save() {
    router.post(route('franchisees.update', props.franchisee.id), contactForm, {
        preserveScroll: true,
        onSuccess: () => {
            editField.value = null;
            contactForm = {
                current_step: 'basic-details',
                errors: null,
            };
        },
        onError: (e) => {
            contactForm.errors = e;
        },
    });
}

const handleUpdate = debounce(function (field) {
    if (props.page !== 'edit') {
        if (field) {
            contactForm[field] = props.form[field];
            contactForm.application_step = 'basic-details';
        }
        router.post(route('franchisees.update', props.franchisee.id), contactForm, {
            preserveScroll: true,
            onSuccess: () => {
                editField.value = null;
                contactForm = {
                    current_step: 'basic-details',
                    errors: null,
                };
            },
            onError: (errors) => {
                contactForm.errors = errors;
            },
        });
    }
}, 500);

const canUpdateFranchisees = computed(() => {
    return usePage().props.auth.permissions.includes('update-franchisees');
});
</script>
<template>
    <div class="bg-white shadow sm:rounded-2xl p-6">
        <h4 v-if="withHeader" class="font-sans font-semibold">Contact Information</h4>
        <div
            v-if="page === 'create' || page === 'edit'"
            class="space-y-5"
            :class="withHeader ? 'border-t mt-5' : ''"
        >
            <div :class="withHeader ? 'mt-5' : ''">
                <SearchInputDropdown
                    :dataList="provinceOptions"
                    v-model="form.residential_address_province"
                    :with-image="false"
                    :required="true"
                    label="Province"
                    @update-data="
                        (selectedOption) =>
                            handleDropdownUpdate('residential_address_province', selectedOption)
                    "
                    @blur="handleUpdate('residential_address_province')"
                />
            </div>

            <SearchInputDropdown
                :dataList="cityOptions"
                v-model="form.residential_address_city"
                :with-image="false"
                :required="true"
                label="City or Municipality"
                @update-data="
                    (selectedOption) =>
                        handleDropdownUpdate('residential_address_city', selectedOption)
                "
                @blur="handleUpdate('residential_address_city')"
            />

            <SearchInputDropdown
                :dataList="barangayOptions"
                v-model="form.residential_address_barangay"
                :with-image="false"
                :required="true"
                label="Barangay"
                @update-data="
                    (selectedOption) =>
                        handleDropdownUpdate('residential_address_barangay', selectedOption)
                "
                @blur="handleUpdate('residential_address_barangay')"
            />

            <TextInput
                v-model="form.residential_address_street"
                input-class="border-gray-300"
                label="Street"
                :required="true"
                @blur="handleUpdate('residential_address_street')"
            />

            <TextInput
                v-model="form.residential_address_postal"
                v-maska="postal_code"
                input-class="border-gray-300 !w-40"
                label="Postal Code"
                max-length="4"
                :required="true"
                @blur="handleUpdate('residential_address_postal')"
            />

            <div class="space-y-2">
                <TextInput
                    v-model="form.contact_number"
                    input-class="border-gray-300"
                    label="Contact Numbers (up to 3)"
                    max-length="13"
                    :required="true"
                    @blur="handleUpdate('contact_number')"
                />
                <TextInput
                    v-model="form.contact_number_2"
                    input-class="border-gray-300"
                    max-length="13"
                    @blur="handleUpdate('contact_number_2')"
                />
                <TextInput
                    v-model="form.contact_number_3"
                    input-class="border-gray-300"
                    max-length="13"
                    @blur="handleUpdate('contact_number_3')"
                />
            </div>

            <div class="space-y-2">
                <TextInput
                    v-model="form.email"
                    input-class="border-gray-300"
                    label="Email Address (up to 3)"
                    type="email"
                    :required="true"
                    @blur="handleUpdate('email')"
                />
                <TextInput
                    v-model="form.email_2"
                    input-class="border-gray-300"
                    type="email"
                    @blur="handleUpdate('email_2')"
                />
                <TextInput
                    v-model="form.email_3"
                    input-class="border-gray-300"
                    type="email"
                    @blur="handleUpdate('email_3')"
                />
            </div>
        </div>
        <div v-else class="mt-5 space-y-3 divide divide-y divide-gray-200">
            <div class="grid grid-cols-3 gap-4 items-center">
                <h5 class="font-medium text-gray-500">Residence Address</h5>
                <div class="col-span-2">
                    <div class="flex justify-between items-center">
                        <h5 class="text-gray-900">
                            {{ franchisee.full_residential_address || 'N/A' }}
                        </h5>
                        <SecondaryButton
                            v-if="canUpdateFranchisees"
                            class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                            @click="updateAddressModalOpen = true"
                        >
                            Edit
                        </SecondaryButton>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 items-start pt-3">
                <h5 class="font-medium text-gray-500">Contact Numbers</h5>
                <div class="col-span-2 space-y-3">
                    <div>
                        <div v-if="editField === 'contact_number'" class="flex gap-4 items-center">
                            <div class="flex-1">
                                <TextInput
                                    v-model="contactForm.contact_number"
                                    input-class="border-gray-300"
                                    v-maska="cel"
                                    :error="contactForm.errors?.contact_number"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('contact_number', true)"
                                >
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton class="!font-medium" @click="save">
                                    Save
                                </PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{ franchisee.contact_number || 'N/A' }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateFranchisees"
                                class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('contact_number', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                    <div>
                        <div
                            v-if="editField === 'contact_number_2'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <TextInput
                                    v-model="contactForm.contact_number_2"
                                    v-maska="cel"
                                    input-class="border-gray-300"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('contact_number_2', true)"
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
                                {{ franchisee.contact_number_2 || 'N/A' }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateFranchisees"
                                class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('contact_number_2', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                    <div>
                        <div
                            v-if="editField === 'contact_number_3'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <TextInput
                                    v-model="contactForm.contact_number_3"
                                    v-maska="cel"
                                    input-class="border-gray-300"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('contact_number_3', true)"
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
                                {{ franchisee.contact_number_3 || 'N/A' }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateFranchisees"
                                class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('contact_number_3', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 items-start pt-3">
                <h5 class="font-medium text-gray-500">Email Address</h5>
                <div class="col-span-2 space-y-3">
                    <div>
                        <div v-if="editField === 'email'" class="flex gap-4 items-center">
                            <div class="flex-1">
                                <TextInput
                                    v-model="contactForm.email"
                                    input-class="border-gray-300"
                                    type="email"
                                    :error="contactForm.errors?.email"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('email', true)"
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
                                {{ franchisee.email || 'N/A' }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateFranchisees"
                                class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('email', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                    <div>
                        <div v-if="editField === 'email_2'" class="flex gap-4 items-center">
                            <div class="flex-1">
                                <TextInput
                                    v-model="contactForm.email_2"
                                    input-class="border-gray-300"
                                    type="email"
                                    :error="contactForm.errors?.email_2"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('email_2', true)"
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
                                {{ franchisee.email_2 || 'N/A' }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateFranchisees"
                                class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('email_2', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                    <div>
                        <div v-if="editField === 'email_3'" class="flex gap-4 items-center">
                            <div class="flex-1">
                                <TextInput
                                    v-model="contactForm.email_3"
                                    input-class="border-gray-300"
                                    type="email"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('email_3', true)"
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
                                {{ franchisee.email_3 || 'N/A' }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateFranchisees"
                                class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('email_3', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <UpdateAddressModal
            :franchisee="franchisee"
            :open="updateAddressModalOpen"
            @close="updateAddressModalOpen = false"
        />
    </div>
</template>
