<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import DatePicker from '@/Components/Common/DatePicker/DatePicker.vue';
import DropdownSelect from '@/Components/Common/Select/DropdownSelect.vue';
import Moment from 'moment';
import TextInput from '@/Components/Common/Input/TextInput.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import NumberInput from '@/Components/Common/Input/NumberInput.vue';
import { debounce } from 'lodash';

const props = defineProps({
    form: Object,
    franchisee: Object,
    withHeader: { type: Boolean, default: true },
    page: { type: String, default: 'create' },
});

const age = computed(() => calculateAge(props.form.birthdate));

const editField = ref(null);

const genders = ref(null);

const marital_statuses = ref(null);

const nationalities = ref(null);

let personalInfoForm = reactive({
    current_step: 'basic-details',
    errors: null,
});

const religions = ref(null);

function calculateAge(dateString) {
    if (!dateString) return '';

    const [month, day, year] = dateString.split('-').map(Number);
    const birthDate = new Date(year, month - 1, day);

    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();

    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }

    return age > 1 ? age + ' yrs' : age + ' yr';
}

function handleEdit(field, clear) {
    editField.value = clear ? null : field;
    if (clear) {
        delete personalInfoForm[field]; // Remove field if clearing
    } else {
        personalInfoForm[field] = props.franchisee[field]; // Set field value
    }
    personalInfoForm.errors = null;
}

const handleUpdate = debounce(function (field) {
    if (props.page !== 'edit') {
        if (field) {
            personalInfoForm[field] = props.form[field];
            personalInfoForm.application_step = 'basic-details';
        }

        router.post(route('franchisees.update', props.franchisee.id), personalInfoForm, {
            preserveScroll: true,
            onSuccess: () => {
                editField.value = null;
                personalInfoForm = {
                    current_step: 'basic-details',
                    errors: null,
                };
            },
            onError: (errors) => {
                personalInfoForm.errors = errors;
            },
        });
    }
}, 500);

function getGenders() {
    let url = route('enums.getDataList', { key: 'gender-dropdown' });
    axios.get(url).then((response) => {
        genders.value = response.data;
    });
}

function getMaritalStatuses() {
    let url = route('enums.getDataList', { key: 'marital-status-dropdown' });
    axios.get(url).then((response) => {
        marital_statuses.value = response.data;
    });
}

function getNationalities() {
    let url = route('enums.getDataList', { key: 'nationality-dropdown' });
    axios.get(url).then((response) => {
        nationalities.value = response.data;
    });
}

function getReligions() {
    let url = route('enums.getDataList', { key: 'religion-dropdown' });
    axios.get(url).then((response) => {
        religions.value = response.data;
    });
}

onMounted(() => {
    getGenders();
    getMaritalStatuses();
    getNationalities();
    getReligions();
});

const canUpdateFranchisees = computed(() => {
    return usePage().props.auth.permissions.includes('update-franchisees');
});
</script>

