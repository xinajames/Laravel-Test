<script setup>
import { debounce } from 'lodash';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { vMaska } from 'maska/vue';

import Avatar from '@/Components/Common/Avatar/Avatar.vue';
import DropdownSelect from '@/Components/Common/Select/DropdownSelect.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import SingleFileUpload from '@/Components/Common/File/SingleFileUpload.vue';
import StatusBadge from '@/Components/Common/Badge/StatusBadge.vue';
import TextInput from '@/Components/Common/Input/TextInput.vue';

const props = defineProps({
    form: Object,
    franchisee: Object,
    withHeader: { type: Boolean, default: true },
    page: { type: String, default: 'create' },
});

const editField = ref(null);

const options = reactive({
    mask: '###-###-###-000',
    eager: true,
});

const profilePhotoPreview = ref(null);

const statuses = ref(null);

let profileForm = reactive({
    current_step: 'basic-details',
    errors: null,
});

const uploadError = ref(null);

function handleEdit(field, clear) {
    editField.value = clear ? null : field;
    if (clear) {
        delete profileForm[field]; // Remove field if clearing
    } else {
        profileForm[field] = props.franchisee[field]; // Set field value
    }
    profileForm.errors = null;
}

function handlePhoto(file) {
    let validFileTypes = ['image/jpg', 'image/jpeg', 'image/png'];
    if (!validFileTypes.includes(file.type)) {
        uploadError.value = 'Invalid file type. Please upload a .png, .jpeg, or .jpg file.';
    } else {
        if (props.page === 'edit') {
            props.form.profile_photo = file;
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = (e) => {
                profilePhotoPreview.value = e.target.result;
            };
        }
        profileForm.profile_photo = file;
        handleUpdate(props.page === 'update' ? 'profile_photo' : null);
    }
}

const handleUpdate = debounce(function (field) {
    if (props.page !== 'edit') {
        if (field) {
            profileForm[field] = props.form[field];
            profileForm.application_step = 'basic-details';
        }

        router.post(route('franchisees.update', props.franchisee.id), profileForm, {
            preserveScroll: true,
            onSuccess: () => {
                editField.value = null;
                profileForm = {
                    current_step: 'basic-details',
                    errors: null,
                };
            },
            onError: (errors) => {
                profileForm.errors = errors;
            },
        });
    }
}, 500);

function getStatuses() {
    let url = route('enums.getDataList', { key: 'franchisee-status-enum' });
    axios.get(url).then((response) => {
        statuses.value = response.data;
    });
}

watch(
    () => props.franchisee,
    (value) => {
        profilePhotoPreview.value = value.franchisee_profile_photo;
    },
    { immediate: true }
);

onMounted(() => {
    getStatuses();
});

defineExpose({
    profilePhotoPreview,
});

const canUpdateFranchisees = computed(() => {
    return usePage().props.auth.permissions.includes('update-franchisees');
});
</script>

