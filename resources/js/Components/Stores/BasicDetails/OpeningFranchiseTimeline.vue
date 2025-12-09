<script setup>
import { computed, reactive, ref } from 'vue';
import { debounce } from 'lodash';
import { router, usePage } from '@inertiajs/vue3';
import Moment from 'moment';
import DatePicker from '@/Components/Common/DatePicker/DatePicker.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import StoreHistoryModal from '@/Components/Modal/StoreHistoryModal.vue';

const props = defineProps({
    form: Object,
    store: Object,
    withHeader: { type: Boolean, default: true },
    page: { type: String, default: 'create' },
});

const storeId = props.store?.id || props.form.store?.id;

const editField = ref(null);

let franchiseTimelineForm = reactive({
    current_step: 'basic-details',
    errors: null,
});

const historyModalOpen = ref(false);

const selectedHistoryField = ref(null);

const openHistoryModal = (field) => {
    selectedHistoryField.value = field;
    historyModalOpen.value = true;
};

function handleEdit(field, clear) {
    editField.value = clear ? null : field;
    if (clear) {
        delete franchiseTimelineForm[field]; // Remove field if clearing
    } else {
        franchiseTimelineForm[field] = props.store[field]; // Set field value
    }
    franchiseTimelineForm.errors = null;
}

