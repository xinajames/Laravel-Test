<script setup>
import { computed, reactive, ref, onMounted } from 'vue';
import { debounce } from 'lodash';
import { router, usePage } from '@inertiajs/vue3';
import { VMoney } from 'v-money';
import TextInput from '@/Components/Common/Input/TextInput.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';

const props = defineProps({
    form: Object,
    store: Object,
    withHeader: { type: Boolean, default: true },
    page: { type: String, default: 'create' },
});

const editField = ref(null);

let locationSpecificationForm = reactive({
    current_step: 'specifications',
    errors: null,
});

function handleEdit(field, clear) {
    editField.value = clear ? null : field;
    if (clear) {
        delete locationSpecificationForm[field]; // Remove field if clearing
    } else {
        locationSpecificationForm[field] = props.store[field]; // Set field value
    }
    locationSpecificationForm.errors = null;
}

const handleUpdate = debounce(function (field) {
    if (props.page !== 'edit') {
        if (field) {
            locationSpecificationForm[field] = props.form[field];
            locationSpecificationForm.application_step = 'specifications';
        }
        router.post(route('stores.update', props.store.id), locationSpecificationForm, {
            preserveScroll: true,
            onSuccess: () => {
                editField.value = null;
                locationSpecificationForm = {
                    current_step: 'specifications',
                    errors: null,
                };
            },
            onError: (errors) => {
                locationSpecificationForm.errors = errors;
            },
        });
    }
}, 500);

const vMoney = VMoney;

const money = reactive({ decimal: '.', thousands: ',', prefix: 'PHP ', precision: 2 });

const canUpdateStores = computed(() => {
    return usePage().props.auth.permissions.includes('update-stores');
});

