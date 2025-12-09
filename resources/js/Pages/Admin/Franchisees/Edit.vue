<script setup>
import { ArrowLeftIcon } from '@heroicons/vue/24/outline/index.js';
import { Head, router, useForm } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import AdditionalNotesCard from '@/Components/Franchisees/FranchiseInfo/AdditionalNotesCard.vue';
import ApplicationDetailsCard from '@/Components/Franchisees/FranchiseInfo/ApplicationDetailsCard.vue';
import Breadcrumbs from '@/Components/Shared/Breadcrumbs.vue';
import BackgroundInformationCard from '@/Components/Franchisees/FranchiseInfo/BackgroundInformationCard.vue';
import ContactInformation from '@/Components/Franchisees/BasicDetails/ContactInformation.vue';
import FranchiseManagementContactsCard from '@/Components/Franchisees/FranchiseInfo/FranchiseManagementContactsCard.vue';
import Layout from '@/Layouts/Admin/Layout.vue';
import PersonalInformationCard from '@/Components/Franchisees/BasicDetails/PersonalInformationCard.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import ProfileOverviewCard from '@/Components/Franchisees/BasicDetails/ProfileOverviewCard.vue';
import TrainingManualsCard from '@/Components/Franchisees/FranchiseInfo/TrainingManualsCard.vue';
import LeaveConfirmationModal from '@/Components/Modal/LeaveConfirmationModal.vue';

const props = defineProps({
    franchisee: Object,
});

const navigations = ref([]);

const activeNav = ref('Profile');
const nextNav = ref(null);
const activeForm = ref(null);
const redirectRoute = ref(null);
const leaveModalOpen = ref(false);
const saving = ref(false);

const profileForm = useForm({
    profile_photo: null,
    franchisee_profile_photo: null,
    corporation_name: props.franchisee?.corporation_name ?? '',
    first_name: props.franchisee?.first_name ?? '',
    middle_name: props.franchisee?.middle_name ?? '',
    last_name: props.franchisee?.last_name ?? '',
    name_suffix: props.franchisee?.name_suffix ?? '',
    status: props.franchisee?.status ?? '',
    tin: props.franchisee?.tin ?? '',
    current_step: 'basic-details',
});

const personalForm = useForm({
    birthdate: props.franchisee?.birthdate ?? '',
    age: props.franchisee?.age ?? '',
    gender: props.franchisee?.gender ?? '',
    nationality: props.franchisee?.nationality ?? '',
    religion: props.franchisee?.religion ?? '',
    marital_status: props.franchisee?.marital_status ?? '',
    spouse_name: props.franchisee?.spouse_name ?? '',
    spouse_birthdate: props.franchisee?.spouse_birthdate ?? '',
    wedding_date: props.franchisee?.wedding_date ?? '',
    number_of_children: props.franchisee?.number_of_children ?? '',
    current_step: 'basic-details',
});

const contactForm = useForm({
    residential_address_province: props.franchisee?.residential_address_province ?? '',
    residential_address_city: props.franchisee?.residential_address_city ?? '',
    residential_address_barangay: props.franchisee?.residential_address_barangay ?? '',
    residential_address_street: props.franchisee?.residential_address_street ?? '',
    residential_address_postal: props.franchisee?.residential_address_postal ?? '',
    contact_number: props.franchisee?.contact_number ?? '',
    contact_number_2: props.franchisee?.contact_number_2 ?? '',
    contact_number_3: props.franchisee?.contact_number_3 ?? '',
    email: props.franchisee?.email ?? '',
    email_2: props.franchisee?.email_2 ?? '',
    email_3: props.franchisee?.email_3 ?? '',
    current_step: 'basic-details',
});

const franchiseContactForm = useForm({
    fm_point_person: props.franchisee?.fm_point_person ?? '',
    fm_email_address: props.franchisee?.fm_email_address ?? '',
    fm_email_address_2: props.franchisee?.fm_email_address_2 ?? '',
    fm_email_address_3: props.franchisee?.fm_email_address_3 ?? '',
    fm_district_manager: props.franchisee?.fm_district_manager ?? '',
    fm_contact_number: props.franchisee?.fm_contact_number ?? '',
    fm_contact_number_2: props.franchisee?.fm_contact_number_2 ?? '',
    fm_contact_number_3: props.franchisee?.fm_contact_number_3 ?? '',
    fm_region: props.franchisee?.fm_region ?? '',
    current_step: 'franchisee-info',
});

