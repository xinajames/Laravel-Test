<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline/index.js';
import { onMounted, onUnmounted, ref } from 'vue';
import Layout from '@/Layouts/Admin/Layout.vue';
import Breadcrumbs from '@/Components/Shared/Breadcrumbs.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import GeneralInformationCard from '@/Components/Stores/BasicDetails/GeneralInformationCard.vue';
import ManagementOperationCard from '@/Components/Stores/BasicDetails/ManagementOperationCard.vue';
import OpeningFranchiseTimeline from '@/Components/Stores/BasicDetails/OpeningFranchiseTimeline.vue';
import FinancialLegalDocuments from '@/Components/Stores/Specifications/FinancialLegalDocuments.vue';
import LocationSpecification from '@/Components/Stores/Specifications/LocationSpecification.vue';
import LeaseLegalInformation from '@/Components/Stores/Specifications/LeaseLegalInformation.vue';
import StoreMaintenanceUpdate from '@/Components/Stores/Specifications/StoreMaintenanceUpdate.vue';
import StoreAddressCard from '@/Components/Stores/ContactInfo/StoreAddressCard.vue';
import EquipmentTechnology from '@/Components/Stores/ContactInfo/EquipmentTechnology.vue';
import OthersDetailsCard from '@/Components/Stores/ContactInfo/OthersDetailsCard.vue';
import LeaveConfirmationModal from '@/Components/Modal/LeaveConfirmationModal.vue';

const props = defineProps({
    store: Object,
});

const navigations = ref([]);

const activeNav = ref('General Information');
const nextNav = ref(null);
const activeForm = ref(null);
const redirectRoute = ref(null);
const leaveModalOpen = ref(false);
const saving = ref(false);

const generalInformationForm = useForm({
    store_code: props.store?.store_code ?? '',
    jbs_name: props.store?.jbs_name ?? '',
    store_type: props.store?.store_type ?? '',
    store_group: props.store?.store_group ?? '',
    cluster_code: props.store?.cluster_code ?? '',
    franchisee_id: props.store?.franchisee_id ?? '',
    jbmis_code: props.store?.jbmis_code ?? '',
    sales_point_code: props.store?.sales_point_code ?? '',
    store_status: props.store?.store_status ?? '',
    region: props.store?.region ?? '',
    area: props.store?.area ?? '',
    district: props.store?.district ?? '',
    google_maps_link: props.store?.google_maps_link ?? '',
    current_step: 'basic-details',
});

const managementOperationForm = useForm({
    om_district_code: props.store?.om_district_code ?? '',
    om_district_name: props.store?.om_district_name ?? '',
    om_district_manager: props.store?.om_district_manager ?? '',
    om_cost_center_code: props.store?.om_cost_center_code ?? '',
    old_continuing_license_fee: props.store?.old_continuing_license_fee ?? '',
    current_continuing_license_fee: props.store?.current_continuing_license_fee ?? '',
    continuing_license_fee_in_effect: props.store?.continuing_license_fee_in_effect ?? '',
    brf_in_effect: props.store?.brf_in_effect ?? '',
    report_percent: props.store?.report_percent ?? '',
    current_step: 'basic-details',
});

const openingFranchiseTimelineForm = useForm({
    date_opened: props.store?.date_opened ?? '',
    franchise_date: props.store?.franchise_date ?? '',
    original_franchise_date: props.store?.original_franchise_date ?? '',
    renewal_date: props.store?.renewal_date ?? '',
    last_renewal_date: props.store?.last_renewal_date ?? '',
    effectivity_date: props.store?.effectivity_date ?? '',
    target_opening_date: props.store?.target_opening_date ?? '',
    soft_opening_date: props.store?.soft_opening_date ?? '',
    grand_opening_date: props.store?.grand_opening_date ?? '',
    current_step: 'basic-details',
});

