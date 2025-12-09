<script setup>
import { ArrowLongRightIcon } from '@heroicons/vue/24/solid/index.js';
import { computed, reactive, ref } from 'vue';
import { debounce } from 'lodash';
import { router, usePage } from '@inertiajs/vue3';
import DatePicker from '@/Components/Common/DatePicker/DatePicker.vue';
import Moment from 'moment';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import TextInput from '@/Components/Common/Input/TextInput.vue';

const props = defineProps({
    form: Object,
    franchisee: Object,
    withHeader: { type: Boolean, default: true },
    page: { type: String, default: 'create' },
});

const editField = ref(null);

let trainingForm = reactive({
    current_step: 'franchisee-info',
    errors: null,
});

function handleEdit(field, clear) {
    editField.value = clear ? null : field;
    if (clear) {
        delete trainingForm[field]; // Remove field if clearing
    } else {
        trainingForm[field] = props.franchisee[field]; // Set field value
    }

    if (field === 'date_start_bakery_management_seminar') {
        trainingForm.date_end_bakery_management_seminar =
            props.franchisee.date_end_bakery_management_seminar;
    }

    if (field === 'date_start_bread_baking_course') {
        trainingForm.date_end_bread_baking_course = props.franchisee.date_end_bread_baking_course;
    }
    trainingForm.errors = null;
}

function save() {
    router.post(route('franchisees.update', props.franchisee.id), trainingForm, {
        preserveScroll: true,
        onSuccess: () => {
            editField.value = null;
            trainingForm = {
                current_step: 'franchisee-info',
                errors: null,
            };
        },
        onError: (e) => {
            trainingForm.errors = e;
        },
    });
}