const trainingForm = useForm({
    date_start_bakery_management_seminar:
        props.franchisee?.date_start_bakery_management_seminar ?? '',
    date_end_bakery_management_seminar: props.franchisee?.date_end_bakery_management_seminar ?? '',
    date_start_bread_baking_course: props.franchisee?.date_start_bread_baking_course ?? '',
    date_end_bread_baking_course: props.franchisee?.date_end_bread_baking_course ?? '',
    operations_manual_number: props.franchisee?.operations_manual_number ?? '',
    operations_manual_release: props.franchisee?.operations_manual_release ?? '',
    current_step: 'franchisee-info',
});

const applicationForm = useForm({
    date_applied: props.franchisee?.date_applied ?? '',
    date_approved: props.franchisee?.date_approved ?? '',
    current_step: 'franchisee-info',
});

const backgroundForm = useForm({
    background: props.franchisee?.background ?? '',
    custom_background: props.franchisee?.custom_background ?? '',
    education: props.franchisee?.education ?? '',
    course: props.franchisee?.course ?? '',
    occupation: props.franchisee?.occupation ?? '',
    source_of_information: props.franchisee?.source_of_information ?? '',
    custom_source_of_information: props.franchisee?.custom_source_of_information ?? '',
    legacy: props.franchisee?.legacy ?? '',
    generation: props.franchisee?.generation ?? '',
    custom_generation: props.franchisee?.custom_generation ?? '',
    current_step: 'franchisee-info',
});

