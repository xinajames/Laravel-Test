<script setup>
import { debounce } from 'lodash';
import { computed, reactive, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import DatePicker from '@/Components/Common/DatePicker/DatePicker.vue';
import Moment from 'moment';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';

const props = defineProps({
    form: Object,
    franchisee: Object,
    withHeader: { type: Boolean, default: true },
    page: { type: String, default: 'create' },
});

const editField = ref(null);

let applicationForm = reactive({
    current_step: 'franchisee-info',
    errors: null,
});

function handleEdit(field, clear) {
    editField.value = clear ? null : field;
    if (clear) {
        delete applicationForm[field]; // Remove field if clearing
    } else {
        applicationForm[field] = props.franchisee[field]; // Set field value
    }
    applicationForm.errors = null;
}

const handleUpdate = debounce(function (field) {
    if (props.page !== 'edit') {
        if (field) {
            applicationForm[field] = props.form[field];
            applicationForm.application_step = 'franchisee-info';
        }
        router.post(route('franchisees.update', props.franchisee.id), applicationForm, {
            preserveScroll: true,
            onSuccess: () => {
                editField.value = null;
                applicationForm = {
                    current_step: 'franchisee-info',
                    errors: null,
                };
            },
            onError: (errors) => {
                applicationForm.errors = errors;
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
        <h4 v-if="withHeader" class="font-sans font-semibold">Application Details</h4>
        <div
            v-if="page === 'create' || page === 'edit'"
            class="space-y-5"
            :class="withHeader ? 'border-t mt-5' : ''"
        >
            <div class="grid grid-cols-2 gap-4" :class="withHeader ? 'mt-5' : ''">
                <DatePicker
                    v-model="form.date_applied"
                    label="Application Date"
                    placeholder="MM/DD/YYY"
                    :text-input="true"
                    @update:modelValue="handleUpdate('date_applied')"
                />

                <DatePicker
                    v-model="form.date_approved"
                    label="Approved Date"
                    placeholder="MM/DD/YYY"
                    :text-input="true"
                    @update:modelValue="handleUpdate('date_approved')"
                />
            </div>
        </div>
        <div v-else class="mt-5 space-y-3 divide divide-y divide-gray-200">
            <div class="grid grid-cols-3 gap-4 items-center">
                <h5 class="font-medium text-gray-500">Application Date</h5>
                <div class="col-span-2">
                    <div v-if="editField === 'date_applied'" class="flex gap-4 items-center">
                        <div class="flex-1">
                            <DatePicker v-model="applicationForm.date_applied" :text-input="true" />
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <SecondaryButton
                                class="!font-medium"
                                @click="handleEdit('date_applied', true)"
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
                                franchisee.date_applied
                                    ? Moment(franchisee.date_applied).format('MM/DD/Y')
                                    : 'N/A'
                            }}
                        </h5>
                        <SecondaryButton
                            v-if="canUpdateFranchisees"
                            class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                            @click="handleEdit('date_applied', false)"
                        >
                            Edit
                        </SecondaryButton>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 items-center pt-3">
                <h5 class="font-medium text-gray-500">Approved Date</h5>
                <div class="col-span-2">
                    <div v-if="editField === 'date_approved'" class="flex gap-4 items-center">
                        <div class="flex-1">
                            <DatePicker
                                v-model="applicationForm.date_approved"
                                :text-input="true"
                            />
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <SecondaryButton
                                class="!font-medium"
                                @click="handleEdit('date_approved', true)"
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
                                franchisee.date_approved
                                    ? Moment(franchisee.date_approved).format('MM/DD/Y')
                                    : 'N/A'
                            }}
                        </h5>
                        <SecondaryButton
                            v-if="canUpdateFranchisees"
                            class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                            @click="handleEdit('date_approved', false)"
                        >
                            Edit
                        </SecondaryButton>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