const financialLegalDocumentsForm = useForm({
    bir_2303: props.store?.bir_2303 ?? '',
    cgl_insurance_policy_number: props.store?.cgl_insurance_policy_number ?? '',
    cgl_expiry_date: props.store?.cgl_expiry_date ?? '',
    fire_insurance_policy_number: props.store?.fire_insurance_policy_number ?? '',
    fire_expiry_date: props.store?.fire_expiry_date ?? '',
    current_step: 'specifications',
});

const locationSpecificationForm = useForm({
    area_population: props.store?.area_population ?? '',
    catchment: props.store?.catchment ?? '',
    foot_traffic: props.store?.foot_traffic ?? '',
    manpower: props.store?.manpower ?? '',
    rental: props.store?.rental ?? '',
    square_meter: props.store?.square_meter ?? '',
    sales_per_capita: props.store?.sales_per_capita ?? 'PHP 0.00',
    projected_peso_bread_sales_per_month:
        props.store?.projected_peso_bread_sales_per_month ?? 'PHP 0.00',
    projected_peso_non_bread_sales_per_month:
        props.store?.projected_peso_non_bread_sales_per_month ?? 'PHP 0.00',
    projected_total_cost: props.store?.projected_total_cost ?? 'PHP 0.00',
    current_step: 'specifications',
});

const leaseLegalInformationForm = useForm({
    contract_of_lease_start_date: props.store?.contract_of_lease_start_date ?? '',
    contract_of_lease_end_date: props.store?.contract_of_lease_end_date ?? '',
    escalation: props.store?.escalation ?? '',
    lessor_name: props.store?.lessor_name ?? '',
    lease_payment_date: props.store?.lease_payment_date ?? '',
    notarized_stamp_payment_receipt_number:
        props.store?.notarized_stamp_payment_receipt_number ?? '',
    col_notarized_date: props.store?.col_notarized_date ?? '',
    col_notarized_by: props.store?.col_notarized_by ?? '',
    current_step: 'specifications',
});

const storeMaintenanceUpdateForm = useForm({
    maintenance_last_repaint_at: props.store?.maintenance_last_repaint_at ?? '',
    maintenance_last_renovation_at: props.store?.maintenance_last_renovation_at ?? '',
    maintenance_temporary_closed_at: props.store?.maintenance_temporary_closed_at ?? '',
    maintenance_temporary_closed_reason: props.store?.maintenance_temporary_closed_reason ?? '',
    maintenance_reopening_date: props.store?.maintenance_reopening_date ?? '',
    maintenance_permanent_closure_date: props.store?.maintenance_permanent_closure_date ?? '',
    maintenance_permanent_closure_reason: props.store?.maintenance_permanent_closure_reason ?? '',
    maintenance_upgrade_date: props.store?.maintenance_upgrade_date ?? '',
    maintenance_downgrade_date: props.store?.maintenance_downgrade_date ?? '',
    maintenance_remarks: props.store?.maintenance_remarks ?? '',
    maintenance_store_acquired_at: props.store?.maintenance_store_acquired_at ?? '',
    maintenance_store_transferred_at: props.store?.maintenance_store_transferred_at ?? '',
    maintenance_old_franchisee_code: props.store?.maintenance_old_franchisee_code ?? '',
    maintenance_old_branch_code: props.store?.maintenance_old_branch_code ?? '',
    current_step: 'specifications',
});

const storeAddressCardForm = useForm({
    store_province: props.store?.store_province ?? '',
    store_city: props.store?.store_city ?? '',
    store_barangay: props.store?.store_barangay ?? '',
    store_street: props.store?.store_street ?? '',
    store_postal_code: props.store?.store_postal_code ?? '',
    store_phone_number: props.store?.store_phone_number ?? '',
    store_mobile_number: props.store?.store_mobile_number ?? '',
    store_email: props.store?.store_email ?? '',
    current_step: 'contact-info',
});