const handleUpdate = debounce(function (field) {
    if (props.page !== 'edit') {
        if (field) {
            franchiseTimelineForm[field] = props.form[field];
            franchiseTimelineForm.application_step = 'basic-details';
        }
        router.post(route('stores.update', props.store.id), franchiseTimelineForm, {
            preserveScroll: true,
            onSuccess: () => {
                editField.value = null;
                franchiseTimelineForm = {
                    current_step: 'basic-details',
                    errors: null,
                };
            },
            onError: (errors) => {
                franchiseTimelineForm.errors = errors;
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
        <h4 v-if="withHeader" class="font-sans font-semibold">Opening & Franchise Timeline</h4>
        <!--Create & Edit Pages-->
        <div
            v-if="page === 'create' || page === 'edit'"
            :class="withHeader ? 'border-t mt-5 pt-5' : ''"
            class="space-y-5"
        >
            <div class="bg-white rounded-2xl">
                <h2 v-if="withHeader && page === 'update'" class="text-xl font-bold pb-4 border-b">
                    Opening & Franchise Timeline
                </h2>
                <div class="space-y-5">
                    <div class="lg:w-1/3">
                        <DatePicker
                            v-model="form.date_opened"
                            :text-input="true"
                            label="Date Opened"
                            placeholder="MM/DD/YYY"
                            @update:modelValue="handleUpdate('date_opened')"
                        />
                    </div>
                    <div
                        class="flex flex-col lg:flex-row gap-6 w-full pb-5 border-b border-gray-200"
                    >
                        <DatePicker
                            v-model="form.franchise_date"
                            :text-input="true"
                            class="w-full"
                            label="Franchise Date"
                            placeholder="MM/DD/YYYY"
                            @update:modelValue="handleUpdate('franchise_date')"
                        />
                        <DatePicker
                            v-model="form.original_franchise_date"
                            :text-input="true"
                            class="w-full"
                            label="Original Franchise Date"
                            placeholder="MM/DD/YYYY"
                            @update:modelValue="handleUpdate('original_franchise_date')"
                        />
                    </div>
                    <div class="flex flex-col lg:flex-row gap-6 w-full">
                        <DatePicker
                            v-model="form.renewal_date"
                            :text-input="true"
                            class="w-full"
                            label="Renewal Date"
                            placeholder="MM/DD/YYYY"
                            @update:modelValue="handleUpdate('renewal_date')"
                        />
                        <DatePicker
                            v-model="form.last_renewal_date"
                            :text-input="true"
                            class="w-full"
                            label="Last Renewal Date"
                            placeholder="MM/DD/YYYY"
                            @update:modelValue="handleUpdate('last_renewal_date')"
                        />
                    </div>

                    <div class="pb-5 border-b border-gray-200">
                        <div class="lg:w-1/3">
                            <DatePicker
                                v-model="form.effectivity_date"
                                :text-input="true"
                                label="Effectivity Date"
                                placeholder="MM/DD/YYY"
                                @update:modelValue="handleUpdate('effectivity_date')"
                            />
                        </div>
                    </div>

                    <div class="grid lg:grid-cols-3 gap-6">
                        <DatePicker
                            v-model="form.target_opening_date"
                            :text-input="true"
                            label="Target Opening"
                            placeholder="MM/DD/YYY"
                            @update:modelValue="handleUpdate('target_opening_date')"
                        />
                        <DatePicker
                            v-model="form.soft_opening_date"
                            :text-input="true"
                            label="Soft Opening"
                            placeholder="MM/DD/YYY"
                            @update:modelValue="handleUpdate('soft_opening_date')"
                        />
                        <DatePicker
                            v-model="form.grand_opening_date"
                            :text-input="true"
                            label="Grand Opening"
                            placeholder="MM/DD/YYY"
                            @update:modelValue="handleUpdate('grand_opening_date')"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="space-y-5 mt-5">
            <div class="space-y-3 divide-y divide-gray-200">
                <!-- Date Opened -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Date Opened</h5>
                    <div class="col-span-2">
                        <div v-if="editField === 'date_opened'" class="flex gap-4 items-center">
                            <div class="flex-1">
                                <DatePicker
                                    v-model="franchiseTimelineForm.date_opened"
                                    :text-input="true"
                                    placeholder="MM/DD/YYY"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('date_opened', true)"
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
                                    store.date_opened
                                        ? Moment(store.date_opened).format('MM/DD/YYYY')
                                        : 'N/A'
                                }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('date_opened', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- Franchise Date -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Franchise Date</h5>
                    <div class="col-span-2">
                        <div v-if="editField === 'franchise_date'" class="flex gap-4 items-center">
                            <div class="flex-1">
                                <DatePicker
                                    v-model="franchiseTimelineForm.franchise_date"
                                    :text-input="true"
                                    placeholder="MM/DD/YYY"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('franchise_date', true)"
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
                                    store.franchise_date
                                        ? Moment(store.franchise_date).format('MM/DD/YYYY')
                                        : 'N/A'
                                }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('franchise_date', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- Original Franchise Date -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Original Franchise Date</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'original_franchise_date'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <DatePicker
                                    v-model="franchiseTimelineForm.original_franchise_date"
                                    :text-input="true"
                                    placeholder="MM/DD/YYY"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('original_franchise_date', true)"
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
                                    store.original_franchise_date
                                        ? Moment(store.original_franchise_date).format('MM/DD/YYYY')
                                        : 'N/A'
                                }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('original_franchise_date', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- Renewal Date -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Renewal Date</h5>
                    <div class="col-span-2">
                        <div v-if="editField === 'renewal_date'" class="flex gap-4 items-center">
                            <div class="flex-1">
                                <DatePicker
                                    v-model="franchiseTimelineForm.renewal_date"
                                    :text-input="true"
                                    placeholder="MM/DD/YYY"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('renewal_date', true)"
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
                                    store.renewal_date
                                        ? Moment(store.renewal_date).format('MM/DD/YYYY')
                                        : 'N/A'
                                }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('renewal_date', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- Last Renewal Date -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Last Renewal Date</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'last_renewal_date'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <DatePicker
                                    v-model="franchiseTimelineForm.last_renewal_date"
                                    :text-input="true"
                                    placeholder="MM/DD/YYY"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('last_renewal_date', true)"
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
                                    store.last_renewal_date
                                        ? Moment(store.last_renewal_date).format('MM/DD/YYYY')
                                        : 'N/A'
                                }}
                            </h5>
                            <div class="flex gap-6">
                                <SecondaryButton
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="openHistoryModal('last_renewal_date')"
                                >
                                    History
                                </SecondaryButton>
                                <SecondaryButton
                                    v-if="canUpdateStores"
                                    class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                    @click="handleEdit('last_renewal_date', false)"
                                >
                                    Edit
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Effectivity Date -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Effectivity Date</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'effectivity_date'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <DatePicker
                                    v-model="franchiseTimelineForm.effectivity_date"
                                    :text-input="true"
                                    placeholder="MM/DD/YYY"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('effectivity_date', true)"
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
                                    store.effectivity_date
                                        ? Moment(store.effectivity_date).format('MM/DD/YYYY')
                                        : 'N/A'
                                }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('effectivity_date', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- Target Opening -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Target Opening</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'target_opening_date'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <DatePicker
                                    v-model="franchiseTimelineForm.target_opening_date"
                                    placeholder="MM/DD/YYY"
                                    :text-input="true"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('target_opening_date', true)"
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
                                    store.target_opening_date
                                        ? Moment(store.target_opening_date).format('MM/DD/YYYY')
                                        : 'N/A'
                                }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('target_opening_date', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- Soft Opening -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Soft Opening</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'soft_opening_date'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <DatePicker
                                    v-model="franchiseTimelineForm.soft_opening_date"
                                    placeholder="MM/DD/YYY"
                                    :text-input="true"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('soft_opening_date', true)"
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
                                    store.soft_opening_date
                                        ? Moment(store.soft_opening_date).format('MM/DD/YYYY')
                                        : 'N/A'
                                }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('soft_opening_date', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- Grand Opening -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Grand Opening</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'grand_opening_date'"
                            class="flex gap-4 items-center"
                        >
                            <div class="flex-1">
                                <DatePicker
                                    v-model="franchiseTimelineForm.grand_opening_date"
                                    placeholder="MM/DD/YYY"
                                    :text-input="true"
                                />
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    class="!font-medium"
                                    @click="handleEdit('grand_opening_date', true)"
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
                                    store.grand_opening_date
                                        ? Moment(store.grand_opening_date).format('MM/DD/YYYY')
                                        : 'N/A'
                                }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('grand_opening_date', false)"
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
            @close="historyModalOpen = false"
            :store-id="storeId"
            :selectedType="selectedHistoryField"
        />
    </div>
</template>