const handleUpdate = debounce(function (field) {
    if (props.page !== 'edit') {
        if (field) {
            trainingForm[field] = props.form[field];
            trainingForm.application_step = 'franchisee-info';
        }
        router.post(route('franchisees.update', props.franchisee.id), trainingForm, {
            preserveScroll: true,
            onSuccess: () => {
                editField.value = null;
                trainingForm = {
                    current_step: 'franchisee-info',
                    errors: null,
                };
            },
            onError: (errors) => {
                trainingForm.errors = errors;
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
        <h4 v-if="withHeader" class="font-sans font-semibold">Training and Manuals</h4>
        <div
            v-if="page === 'create' || page === 'edit'"
            class="space-y-5"
            :class="withHeader ? 'border-t mt-5' : ''"
        >
            <div class="grid grid-cols-11" :class="withHeader ? 'mt-5' : ''">
                <div class="col-span-5">
                    <DatePicker
                        v-model="form.date_start_bakery_management_seminar"
                        label="Bakery Management Seminar"
                        placeholder="MM/DD/YYY"
                        :text-input="true"
                        @update:modelValue="handleUpdate('date_start_bakery_management_seminar')"
                    />
                </div>

                <ArrowLongRightIcon
                    class="w-10 h-7 text-gray-400 self-center col-span-1 mx-4 mt-5"
                />

                <div class="col-span-5">
                    <DatePicker
                        v-model="form.date_end_bakery_management_seminar"
                        class="mt-6"
                        placeholder="MM/DD/YYY"
                        :text-input="true"
                        @update:modelValue="handleUpdate('date_end_bakery_management_seminar')"
                    />
                </div>
            </div>
            <div class="grid grid-cols-11">
                <div class="col-span-5">
                    <DatePicker
                        v-model="form.date_start_bread_baking_course"
                        label="Bread Baking Course"
                        placeholder="MM/DD/YYY"
                        :text-input="true"
                        @update:modelValue="handleUpdate('date_start_bread_baking_course')"
                    />
                </div>

                <ArrowLongRightIcon
                    class="w-10 h-7 text-gray-400 self-center col-span-1 mx-4 mt-5"
                />

                <div class="col-span-5">
                    <DatePicker
                        v-model="form.date_end_bread_baking_course"
                        class="mt-6"
                        placeholder="MM/DD/YYY"
                        :text-input="true"
                        @update:modelValue="handleUpdate('date_end_bread_baking_course')"
                    />
                </div>
            </div>
            <div class="border-t">
                <div class="grid grid-cols-2 gap-8 mt-5">
                    <TextInput
                        v-model="form.operations_manual_number"
                        input-class="border-gray-300"
                        label="Operations Manual Number"
                        @blur="handleUpdate('operations_manual_number')"
                    />

                    <TextInput
                        v-model="form.operations_manual_release"
                        input-class="border-gray-300"
                        label="Operations Manual Release"
                        @blur="handleUpdate('operations_manual_release')"
                    />
                </div>
            </div>
        </div>
        <div v-else class="mt-5 space-y-3 divide divide-y divide-gray-200">
            <div class="grid grid-cols-3 gap-4 items-center">
                <h5 class="font-medium text-gray-500">Bakery Management Seminar</h5>
                <div class="col-span-2">
                    <div
                        v-if="editField === 'date_start_bakery_management_seminar'"
                        class="flex gap-4 items-center"
                    >
                        <div class="flex-1">
                            <div class="grid grid-cols-7">
                                <div class="col-span-3">
                                    <DatePicker
                                        v-model="trainingForm.date_start_bakery_management_seminar"
                                        :text-input="true"
                                    />
                                </div>

                                <ArrowLongRightIcon
                                    class="w-10 h-7 text-gray-400 self-center col-span-1 mx-4"
                                />

                                <div class="col-span-3">
                                    <DatePicker
                                        v-model="trainingForm.date_end_bakery_management_seminar"
                                        :text-input="true"
                                    />
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <SecondaryButton
                                class="!font-medium"
                                @click="handleEdit('date_start_bakery_management_seminar', true)"
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
                                franchisee.date_start_bakery_management_seminar
                                    ? Moment(
                                          franchisee.date_start_bakery_management_seminar
                                      ).format('MM/DD/Y')
                                    : 'N/A'
                            }}
                            -
                            {{
                                franchisee.date_end_bakery_management_seminar
                                    ? Moment(franchisee.date_end_bakery_management_seminar).format(
                                          'MM/DD/Y'
                                      )
                                    : 'N/A'
                            }}
                        </h5>
                        <SecondaryButton
                            v-if="canUpdateFranchisees"
                            class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                            @click="handleEdit('date_start_bakery_management_seminar', false)"
                        >
                            Edit
                        </SecondaryButton>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 items-center pt-3">
                <h5 class="font-medium text-gray-500">Bread Baking Course</h5>
                <div class="col-span-2">
                    <div
                        v-if="editField === 'date_start_bread_baking_course'"
                        class="flex gap-4 items-center"
                    >
                        <div class="flex-1">
                            <div class="grid grid-cols-7">
                                <div class="col-span-3">
                                    <DatePicker
                                        v-model="trainingForm.date_start_bread_baking_course"
                                        :text-input="true"
                                    />
                                </div>

                                <ArrowLongRightIcon
                                    class="w-10 h-7 text-gray-400 self-center col-span-1 mx-4"
                                />

                                <div class="col-span-3">
                                    <DatePicker
                                        v-model="trainingForm.date_end_bread_baking_course"
                                        :text-input="true"
                                    />
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <SecondaryButton
                                class="!font-medium"
                                @click="handleEdit('date_end_bread_baking_course', true)"
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
                                franchisee.date_start_bread_baking_course
                                    ? Moment(franchisee.date_start_bread_baking_course).format(
                                          'MM/DD/Y'
                                      )
                                    : 'N/A'
                            }}
                            -
                            {{
                                franchisee.date_end_bread_baking_course
                                    ? Moment(franchisee.date_end_bread_baking_course).format(
                                          'MM/DD/Y'
                                      )
                                    : 'N/A'
                            }}
                        </h5>
                        <SecondaryButton
                            v-if="canUpdateFranchisees"
                            class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                            @click="handleEdit('date_start_bread_baking_course', false)"
                        >
                            Edit
                        </SecondaryButton>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 items-center pt-3">
                <h5 class="font-medium text-gray-500">Operations Manual Number</h5>
                <div class="col-span-2">
                    <div
                        v-if="editField === 'operations_manual_number'"
                        class="flex gap-4 items-center"
                    >
                        <div class="flex-1">
                            <TextInput
                                v-model="trainingForm.operations_manual_number"
                                input-class="border-gray-300"
                            />
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <SecondaryButton
                                class="!font-medium"
                                @click="handleEdit('operations_manual_number', true)"
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
                            {{ franchisee.operations_manual_number || 'N/A' }}
                        </h5>
                        <SecondaryButton
                            v-if="canUpdateFranchisees"
                            class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                            @click="handleEdit('operations_manual_number', false)"
                        >
                            Edit
                        </SecondaryButton>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 items-center pt-3">
                <h5 class="font-medium text-gray-500">Operations Manual Release</h5>
                <div class="col-span-2">
                    <div
                        v-if="editField === 'operations_manual_release'"
                        class="flex gap-4 items-center"
                    >
                        <div class="flex-1">
                            <TextInput
                                v-model="trainingForm.operations_manual_release"
                                input-class="border-gray-300"
                            />
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <SecondaryButton
                                class="!font-medium"
                                @click="handleEdit('operations_manual_release', true)"
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
                            {{ franchisee.operations_manual_release || 'N/A' }}
                        </h5>
                        <SecondaryButton
                            v-if="canUpdateFranchisees"
                            class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                            @click="handleEdit('operations_manual_release', false)"
                        >
                            Edit
                        </SecondaryButton>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
