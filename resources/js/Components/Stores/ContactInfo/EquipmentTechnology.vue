<script setup>
import { computed, reactive, ref } from 'vue';
import { debounce } from 'lodash';
import { router, usePage } from '@inertiajs/vue3';
import Moment from 'moment';
import RadioGroup from '@/Components/Common/Radio/RadioGroup.vue';
import DatePicker from '@/Components/Common/DatePicker/DatePicker.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import TextInput from '@/Components/Common/Input/TextInput.vue';

const props = defineProps({
    form: Object,
    store: Object,
    withHeader: { type: Boolean, default: true },
    page: { type: String, default: 'create' },
});

const cctv = [
    { id: '1', label: 'Yes', value: 1 },
    { id: '2', label: 'No', value: 0 },
];

const internet = [
    { id: '1', label: 'Yes', value: 1 },
    { id: '2', label: 'No', value: 0 },
];

const pos = [
    { id: '1', label: 'Yes', value: 1 },
    { id: '2', label: 'No', value: 0 },
];

const editField = ref(null);

let equipmentTechnologyForm = reactive({
    current_step: 'contact-info',
    errors: null,
});

function handleEdit(field, clear) {
    editField.value = clear ? null : field;
    if (clear) {
        delete equipmentTechnologyForm[field]; // Remove field if clearing
    } else {
        equipmentTechnologyForm[field] = props.store[field]; // Set field value
    }
    equipmentTechnologyForm.errors = null;
}