const additionalForm = useForm({
    date_separated: props.franchisee?.date_separated ?? '',
    remarks: props.franchisee?.remarks ?? '',
    current_step: 'franchisee-info',
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
    activeForm.value.post(route('franchisees.update', props.franchisee.id), {
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
    router.visit(route('franchisees.show', props.franchisee.id));
}

function submit(form) {
    saving.value = true;
    let payload = { ...form };

    if (form === backgroundForm) {
        if (form.background !== 'Others') {
            delete payload.custom_background;
        }
        if (form.source_of_information !== 'Others') {
            delete payload.custom_source_of_information;
        }
        if (form.generation !== 'Others') {
            delete payload.custom_generation;
        }
    }

    form.post(route('franchisees.update', props.franchisee.id), {
        data: payload,
        onSuccess: () => {
            saving.value = false;

            if (form === backgroundForm) {
                console.log(props.franchisee?.custom_background);
                if (form.background !== 'Others') {
                    form.custom_background = props.franchisee?.custom_background;
                }
                if (form.source_of_information !== 'Others') {
                    form.custom_source_of_information =
                        props.franchisee?.custom_source_of_information;
                }
                if (form.generation !== 'Others') {
                    form.custom_generation = props.franchisee?.custom_generation;
                }
            }
        },
    });
}

onMounted(() => {
    activeForm.value = profileForm;
    navigations.value = [
        {
            title: 'Profile',
            form: profileForm,
        },
        {
            title: 'Personal Information',
            form: personalForm,
        },
        {
            title: 'Contact Information',
            form: contactForm,
        },
        {
            title: 'Franchise Management Contacts',
            form: franchiseContactForm,
        },
        {
            title: 'Training and Manuals',
            form: trainingForm,
        },
        {
            title: 'Application Details',
            form: applicationForm,
        },
        {
            title: 'Background Information',
            form: backgroundForm,
        },
        {
            title: 'Additional Notes',
            form: additionalForm,
        },
    ];
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
    <Head title="Franchisees" />

    <Layout :has-left-nav="true">
        <template v-slot:left-nav>
            <div class="flex items-center gap-4">
                <div class="bg-rose-50 p-2 rounded-md cursor-pointer" @click="handleBack">
                    <ArrowLeftIcon class="w-4 h-4 text-gray-900" />
                </div>
                <h5 class="text-lg font-medium text-gray-900">Edit Franchisee Info</h5>
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

        <!-- Profile -->
        <div class="py-6">
            <form
                v-if="activeNav === 'Profile'"
                class="space-y-6"
                @submit.prevent="submit(profileForm)"
            >
                <h4 class="font-semibold font-sans">Profile</h4>
                <ProfileOverviewCard
                    :form="profileForm"
                    :franchisee="franchisee"
                    :with-header="false"
                    page="edit"
                />
                <PrimaryButton
                    :disabled="profileForm.processing"
                    class="!font-medium ! !text-sm"
                    type="submit"
                >
                    Save Changes
                </PrimaryButton>
            </form>

            <!-- Personal Info -->
            <form
                v-if="activeNav === 'Personal Information'"
                class="space-y-6"
                @submit.prevent="submit(personalForm)"
            >
                <h4 class="font-semibold font-sans">Personal Information</h4>
                <PersonalInformationCard :form="personalForm" :with-header="false" page="edit" />
                <PrimaryButton
                    :disabled="personalForm.processing"
                    class="!font-medium ! !text-sm"
                    type="submit"
                >
                    Save Changes
                </PrimaryButton>
            </form>

            <!-- Contact Info -->
            <form
                v-if="activeNav === 'Contact Information'"
                class="space-y-6"
                @submit.prevent="submit(contactForm)"
            >
                <h4 class="font-semibold font-sans">Contact Information</h4>
                <ContactInformation :form="contactForm" page="edit" />
                <PrimaryButton
                    :disabled="contactForm.processing"
                    class="!font-medium ! !text-sm"
                    type="submit"
                >
                    Save Changes
                </PrimaryButton>
            </form>

            <!-- Franchise Management Contacts -->
            <form
                v-if="activeNav === 'Franchise Management Contacts'"
                class="space-y-6"
                @submit.prevent="submit(franchiseContactForm)"
            >
                <h4 class="font-semibold font-sans">Franchise Management Contacts</h4>
                <FranchiseManagementContactsCard
                    :form="franchiseContactForm"
                    :with-header="false"
                    page="edit"
                />
                <PrimaryButton
                    :disabled="franchiseContactForm.processing"
                    class="!font-medium ! !text-sm"
                    type="submit"
                >
                    Save Changes
                </PrimaryButton>
            </form>

            <!-- Training and Manuals -->
            <form
                v-if="activeNav === 'Training and Manuals'"
                class="space-y-6"
                @submit.prevent="submit(trainingForm)"
            >
                <h4 class="font-semibold font-sans">Training and Manuals</h4>
                <TrainingManualsCard :form="trainingForm" :with-header="false" page="edit" />
                <PrimaryButton
                    :disabled="trainingForm.processing"
                    class="!font-medium ! !text-sm"
                    type="submit"
                >
                    Save Changes
                </PrimaryButton>
            </form>

            <!-- Application Details -->
            <form
                v-if="activeNav === 'Application Details'"
                class="space-y-6"
                @submit.prevent="submit(applicationForm)"
            >
                <h4 class="font-semibold font-sans">Application Details</h4>
                <ApplicationDetailsCard :form="applicationForm" :with-header="false" page="edit" />
                <PrimaryButton
                    :disabled="applicationForm.processing"
                    class="!font-medium ! !text-sm"
                    type="submit"
                >
                    Save Changes
                </PrimaryButton>
            </form>

            <!-- Background Information -->
            <form
                v-if="activeNav === 'Background Information'"
                class="space-y-6"
                @submit.prevent="submit(backgroundForm)"
            >
                <h4 class="font-semibold font-sans">Background Information</h4>
                <BackgroundInformationCard
                    :form="backgroundForm"
                    :with-header="false"
                    page="edit"
                />
                <PrimaryButton
                    :disabled="backgroundForm.processing"
                    class="!font-medium ! !text-sm"
                    type="submit"
                >
                    Save Changes
                </PrimaryButton>
            </form>

            <!-- Additional Notes -->
            <form
                v-if="activeNav === 'Additional Notes'"
                @submit.prevent="submit(additionalForm)"
                class="space-y-6"
            >
                <h4 class="font-semibold font-sans">Additional Notes</h4>
                <AdditionalNotesCard :form="additionalForm" :with-header="false" page="edit" />
                <PrimaryButton
                    :disabled="additionalForm.processing"
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
            :level1="{ name: 'Franchisees', route: 'franchisees' }"
            :level2="{
                name: franchisee.first_name + ' ' + franchisee.last_name,
                route: 'franchisees.show',
                route_id: franchisee.id,
            }"
            :level3="{
                name: 'Edit All Details',
                route: 'franchisees.edit',
                route_id: franchisee.id,
            }"
        />
    </Teleport>
</template>