const equipmentTechnologyForm = useForm({
    with_cctv: Number(props.store?.with_cctv ?? 0), // Ensure it's a number
    cctv_installed_at: props.store?.cctv_installed_at ?? '',
    with_internet: Number(props.store?.with_internet ?? 0),
    internet_installed_at: props.store?.internet_installed_at ?? '',
    with_pos: Number(props.store?.with_pos ?? 0),
    pos_name: props.store?.pos_name ?? '',
    pos_installed_at: props.store?.pos_installed_at ?? '',
    current_step: 'contact-info',
});

const othersDetailsCardForm = useForm({
    warehouse: props.store?.warehouse ?? '',
    custom_warehouse_name: props.store?.custom_warehouse_name ?? '',
    warehouse_remarks: props.store?.warehouse_remarks ?? '',
    current_step: 'contact-info',
});

function handleNav(nav) {
    if (activeForm.value.isDirty) {
        leaveModalOpen.value = true;
        nextNav.value = nav;
    } else {
        activeNav.value = nav.title;
        activeForm.value = nav.form;
    }
}

function handleExit() {
    leaveModalOpen.value = false;
    activeForm.value.reset();
    if (!redirectRoute.value) {
        activeNav.value = nextNav.value.title;
        activeForm.value = nextNav.value.form;
    } else {
        location.href = redirectRoute.value;
    }
}

function handleSaveExit() {
    saving.value = true;
    activeForm.value.post(route('stores.update', props.store.id), {
        onSuccess: () => {
            leaveModalOpen.value = false;
            saving.value = false;
            if (!redirectRoute.value) {
                activeNav.value = nextNav.value.title;
                activeForm.value = nextNav.value.form;
            } else {
                location.href = redirectRoute.value;
            }
        },
        onError: () => {
            leaveModalOpen.value = false;
        },
    });
}

function handleBack() {
    router.visit(route('stores.show', props.store.id));
}

function submit(form) {
    saving.value = true;
    let payload = { ...form };
    if (form === othersDetailsCardForm && form.warehouse !== 'Others') {
        delete payload.custom_warehouse_name;
    }
    form.post(route('stores.update', props.store.id), {
        data: payload,
        onSuccess: () => {
            saving.value = false;
            if (form === othersDetailsCardForm) {
                form.custom_warehouse_name = props.store?.custom_warehouse_name;
            }
        },
        onError: () => {
            saving.value = false;
        },
    });
}

onMounted(() => {
    activeForm.value = generalInformationForm;
    navigations.value = [
        {
            title: 'General Information',
            form: generalInformationForm,
        },
        {
            title: 'Management & Operations',
            form: managementOperationForm,
        },
        {
            title: 'Opening & Franchise Timeline',
            form: openingFranchiseTimelineForm,
        },
        {
            title: 'Financial & Legal Documents',
            form: financialLegalDocumentsForm,
        },
        {
            title: 'Location & Specifications',
            form: locationSpecificationForm,
        },
        {
            title: 'Lease & Legal Information',
            form: leaseLegalInformationForm,
        },
        {
            title: 'Store Maintenance & Updates',
            form: storeMaintenanceUpdateForm,
        },
        {
            title: 'Store Address & Communication Details',
            form: storeAddressCardForm,
        },
        {
            title: 'Equipment & Technology',
            form: equipmentTechnologyForm,
        },
        {
            title: 'Other Details',
            form: othersDetailsCardForm,
        },
    ];
    // Clear initial dirty flags by setting current values as defaults
    navigations.value.forEach((nav) => {
        nav.form.defaults(nav.form.data());
    });
});

onUnmounted(
    router.on('before', (event) => {
        if (activeForm.value.isDirty && !saving.value) {
            redirectRoute.value = event.detail.visit.url.href;
            event.preventDefault();
            leaveModalOpen.value = true;
        }
    })
);
</script>