const handleUpdate = debounce(function (field) {
    if (props.page !== 'edit') {
        if (field) {
            equipmentTechnologyForm[field] = props.form[field];
            equipmentTechnologyForm.application_step = 'contact-info';
        }
        router.post(route('stores.update', props.store.id), equipmentTechnologyForm, {
            preserveScroll: true,
            onSuccess: () => {
                editField.value = null;
                equipmentTechnologyForm = {
                    current_step: 'contact-info',
                    errors: null,
                };
            },
            onError: (errors) => {
                equipmentTechnologyForm.errors = errors;
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
        <h4 v-if="withHeader" class="font-sans font-semibold">Equipment & Technology</h4>
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
                    Equipment & Technology
                </h2>
                <div class="space-y-5">
                    <div class="grid md:grid-cols-2 gap-6 pb-6 border-b border-gray-200">
                        <RadioGroup
                            v-model="form.with_cctv"
                            :options="cctv"
                            color="#A32130"
                            label="with CCTV"
                            name="with CCTV"
                            orientation="inline"
                            radioPosition="left"
                            @update:modelValue="handleUpdate('with_cctv')"
                        />
                        <DatePicker
                            v-model="form.cctv_installed_at"
                            :text-input="true"
                            class="w-full"
                            label="Installation Date"
                            placeholder="MM/DD/YYYY"
                            @update:modelValue="handleUpdate('cctv_installed_at')"
                        />
                    </div>
                    <div class="grid md:grid-cols-2 gap-6 pb-6 border-b border-gray-200">
                        <RadioGroup
                            v-model="form.with_internet"
                            :options="internet"
                            color="#A32130"
                            label="with Internet"
                            name="with Internet"
                            orientation="inline"
                            radioPosition="left"
                            @update:modelValue="handleUpdate('with_internet')"
                        />
                        <DatePicker
                            v-model="form.internet_installed_at"
                            :text-input="true"
                            class="w-full"
                            label="Installation Date"
                            placeholder="MM/DD/YYYY"
                            @update:modelValue="handleUpdate('internet_installed_at')"
                        />
                    </div>
                    <div class="grid md:grid-cols-2 gap-6">
                        <RadioGroup
                            v-model="form.with_pos"
                            :options="pos"
                            color="#A32130"
                            label="With POS"
                            name="with_pos"
                            orientation="inline"
                            radioPosition="left"
                            @update:modelValue="handleUpdate('with_pos')"
                        />
                        <div class="space-y-4">
                            <TextInput
                                v-model="form.pos_name"
                                input-class="border-gray-300"
                                label="POS"
                                @blur="handleUpdate('pos_name')"
                            />
                            <DatePicker
                                v-model="form.pos_installed_at"
                                :text-input="true"
                                class="w-full"
                                label="Installation Date"
                                placeholder="MM/DD/YYYY"
                                @update:modelValue="handleUpdate('pos_installed_at')"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--Show Page-->
        <div v-else class="space-y-5 mt-5">
            <div class="space-y-3 divide-y divide-gray-200">
                <!-- with CCTV -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">with CCTV</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'with_cctv'"
                            class="flex gap-4 items-center justify-between"
                        >
                            <RadioGroup
                                v-model="equipmentTechnologyForm.with_cctv"
                                :model-value="equipmentTechnologyForm.with_cctv"
                                :options="cctv"
                                color="#A32130"
                                name="with CCTV"
                                orientation="inline"
                                radioPosition="left"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('with_cctv', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{ store.with_cctv === 1 ? 'Yes' : 'No' }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('with_cctv', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
                <!-- Installation Date -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Installation Date</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'cctv_installed_at'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <DatePicker
                                    v-model="equipmentTechnologyForm.cctv_installed_at"
                                    :text-input="true"
                                    placeholder="MM/DD/YYY"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('cctv_installed_at', true)"
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
                                    store.cctv_installed_at
                                        ? Moment(store.cctv_installed_at).format('MM/DD/YYYY')
                                        : 'N/A'
                                }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('cctv_installed_at', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
                <!-- with Internet -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">with Internet</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'with_internet'"
                            class="flex gap-4 items-center justify-between"
                        >
                            <RadioGroup
                                v-model="equipmentTechnologyForm.with_internet"
                                :model-value="equipmentTechnologyForm.with_internet"
                                :options="internet"
                                color="#A32130"
                                name="with Internet"
                                orientation="inline"
                                radioPosition="left"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('with_internet', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{ store.with_internet === 1 ? 'Yes' : 'No' }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('with_internet', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
                <!-- Installation Date -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Installation Date</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'internet_installed_at'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <DatePicker
                                    v-model="equipmentTechnologyForm.internet_installed_at"
                                    :text-input="true"
                                    placeholder="MM/DD/YYY"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('internet_installed_at', true)"
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
                                    store.internet_installed_at
                                        ? Moment(store.internet_installed_at).format('MM/DD/YYYY')
                                        : 'N/A'
                                }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('internet_installed_at', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
                <!-- with POS -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">with POS</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'with_pos'"
                            class="flex gap-4 items-center justify-between"
                        >
                            <RadioGroup
                                v-model="equipmentTechnologyForm.with_pos"
                                :model-value="equipmentTechnologyForm.with_pos"
                                :options="pos"
                                color="#A32130"
                                name="with POS"
                                orientation="inline"
                                radioPosition="left"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('with_pos', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{ store.with_pos === 1 ? 'Yes' : 'No' }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('with_pos', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
                <!-- POS Name -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">POS</h5>
                    <div class="col-span-2">
                        <div v-if="editField === 'pos_name'" class="flex gap-4 items-center">
                            <TextInput
                                v-model="equipmentTechnologyForm.pos_name"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('pos_name', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">{{ store.pos_name }}</h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('pos_name', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
                <!-- POS - Installation Date -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Installation Date</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'pos_installed_at'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <DatePicker
                                    v-model="equipmentTechnologyForm.pos_installed_at"
                                    :text-input="true"
                                    placeholder="MM/DD/YYY"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('pos_installed_at', true)"
                                >
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton class="!font-medium" @click="handleUpdate">
                                    Save
                                </PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                {{
                                    store.pos_installed_at
                                        ? Moment(store.pos_installed_at).format('MM/DD/YYYY')
                                        : 'N/A'
                                }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('pos_installed_at', false)"
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