<template>
    <div class="bg-white shadow sm:rounded-2xl p-6">
        <h4 v-if="withHeader" class="font-sans font-semibold">Profile Overview</h4>
        <!-- Create/Edit -->
        <div
            v-if="page === 'create' || page === 'edit'"
            :class="withHeader ? 'border-t mt-5' : ''"
            class="space-y-5"
        >
            <div :class="withHeader ? 'mt-5' : ''">
                <p class="text-sm text-gray-700 font-medium">
                    Photo
                    <span class="text-red-500">*</span>
                </p>

                <div class="mt-4 inline-flex items-center gap-4">
                    <Avatar
                        :image-url="profilePhotoPreview"
                        image-class="!size-14 !rounded-full !object-cover"
                    />

                    <SingleFileUpload
                        v-model="profilePhotoPreview"
                        :button-text="form.profile_photo ? 'Change' : 'Upload'"
                        :required="!profilePhotoPreview"
                        button-class="!py-2 !rounded-md !text-sm  !font-medium !relative !cursor-pointer"
                        file-types=".jpg,.jpeg,.png"
                        type="button"
                        @uploaded="handlePhoto($event)"
                    />
                </div>

                <p v-if="uploadError" class="text-red-500 text-sm mt-1">{{ uploadError }}</p>
            </div>

            <TextInput
                v-model="form.corporation_name"
                input-class="border-gray-300"
                label="Name of Corporation"
                @blur="handleUpdate('corporation_name')"
            />

            <div class="grid grid-cols-2 gap-4">
                <TextInput
                    v-model="form.last_name"
                    :error="form.errors?.last_name"
                    :required="true"
                    input-class="border-gray-300"
                    label="Last Name"
                    @blur="handleUpdate('last_name')"
                />

                <TextInput
                    v-model="form.first_name"
                    :error="form.errors?.first_name"
                    :required="true"
                    input-class="border-gray-300"
                    label="First Name"
                    @blur="handleUpdate('first_name')"
                />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <TextInput
                    v-model="form.middle_name"
                    :error="form.errors?.middle_name"
                    input-class="border-gray-300"
                    label="Middle Name"
                    @blur="handleUpdate('middle_name')"
                />

                <TextInput
                    v-model="form.name_suffix"
                    input-class="border-gray-300"
                    label="Suffix"
                    @blur="handleUpdate('name_suffix')"
                />
            </div>

            <div class="border-t">
                <div class="space-y-5 mt-5">
                    <DropdownSelect
                        v-model="form.status"
                        :error="form.errors?.status"
                        :required="true"
                        :value="form.status"
                        custom-class="border-gray-300 !w-40"
                        label="Status"
                        @update:modelValue="handleUpdate('status')"
                    >
                        <option
                            v-for="(status, index) in statuses"
                            :key="index"
                            :value="status.value"
                        >
                            {{ status.label }}
                        </option>
                    </DropdownSelect>

                    <TextInput
                        v-model="form.tin"
                        v-maska="options"
                        :error="form.errors?.tin"
                        :required="true"
                        input-class="border-gray-300"
                        label="Tin"
                        pattern=".{11,}"
                        @blur="handleUpdate('tin')"
                    />
                </div>
            </div>
        </div>
        <div v-else>
            <div>
                <div class="mt-5 inline-flex items-center gap-4">
                    <Avatar
                        :image-url="profilePhotoPreview"
                        image-class="!size-14 !rounded-full !object-cover"
                    />

                    <SingleFileUpload
                        v-if="canUpdateFranchisees"
                        v-model="profilePhotoPreview"
                        button-class="!py-2 !rounded-md !text-sm  !font-medium !relative !cursor-pointer"
                        button-text="Change"
                        type="button"
                        @uploaded="handlePhoto($event)"
                    />
                </div>
            </div>
            <div class="space-y-3 divide divide-y divide-gray-200">
                <div class="grid grid-cols-3 gap-4 items-center mt-5">
                    <h5 class="font-medium text-gray-500">Franchisee Code</h5>
                    <h5 class="text-gray-900">
                        {{ franchisee.franchisee_code }}
                    </h5>
                </div>
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Name of Corporation Code</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'corporation_name'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <TextInput
                                    v-model="profileForm.corporation_name"
                                    input-class="border-gray-300"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('corporation_name', true)"
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
                                {{ franchisee.corporation_name }}
                            </h5>

                            <SecondaryButton
                                v-if="canUpdateFranchisees"
                                class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('corporation_name', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Last Name</h5>
                    <div class="col-span-2">
                        <div v-if="editField === 'last_name'" class="flex gap-4 items-center">
                            <div class="flex-1">
                                <TextInput
                                    v-model="profileForm.last_name"
                                    :error="profileForm.errors?.last_name"
                                    input-class="border-gray-300"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('last_name', true)"
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
                                {{ franchisee.last_name }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateFranchisees"
                                class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('last_name', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">First Name</h5>
                    <div class="col-span-2">
                        <div v-if="editField === 'first_name'" class="flex gap-4 items-center">
                            <div class="flex-1">
                                <TextInput
                                    v-model="profileForm.first_name"
                                    :error="profileForm.errors?.first_name"
                                    input-class="border-gray-300"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('first_name', true)"
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
                                {{ franchisee.first_name }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateFranchisees"
                                class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('first_name', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Middle Name</h5>
                    <div class="col-span-2">
                        <div v-if="editField === 'middle_name'" class="flex gap-4 items-center">
                            <div class="flex-1">
                                <TextInput
                                    v-model="profileForm.middle_name"
                                    :error="profileForm.errors?.middle_name"
                                    input-class="border-gray-300"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('middle_name', true)"
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
                                {{ franchisee.middle_name || 'N/A' }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateFranchisees"
                                class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('middle_name', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Suffix</h5>
                    <div class="col-span-2">
                        <div v-if="editField === 'name_suffix'" class="flex gap-4 items-center">
                            <div class="flex-1">
                                <TextInput
                                    v-model="profileForm.name_suffix"
                                    input-class="border-gray-300"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('name_suffix', true)"
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
                                {{ franchisee.name_suffix || 'N/A' }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateFranchisees"
                                class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('name_suffix', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Status</h5>
                    <div class="col-span-2">
                        <div v-if="editField === 'status'" class="flex gap-4 items-center">
                            <div class="flex-1">
                                <DropdownSelect
                                    v-model="profileForm.status"
                                    custom-class="border-gray-300"
                                    :error="profileForm.errors?.status"
                                    :value="profileForm.status"
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
                                    @click="handleEdit('status', true)"
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
                                category="franchiseeStatus"
                                :type="franchisee.status === 1 ? 'Active' : 'Inactive'"
                                class="!rounded-full ! !font-medium [&_svg]:hidden"
                            >
                                {{ franchisee.status_description }}
                            </StatusBadge>
                            <SecondaryButton
                                v-if="canUpdateFranchisees"
                                class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('status', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">TIN</h5>
                    <div class="col-span-2">
                        <div v-if="editField === 'tin'" class="flex gap-4 items-center">
                            <div class="flex-1">
                                <TextInput
                                    v-model="profileForm.tin"
                                    input-class="border-gray-300"
                                    pattern=".{11,}"
                                    v-maska="options"
                                    :error="profileForm.errors?.tin"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('tin', true)"
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
                                {{ franchisee.tin || 'N/A' }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateFranchisees"
                                class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('tin', false)"
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