function formatCurrency(value) {
    if (value == null || isNaN(value)) return '0.00';
    return new Intl.NumberFormat('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
}

onMounted(() => {
    if (
        props.form &&
        typeof props.form.defaults === 'function' &&
        typeof props.form.data === 'function'
    ) {
        props.form.defaults(props.form.data());
    }
});
</script>

<template>
    <div class="bg-white shadow sm:rounded-2xl p-6">
        <h4 v-if="withHeader" class="font-sans font-semibold">Location & Specifications</h4>
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
                    Location & Specifications
                </h2>
                <div class="space-y-5">
                    <TextInput
                        v-model="form.area_population"
                        input-class="border-gray-300"
                        label="Area Population"
                        @blur="handleUpdate('area_population')"
                    />
                    <TextInput
                        v-model="form.catchment"
                        input-class="border-gray-300"
                        label="Catchment"
                        @blur="handleUpdate('catchment')"
                    />
                    <TextInput
                        v-model="form.foot_traffic"
                        input-class="border-gray-300"
                        label="Foot Traffic"
                        @blur="handleUpdate('foot_traffic')"
                    />
                    <TextInput
                        v-model="form.manpower"
                        input-class="border-gray-300"
                        label="Manpower"
                        @blur="handleUpdate('manpower')"
                    />
                    <TextInput
                        v-model="form.rental"
                        input-class="border-gray-300"
                        label="Rental"
                        @blur="handleUpdate('rental')"
                    />
                    <TextInput
                        v-model="form.square_meter"
                        input-class="border-gray-300"
                        label="Square Meter"
                        @blur="handleUpdate('square_meter')"
                    />
                    <TextInput
                        v-model="form.sales_per_capita"
                        v-money="money"
                        input-class="border-gray-300"
                        label="Sales Per Capita"
                        @blur="handleUpdate('sales_per_capita')"
                    />
                    <TextInput
                        v-model="form.projected_peso_bread_sales_per_month"
                        v-money="money"
                        input-class="border-gray-300"
                        label="Projected Peso Bread Sales / Month"
                        @blur="handleUpdate('projected_peso_bread_sales_per_month')"
                    />
                    <TextInput
                        v-model="form.projected_peso_non_bread_sales_per_month"
                        v-money="money"
                        input-class="border-gray-300"
                        label="Projected Peso Non Bread Sales / Month"
                        @blur="handleUpdate('projected_peso_non_bread_sales_per_month')"
                    />
                    <TextInput
                        v-model="form.projected_total_cost"
                        v-money="money"
                        input-class="border-gray-300"
                        label="Total Projected Cost"
                        @blur="handleUpdate('projected_total_cost')"
                    />
                </div>
            </div>
        </div>
        <!--Show Page-->
        <div v-else class="space-y-5 mt-5">
            <div class="space-y-3 divide-y divide-gray-200">
                <!-- Area Population -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Area Population</h5>
                    <div class="col-span-2">
                        <div v-if="editField === 'area_population'" class="flex gap-4 items-center">
                            <TextInput
                                v-model="locationSpecificationForm.area_population"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('area_population', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">{{ store.area_population }}</h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('area_population', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
                <!-- Catchment -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Catchment</h5>
                    <div class="col-span-2">
                        <div v-if="editField === 'catchment'" class="flex gap-4 items-center">
                            <TextInput
                                v-model="locationSpecificationForm.catchment"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('catchment', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">{{ store.catchment }}</h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('catchment', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
                <!-- Foot Traffic -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Foot Traffic</h5>
                    <div class="col-span-2">
                        <div v-if="editField === 'foot_traffic'" class="flex gap-4 items-center">
                            <TextInput
                                v-model="locationSpecificationForm.foot_traffic"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('foot_traffic', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">{{ store.foot_traffic }}</h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('foot_traffic', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
                <!-- Manpower -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Manpower</h5>
                    <div class="col-span-2">
                        <div v-if="editField === 'manpower'" class="flex gap-4 items-center">
                            <TextInput
                                v-model="locationSpecificationForm.manpower"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('manpower', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">{{ store.manpower }}</h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('manpower', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
                <!-- Rental -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Rental</h5>
                    <div class="col-span-2">
                        <div v-if="editField === 'rental'" class="flex gap-4 items-center">
                            <TextInput
                                v-model="locationSpecificationForm.rental"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('rental', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">{{ store.rental }}</h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('rental', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
                <!-- Square Meter -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Square Meter</h5>
                    <div class="col-span-2">
                        <div v-if="editField === 'square_meter'" class="flex gap-4 items-center">
                            <TextInput
                                v-model="locationSpecificationForm.square_meter"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('square_meter', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">{{ store.square_meter }}</h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('square_meter', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
                <!-- Sales Per Capita -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Sales Per Capita</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'sales_per_capita'"
                            class="flex gap-4 items-center"
                        >
                            <TextInput
                                v-model="locationSpecificationForm.sales_per_capita"
                                v-money="money"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('sales_per_capita', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                PHP {{ formatCurrency(store.sales_per_capita) }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('sales_per_capita', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
                <!-- Projected Peso Bread Sales / Month -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Projected Peso Bread Sales / Month</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'projected_peso_bread_sales_per_month'"
                            class="flex gap-4 items-center"
                        >
                            <TextInput
                                v-model="
                                    locationSpecificationForm.projected_peso_bread_sales_per_month
                                "
                                v-money="money"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    @click="
                                        handleEdit('projected_peso_bread_sales_per_month', true)
                                    "
                                >
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                PHP {{ formatCurrency(store.projected_peso_bread_sales_per_month) }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('projected_peso_bread_sales_per_month', false)"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
                <!-- Projected Peso Non Bread Sales / Month -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">
                        Projected Peso Non Bread Sales / Month
                    </h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'projected_peso_non_bread_sales_per_month'"
                            class="flex gap-4 items-center"
                        >
                            <TextInput
                                v-model="
                                    locationSpecificationForm.projected_peso_non_bread_sales_per_month
                                "
                                v-money="money"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton
                                    @click="
                                        handleEdit('projected_peso_non_bread_sales_per_month', true)
                                    "
                                >
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                PHP
                                {{ formatCurrency(store.projected_peso_non_bread_sales_per_month) }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="
                                    handleEdit('projected_peso_non_bread_sales_per_month', false)
                                "
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
                <!-- Total Projected Cost -->
                <div class="grid grid-cols-3 gap-4 items-center pt-3">
                    <h5 class="font-medium text-gray-500">Total Projected Cost</h5>
                    <div class="col-span-2">
                        <div
                            v-if="editField === 'projected_total_cost'"
                            class="flex gap-4 items-center"
                        >
                            <TextInput
                                v-model="locationSpecificationForm.projected_total_cost"
                                v-money="money"
                                class="flex-1"
                                input-class="border-gray-300"
                            />
                            <div class="flex items-center gap-2 mt-1">
                                <SecondaryButton @click="handleEdit('projected_total_cost', true)">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton @click="handleUpdate(null)">Save</PrimaryButton>
                            </div>
                        </div>
                        <div v-else class="flex justify-between items-center">
                            <h5 class="text-gray-900">
                                PHP {{ formatCurrency(store.projected_total_cost) }}
                            </h5>
                            <SecondaryButton
                                v-if="canUpdateStores"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="handleEdit('projected_total_cost', false)"
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
