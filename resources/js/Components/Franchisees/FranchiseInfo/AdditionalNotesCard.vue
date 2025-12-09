<script setup>
import { debounce } from 'lodash';
import { computed, reactive, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import DatePicker from '@/Components/Common/DatePicker/DatePicker.vue';
import Moment from 'moment';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import TextArea from '@/Components/Common/Input/TextArea.vue';

const props = defineProps({
    form: Object,
    franchisee: Object,
    withHeader: { type: Boolean, default: true },
    page: { type: String, default: 'create' },
});

const editField = ref(null);

let additionalForm = reactive({
    current_step: 'franchisee-info',
    errors: null,
});

function handleEdit(field, clear) {
    editField.value = clear ? null : field;
    if (clear) {
        delete additionalForm[field]; // Remove field if clearing
    } else {
        additionalForm[field] = props.franchisee[field]; // Set field value
    }
    additionalForm.errors = null;
}

const handleUpdate = debounce(function (field) {
    if (props.page !== 'edit') {
        if (field) {
            additionalForm[field] = props.form[field];
            additionalForm.application_step = 'franchisee-info';
        }

        router.post(route('franchisees.update', props.franchisee.id), additionalForm, {
            preserveScroll: true,
            onSuccess: () => {
                editField.value = null;
                additionalForm = {
                    current_step: 'franchisee-info',
                    errors: null,
                };
            },
            onError: (errors) => {
                additionalForm.errors = errors;
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
        <h4 v-if="withHeader" class="font-sans font-semibold">Additional Notes</h4>
        <div
            v-if="page === 'create' || page === 'edit'"
            class="space-y-5"
            :class="withHeader ? 'border-t mt-5' : ''"
        >
            <div :class="withHeader ? 'mt-5' : ''">
                <TextArea
                    v-model="form.remarks"
                    input-class="border-gray-300"
                    label="Remarks"
                    @blur="handleUpdate('remarks')"
                />
            </div>
            <div class="w-40">
                <DatePicker
                    v-model="form.date_separated"
                    label="Separation Date"
                    @update:modelValue="handleUpdate('date_separated')"
                />
            </div>
        </div>
        <div v-else class="mt-5 space-y-3 divide divide-y divide-gray-200">
            <div class="grid grid-cols-3 gap-4 items-center">
                <h5 class="font-medium text-gray-500">Remarks</h5>
                <div class="col-span-2">
                    <div v-if="editField === 'remarks'" class="flex gap-4 items-center">
                        <div class="flex-1">
                            <TextArea
                                v-model="additionalForm.remarks"
                                input-class="border-gray-300"
                            />
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <SecondaryButton
                                class="!font-medium"
                                @click="handleEdit('remarks', true)"
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
                            {{ franchisee.remarks || 'N/A' }}
                        </h5>
                        <SecondaryButton
                            v-if="canUpdateFranchisees"
                            class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                            @click="handleEdit('remarks', false)"
                        >
                            Edit
                        </SecondaryButton>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 items-center pt-3">
                <h5 class="font-medium text-gray-500">Separation Date</h5>
                <div class="col-span-2">
                    <div v-if="editField === 'date_separated'" class="flex gap-4 items-center">
                        <div class="flex-1">
                            <DatePicker v-model="additionalForm.date_separated" />
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <SecondaryButton
                                class="!font-medium"
                                @click="handleEdit('date_separated', true)"
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
                                franchisee.date_separated
                                    ? Moment(franchisee.date_separated).format('MM/DD/Y')
                                    : 'N/A'
                            }}
                        </h5>
                        <SecondaryButton
                            v-if="canUpdateFranchisees"
                            class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                            @click="handleEdit('date_separated', false)"
                        >
                            Edit
                        </SecondaryButton>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
