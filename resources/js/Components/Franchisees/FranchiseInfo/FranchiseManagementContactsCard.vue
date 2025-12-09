<script setup>
import { debounce } from 'lodash';
import { computed, onMounted, reactive, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { vMaska } from 'maska/vue';
import DropdownSelect from '@/Components/Common/Select/DropdownSelect.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import TextInput from '@/Components/Common/Input/TextInput.vue';

const props = defineProps({
    form: Object,
    franchisee: Object,
    withHeader: { type: Boolean, default: true },
    page: { type: String, default: 'create' },
});

const cel = reactive({
    mask: '### ### ####',
    eager: true,
});

const point_persons = ref(null);

const editField = ref(null);

const regionsData = ref(null);

function getRegionsData() {
    let url = route('enums.getDataList', { key: 'region-dropdown' });
    axios.get(url).then((response) => {
        regionsData.value = response.data;
    });
}

let franchiseForm = reactive({
    current_step: 'franchisee-info',
    errors: null,
});

function handleEdit(field, clear) {
    editField.value = clear ? null : field;
    if (clear) {
        delete franchiseForm[field]; // Remove field if clearing
    } else {
        franchiseForm[field] = props.franchisee[field]; // Set field value
    }
    franchiseForm.errors = null;
}

const handleUpdate = debounce(function (field) {
    if (props.page !== 'edit') {
        if (field) {
            franchiseForm[field] = props.form[field];
            franchiseForm.application_step = 'franchisee-info';
        }
        router.post(route('franchisees.update', props.franchisee.id), franchiseForm, {
            preserveScroll: true,
            onSuccess: () => {
                editField.value = null;
                franchiseForm = {
                    current_step: 'franchisee-info',
                    errors: null,
                };
            },
            onError: (errors) => {
                franchiseForm.errors = errors;
            },
        });
    }
}, 500);

function getPointPersons() {
    let url = route('enums.getDataList', { key: 'point-person-dropdown' });
    axios.get(url).then((response) => {
        point_persons.value = response.data;
    });
}

onMounted(() => {
    getPointPersons();
    getRegionsData();
});

const canUpdateFranchisees = computed(() => {
    return usePage().props.auth.permissions.includes('update-franchisees');
});
</script>
<template>
    <div class="bg-white shadow sm:rounded-2xl p-6">
        <h4 v-if="withHeader" class="font-sans font-semibold">Franchise Management Contacts</h4>
        <div
            v-if="page === 'create' || page === 'edit'"
            class="space-y-5"
            :class="withHeader ? 'border-t mt-5' : ''"
        >
            <div :class="withHeader ? 'mt-5' : ''">
                <DropdownSelect
                    v-model="form.fm_point_person"
                    custom-class="border-gray-300 !w-40"
                    :value="form.fm_point_person"
                    :required="false"
                    label="Point Person"
                    @update:modelValue="handleUpdate('fm_point_person')"
                >
                    <option
                        v-for="(person, index) in point_persons"
                        :key="index"
                        :value="person.value"
                    >
                        {{ person.label }}
                    </option>
                </DropdownSelect>
            </div>

            <TextInput
                v-model="form.fm_district_manager"
                input-class="border-gray-300 !w-52"
                label="District Manager"
                @blur="handleUpdate('fm_district_manager')"
            />

            <DropdownSelect
                v-model="form.fm_region"
                :value="form.fm_region"
                custom-class="border-gray-300"
                label="Region"
                @update:modelValue="handleUpdate('fm_region')"
            >
                <option v-for="(region, index) in regionsData" :key="index" :value="region.value">
                    {{ region.label }}
                </option>
            </DropdownSelect>

            <div class="border-t space-y-5">
                <div class="mt-5 space-y-2">
                    <TextInput
                        v-model="form.fm_contact_number"
                        v-maska="cel"
                        input-class="border-gray-300"
                        label="Contact Numbers (up to 3)"
                        @blur="handleUpdate('fm_contact_number')"
                    />

                    <TextInput
                        v-model="form.fm_contact_number_2"
                        v-maska="cel"
                        input-class="border-gray-300"
                        @blur="handleUpdate('fm_contact_number_2')"
                    />

                    <TextInput
                        v-model="form.fm_contact_number_3"
                        v-maska="cel"
                        input-class="border-gray-300"
                        @blur="handleUpdate('fm_contact_number_3')"
                    />
                </div>
                <div class="space-y-2">
                    <TextInput
                        v-model="form.fm_email_address"
                        input-class="border-gray-300"
                        label="Email Address (up to 3)"
                        type="email"
                        @blur="handleUpdate('fm_email_address')"
                    />

                    <TextInput
                        v-model="form.fm_email_address_2"
                        input-class="border-gray-300"
                        type="email"
                        @blur="handleUpdate('fm_email_address_2')"
                    />

                    <TextInput
                        v-model="form.fm_email_address_3"
                        input-class="border-gray-300"
                        type="email"
                        @blur="handleUpdate('fm_email_address_3')"
                    />
                </div>
            </div>
        </div>
        <div v-else class="mt-5 space-y-3 divide divide-y divide-gray-200">
            <div class="grid grid-cols-3 gap-4 items-center">
                <h5 class="font-medium text-gray-500">Point Person</h5>
                <div class="col-span-2">
                    <div v-if="editField === 'fm_point_person'" class="flex gap-4 items-center">
                        <div class="flex-1">
                            <DropdownSelect
                                v-model="franchiseForm.fm_point_person"
                                custom-class="border-gray-300 !w-40"
                                :value="franchiseForm.fm_point_person"
                            >
                                <option
                                    v-for="(person, index) in point_persons"
                                    :key="index"
                                    :value="person.value"
                                >
                                    {{ person.label }}
                                </option>
                            </DropdownSelect>
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <SecondaryButton
                                class="!font-medium"
                                @click="handleEdit('fm_point_person', true)"
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
                            {{ franchisee.fm_point_person || 'N/A' }}
                        </h5>
                        <SecondaryButton
                            v-if="canUpdateFranchisees"
                            class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                            @click="handleEdit('fm_point_person', false)"
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
                        <div
                            v-if="editField === 'fm_contact_number'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <TextInput
                                    v-model="franchiseForm.fm_contact_number"
                                    v-maska="cel"
                                    input-class="border-gray-300"
                                    :error="franchiseForm.errors?.fm_contact_number"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('fm_contact_number', true)"
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
                                {{ franchisee.fm_contact_number || 'N/A' }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateFranchisees"
                                class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('fm_contact_number', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                    <div>
                        <div
                            v-if="editField === 'fm_contact_number_2'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <TextInput
                                    v-model="franchiseForm.fm_contact_number_2"
                                    v-maska="cel"
                                    input-class="border-gray-300"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('fm_contact_number_2', true)"
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
                                {{ franchisee.fm_contact_number_2 || 'N/A' }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateFranchisees"
                                class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('fm_contact_number_2', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                    <div>
                        <div
                            v-if="editField === 'fm_contact_number_3'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <TextInput
                                    v-model="franchiseForm.fm_contact_number_3"
                                    v-maska="cel"
                                    input-class="border-gray-300"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('fm_contact_number_3', true)"
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
                                {{ franchisee.fm_contact_number_3 || 'N/A' }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateFranchisees"
                                class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('fm_contact_number_3', false)"
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
                        <div
                            v-if="editField === 'fm_email_address'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <TextInput
                                    v-model="franchiseForm.fm_email_address"
                                    input-class="border-gray-300"
                                    :error="franchiseForm.errors?.fm_email_address"
                                    type="email"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('fm_email_address', true)"
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
                                {{ franchisee.fm_email_address || 'N/A' }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateFranchisees"
                                class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('fm_email_address', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>

                    <div>
                        <div
                            v-if="editField === 'fm_email_address_2'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <TextInput
                                    v-model="franchiseForm.fm_email_address_2"
                                    input-class="border-gray-300"
                                    type="email"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('fm_email_address_2', true)"
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
                                {{ franchisee.fm_email_address_2 || 'N/A' }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateFranchisees"
                                class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('fm_email_address_2', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                    <div>
                        <div
                            v-if="editField === 'fm_email_address_3'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <TextInput
                                    v-model="franchiseForm.fm_email_address_3"
                                    input-class="border-gray-300"
                                    type="email"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('fm_email_address_3', true)"
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
                                {{ franchisee.fm_email_address_3 || 'N/A' }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateFranchisees"
                                class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('fm_email_address_3', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 items-center pt-3">
                <h5 class="font-medium text-gray-500">District Manager</h5>
                <div class="col-span-2">
                    <div v-if="editField === 'fm_district_manager'" class="flex gap-4 items-center">
                        <div class="flex-1">
                            <TextInput
                                v-model="franchiseForm.fm_district_manager"
                                input-class="border-gray-300"
                            />
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <SecondaryButton
                                class="!font-medium"
                                @click="handleEdit('fm_district_manager', true)"
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
                            {{ franchisee.fm_district_manager || 'N/A' }}
                        </h5>
                        <SecondaryButton
                            v-if="canUpdateFranchisees"
                            class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                            @click="handleEdit('fm_district_manager', false)"
                        >
                            Edit
                        </SecondaryButton>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 items-center pt-3">
                <h5 class="font-medium text-gray-500">Region</h5>
                <div class="col-span-2">
                    <div v-if="editField === 'fm_region'" class="flex gap-4 items-center">
                        <div class="flex-1">
                            <DropdownSelect
                                v-model="franchiseForm.fm_region"
                                :value="franchiseForm.fm_region"
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
                            <SecondaryButton
                                class="!font-medium"
                                @click="handleEdit('fm_region', true)"
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
                            {{ franchisee.fm_region || 'N/A' }}
                        </h5>
                        <SecondaryButton
                            v-if="canUpdateFranchisees"
                            class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                            @click="handleEdit('fm_region', false)"
                        >
                            Edit
                        </SecondaryButton>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