<template>
    <Head title="Stores" />

    <Layout :has-left-nav="true">
        <template v-slot:left-nav>
            <div class="flex items-center gap-4">
                <div class="bg-rose-50 p-2 rounded-md cursor-pointer" @click="handleBack">
                    <ArrowLeftIcon class="w-4 h-4 text-gray-900" />
                </div>
                <h5 class="text-lg font-medium text-gray-900">Edit Store Info</h5>
            </div>
            <div class="space-y-1 mt-5">
                <div
                    v-for="(nav, index) in navigations"
                    class="cursor-pointer"
                    @click="handleNav(nav)"
                >
                    <div
                        :class="
                            nav.title === activeNav
                                ? 'rounded-md bg-gray-200 text-gray-900'
                                : 'text-gray-600'
                        "
                        class="p-2 hover:bg-gray-200 hover:rounded-md hover:text-gray-900"
                    >
                        <p class="font-medium">
                            {{ nav.title }}
                        </p>
                    </div>
                </div>
            </div>
        </template>

        <!-- General Information -->
        <div class="py-6">
            <form
                v-if="activeNav === 'General Information'"
                class="space-y-6"
                @submit.prevent="submit(generalInformationForm)"
            >
                <h4 class="font-sans font-semibold">General Information</h4>
                <GeneralInformationCard
                    :form="generalInformationForm"
                    :store="store"
                    :with-header="false"
                    page="edit"
                />
                <PrimaryButton
                    :disabled="generalInformationForm.processing"
                    class="!font-medium ! !text-sm"
                    type="submit"
                >
                    Save Changes
                </PrimaryButton>
            </form>

            <!-- Management & Operations -->
            <form
                v-if="activeNav === 'Management & Operations'"
                class="space-y-6"
                @submit.prevent="submit(managementOperationForm)"
            >
                <h4 class="font-sans font-semibold">Management & Operations</h4>
                <ManagementOperationCard
                    :form="managementOperationForm"
                    :with-header="false"
                    page="edit"
                />
                <PrimaryButton
                    :disabled="managementOperationForm.processing"
                    class="!font-medium ! !text-sm"
                    type="submit"
                >
                    Save Changes
                </PrimaryButton>
            </form>

            <!-- Opening & Franchise Timeline -->
            <form
                v-if="activeNav === 'Opening & Franchise Timeline'"
                class="space-y-6"
                @submit.prevent="submit(openingFranchiseTimelineForm)"
            >
                <h4 class="font-sans font-semibold">Opening & Franchise Timeline</h4>
                <OpeningFranchiseTimeline
                    :form="openingFranchiseTimelineForm"
                    :with-header="false"
                    page="edit"
                />
                <PrimaryButton class="!font-medium ! !text-sm" type="submit">
                    Save Changes
                </PrimaryButton>
            </form>

            <!-- Financial & Legal Documents -->
            <form
                v-if="activeNav === 'Financial & Legal Documents'"
                class="space-y-6"
                @submit.prevent="submit(financialLegalDocumentsForm)"
            >
                <h4 class="font-sans font-semibold">Financial & Legal Documents</h4>
                <financial-legal-documents
                    :form="financialLegalDocumentsForm"
                    :with-header="false"
                    page="edit"
                />
                <PrimaryButton
                    :disabled="financialLegalDocumentsForm.processing"
                    class="!font-medium ! !text-sm"
                    type="submit"
                >
                    Save Changes
                </PrimaryButton>
            </form>

            <!-- Location & Specifications -->
            <form
                v-if="activeNav === 'Location & Specifications'"
                class="space-y-6"
                @submit.prevent="submit(locationSpecificationForm)"
            >
                <h4 class="font-sans font-semibold">Location & Specifications</h4>
                <location-specification
                    :form="locationSpecificationForm"
                    :with-header="false"
                    page="edit"
                />
                <PrimaryButton
                    :disabled="locationSpecificationForm.processing"
                    class="!font-medium ! !text-sm"
                    type="submit"
                >
                    Save Changes
                </PrimaryButton>
            </form>

            <!-- Lease & Legal Information -->
            <form
                v-if="activeNav === 'Lease & Legal Information'"
                class="space-y-6"
                @submit.prevent="submit(leaseLegalInformationForm)"
            >
                <h4 class="font-sans font-semibold">Lease & Legal Information</h4>
                <lease-legal-information
                    :form="leaseLegalInformationForm"
                    :with-header="false"
                    page="edit"
                />
                <PrimaryButton
                    :disabled="leaseLegalInformationForm.processing"
                    class="!font-medium ! !text-sm"
                    type="submit"
                >
                    Save Changes
                </PrimaryButton>
            </form>

            <!-- Store Maintenance & Updates -->
            <form
                v-if="activeNav === 'Store Maintenance & Updates'"
                class="space-y-6"
                @submit.prevent="submit(storeMaintenanceUpdateForm)"
            >
                <h4 class="font-sans font-semibold">Store Maintenance & Updates</h4>
                <store-maintenance-update
                    :form="storeMaintenanceUpdateForm"
                    :with-header="false"
                    page="edit"
                />
                <PrimaryButton
                    :disabled="storeMaintenanceUpdateForm.processing"
                    class="!font-medium ! !text-sm"
                    type="submit"
                >
                    Save Changes
                </PrimaryButton>
            </form>

            <!-- Store Address & Communication Details -->
            <form
                v-if="activeNav === 'Store Address & Communication Details'"
                class="space-y-6"
                @submit.prevent="submit(storeAddressCardForm)"
            >
                <h4 class="font-sans font-semibold">Store Address & Communication Details</h4>
                <store-address-card :form="storeAddressCardForm" :with-header="false" page="edit" />
                <PrimaryButton
                    :disabled="storeAddressCardForm.processing"
                    class="!font-medium ! !text-sm"
                    type="submit"
                >
                    Save Changes
                </PrimaryButton>
            </form>

            <!-- Equipment & Technology -->
            <form
                v-if="activeNav === 'Equipment & Technology'"
                @submit.prevent="submit(equipmentTechnologyForm)"
                class="space-y-6"
            >
                <h4 class="font-sans font-semibold">Equipment & Technology</h4>
                <equipment-technology
                    :form="equipmentTechnologyForm"
                    :with-header="false"
                    page="edit"
                />
                <PrimaryButton
                    :disabled="equipmentTechnologyForm.processing"
                    class="!font-medium ! !text-sm"
                    type="submit"
                >
                    Save Changes
                </PrimaryButton>
            </form>

            <!-- Other Details -->
            <form
                v-if="activeNav === 'Other Details'"
                @submit.prevent="submit(othersDetailsCardForm)"
                class="space-y-6"
            >
                <h4 class="font-sans font-semibold">Other Details</h4>
                <others-details-card
                    :form="othersDetailsCardForm"
                    :with-header="false"
                    page="edit"
                />
                <PrimaryButton
                    :disabled="othersDetailsCardForm.processing"
                    class="!font-medium ! !text-sm"
                    type="submit"
                >
                    Save Changes
                </PrimaryButton>
            </form>
        </div>

        <LeaveConfirmationModal
            :is-processing="activeForm?.processing"
            :open="leaveModalOpen"
            @close="leaveModalOpen = false"
            @exit="handleExit"
            @save="handleSaveExit"
        />
    </Layout>

    <Teleport to="#portal-breadcrumb">
        <Breadcrumbs
            :levels="3"
            :level1="{ name: 'Stores', route: 'stores' }"
            :level2="{
                name: store.jbs_name,
                route: 'stores.show',
                route_id: store.id,
            }"
            :level3="{
                name: 'Edit All Details',
                route: 'stores.edit',
                route_id: store.id,
            }"
        />
    </Teleport>
</template>