<template>
    <div class="bg-white shadow sm:rounded-2xl p-6">
        <h4 v-if="withHeader" class="font-sans font-semibold">Personal Information</h4>
        <div
            v-if="page === 'create' || page === 'edit'"
            :class="withHeader ? 'border-t mt-5' : ''"
            class="space-y-5"
        >
            <div :class="withHeader ? 'mt-5' : ''" class="grid grid-cols-2 gap-4">
                <DatePicker
                    v-model="form.birthdate"
                    :error="form.errors?.birthdate"
                    :required="true"
                    :text-input="true"
                    label="Birthdate"
                    placeholder="MM/DD/YYY"
                    @update:modelValue="handleUpdate('birthdate')"
                />

                <TextInput
                    v-model="age"
                    :disabled="true"
                    input-class="border-gray-300"
                    label="Age"
                />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <DropdownSelect
                    v-model="form.gender"
                    :error="form.errors?.gender"
                    :required="true"
                    :value="form.gender"
                    custom-class="border-gray-300"
                    label="Gender"
                    @update:modelValue="handleUpdate('gender')"
                >
                    <option v-for="(gender, index) in genders" :key="index" :value="gender.value">
                        {{ gender.label }}
                    </option>
                </DropdownSelect>

                <DropdownSelect
                    v-model="form.nationality"
                    :error="form.errors?.nationality"
                    :required="true"
                    :value="form.nationality"
                    custom-class="border-gray-300"
                    label="Nationality"
                    @update:modelValue="handleUpdate('nationality')"
                >
                    <option
                        v-for="(nationality, index) in nationalities"
                        :key="index"
                        :value="nationality.value"
                    >
                        {{ nationality.label }}
                    </option>
                </DropdownSelect>
            </div>

            <DropdownSelect
                v-model="form.religion"
                :required="true"
                :value="form.religion"
                custom-class="border-gray-300 !w-40"
                label="Religion"
                @update:modelValue="handleUpdate('religion')"
            >
                <option v-for="(religion, index) in religions" :key="index" :value="religion.value">
                    {{ religion.value }}
                </option>
            </DropdownSelect>

            <hr />
            <div class="grid grid-cols-2 gap-4">
                <DropdownSelect
                    v-model="form.marital_status"
                    :error="form.errors?.marital_status"
                    :required="true"
                    :value="form.marital_status"
                    custom-class="border-gray-300"
                    label="Marital Status"
                    @update:modelValue="handleUpdate('marital_status')"
                >
                    <option
                        v-for="(status, index) in marital_statuses"
                        :key="index"
                        :value="status.value"
                    >
                        {{ status.label }}
                    </option>
                </DropdownSelect>

                <TextInput
                    v-model="form.spouse_name"
                    input-class="border-gray-300"
                    label="Spouse Name"
                    @blur="handleUpdate('spouse_name')"
                />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <DatePicker
                        v-model="form.spouse_birthdate"
                        :text-input="true"
                        label="Spouse B-Date"
                        placeholder="MM/DD/YYY"
                        @update:modelValue="handleUpdate('spouse_birthdate')"
                    />
                </div>

                <DatePicker
                    v-model="form.wedding_date"
                    :text-input="true"
                    label="Wedding Date"
                    placeholder="MM/DD/YYY"
                    @update:modelValue="handleUpdate('wedding_date')"
                />
            </div>

            <NumberInput
                v-model="form.number_of_children"
                :required="false"
                input-class="border-gray-300 !w-40"
                label="Kids"
                @blur="handleUpdate('number_of_children')"
            />
        </div>
        <div v-else class="mt-5 space-y-3 divide divide-y divide-gray-200">
            <div class="grid grid-cols-3 gap-4 items-center">
                <h5 class="font-medium text-gray-500">Birth Date</h5>
                <div class="col-span-2">
                    <div v-if="editField === 'birthdate'" class="flex gap-4 items-center">
                        <div class="flex-1">
                            <DatePicker
                                v-model="personalInfoForm.birthdate"
                                :error="personalInfoForm.errors?.birthdate"
                                :text-input="true"
                                placeholder="MM/DD/YYY"
                            />
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <SecondaryButton
                                class="!font-medium"
                                @click="handleEdit('birthdate', true)"
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
                                franchisee.birthdate
                                    ? Moment(franchisee.birthdate).format('M / DD / Y')
                                    : 'N/A'
                            }}
                        </h5>
                        <SecondaryButton
                            v-if="canUpdateFranchisees"
                            class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                            @click="handleEdit('birthdate', false)"
                        >
                            Edit
                        </SecondaryButton>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 items-center pt-3">
                <h5 class="font-medium text-gray-500">Age</h5>
                <h5 class="text-gray-900">
                    {{ franchisee.birthdate ? calculateAge(franchisee.birthdate) : 0 }}
                </h5>
            </div>
            <div class="grid grid-cols-3 gap-4 items-center pt-3">
                <h5 class="font-medium text-gray-500">Gender</h5>
                <div class="col-span-2">
                    <div v-if="editField === 'gender'" class="flex gap-4 items-center">
                        <div class="flex-1">
                            <DropdownSelect
                                v-model="personalInfoForm.gender"
                                :error="personalInfoForm.errors?.gender"
                                :value="personalInfoForm.gender"
                                custom-class="border-gray-300"
                            >
                                <option
                                    v-for="(gender, index) in genders"
                                    :key="index"
                                    :value="gender.value"
                                >
                                    {{ gender.label }}
                                </option>
                            </DropdownSelect>
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <SecondaryButton
                                class="!font-medium"
                                @click="handleEdit('gender', true)"
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
                            {{ franchisee.gender }}
                        </h5>
                        <SecondaryButton
                            v-if="canUpdateFranchisees"
                            class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                            @click="handleEdit('gender', false)"
                        >
                            Edit
                        </SecondaryButton>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 items-center pt-3">
                <h5 class="font-medium text-gray-500">Nationality</h5>
                <div class="col-span-2">
                    <div v-if="editField === 'nationality'" class="flex gap-4 items-center">
                        <div class="flex-1">
                            <DropdownSelect
                                v-model="personalInfoForm.nationality"
                                :error="personalInfoForm.errors?.nationality"
                                :value="personalInfoForm.nationality"
                                custom-class="border-gray-300"
                            >
                                <option
                                    v-for="(nationality, index) in nationalities"
                                    :key="index"
                                    :value="nationality.value"
                                >
                                    {{ nationality.label }}
                                </option>
                            </DropdownSelect>
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <SecondaryButton
                                class="!font-medium"
                                @click="handleEdit('nationality', true)"
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
                            {{ franchisee.nationality || 'N/A' }}
                        </h5>
                        <SecondaryButton
                            v-if="canUpdateFranchisees"
                            class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                            @click="handleEdit('nationality', false)"
                        >
                            Edit
                        </SecondaryButton>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 items-center pt-3">
                <h5 class="font-medium text-gray-500">Religion</h5>
                <div class="col-span-2">
                    <div v-if="editField === 'religion'" class="flex gap-4 items-center">
                        <div class="flex-1">
                            <DropdownSelect
                                v-model="personalInfoForm.religion"
                                :value="personalInfoForm.religion"
                                custom-class="border-gray-300"
                            >
                                <option
                                    v-for="(religion, index) in religions"
                                    :key="index"
                                    :value="religion.value"
                                >
                                    {{ religion.value }}
                                </option>
                            </DropdownSelect>
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <SecondaryButton
                                class="!font-medium"
                                @click="handleEdit('religion', true)"
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
                            {{ franchisee.religion || 'N/A' }}
                        </h5>
                        <SecondaryButton
                            v-if="canUpdateFranchisees"
                            class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                            @click="handleEdit('religion', false)"
                        >
                            Edit
                        </SecondaryButton>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 items-center pt-3">
                <h5 class="font-medium text-gray-500">Marital Status</h5>
                <div class="col-span-2">
                    <div v-if="editField === 'marital_status'" class="flex gap-4 items-center">
                        <div class="flex-1">
                            <DropdownSelect
                                v-model="personalInfoForm.marital_status"
                                :error="personalInfoForm.errors?.marital_status"
                                :value="personalInfoForm.marital_status"
                                custom-class="border-gray-300"
                            >
                                <option
                                    v-for="(status, index) in marital_statuses"
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
                                @click="handleEdit('marital_status', true)"
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
                            {{ franchisee.marital_status }}
                        </h5>
                        <SecondaryButton
                            v-if="canUpdateFranchisees"
                            class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                            @click="handleEdit('marital_status', false)"
                        >
                            Edit
                        </SecondaryButton>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 items-center pt-3">
                <h5 class="font-medium text-gray-500">Spouse Name</h5>
                <div class="col-span-2">
                    <div v-if="editField === 'spouse_name'" class="flex gap-4 items-center">
                        <div class="flex-1">
                            <TextInput
                                v-model="personalInfoForm.spouse_name"
                                :error="personalInfoForm.errors?.spouse_name"
                                input-class="border-gray-300"
                            />
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <SecondaryButton
                                class="!font-medium"
                                @click="handleEdit('spouse_name', true)"
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
                            {{ franchisee.spouse_name || 'N/A' }}
                        </h5>
                        <SecondaryButton
                            v-if="canUpdateFranchisees"
                            class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                            @click="handleEdit('spouse_name', false)"
                        >
                            Edit
                        </SecondaryButton>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 items-center pt-3">
                <h5 class="font-medium text-gray-500">Spouse B-Date</h5>
                <div class="col-span-2">
                    <div v-if="editField === 'spouse_birthdate'" class="flex gap-4 items-center">
                        <div class="flex-1">
                            <DatePicker
                                v-model="personalInfoForm.spouse_birthdate"
                                placeholder="MM/DD/YYY"
                                :text-input="true"
                            />
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <SecondaryButton
                                class="!font-medium"
                                @click="handleEdit('spouse_birthdate', true)"
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
                                franchisee.spouse_birthdate
                                    ? Moment(franchisee.spouse_birthdate).format('M / DD / Y')
                                    : 'N/A'
                            }}
                        </h5>
                        <SecondaryButton
                            v-if="canUpdateFranchisees"
                            class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                            @click="handleEdit('spouse_birthdate', false)"
                        >
                            Edit
                        </SecondaryButton>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 items-center pt-3">
                <h5 class="font-medium text-gray-500">Wedding Date</h5>
                <div class="col-span-2">
                    <div v-if="editField === 'wedding_date'" class="flex gap-4 items-center">
                        <div class="flex-1">
                            <DatePicker
                                v-model="personalInfoForm.wedding_date"
                                placeholder="MM/DD/YYY"
                                :text-input="true"
                            />
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <SecondaryButton
                                class="!font-medium"
                                @click="handleEdit('wedding_date', true)"
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
                                franchisee.wedding_date
                                    ? Moment(franchisee.wedding_date).format('M / DD / Y')
                                    : 'N/A'
                            }}
                        </h5>
                        <SecondaryButton
                            v-if="canUpdateFranchisees"
                            class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                            @click="handleEdit('wedding_date', false)"
                        >
                            Edit
                        </SecondaryButton>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 items-center pt-3">
                <h5 class="font-medium text-gray-500">Kids</h5>
                <div class="col-span-2">
                    <div v-if="editField === 'number_of_children'" class="flex gap-4 items-center">
                        <div class="flex-1">
                            <NumberInput
                                v-model="personalInfoForm.number_of_children"
                                custom-class="border-gray-300"
                                :error="personalInfoForm.errors?.number_of_children"
                                :value="personalInfoForm.number_of_children"
                            />
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <SecondaryButton
                                class="!font-medium"
                                @click="handleEdit('number_of_children', true)"
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
                            {{ franchisee.number_of_children }}
                        </h5>
                        <SecondaryButton
                            v-if="canUpdateFranchisees"
                            class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                            @click="handleEdit('number_of_children', false)"
                        >
                            Edit
                        </SecondaryButton>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
