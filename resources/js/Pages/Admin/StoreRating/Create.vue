<script setup>
import { computed, reactive, ref, toRef } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { StarIcon } from '@heroicons/vue/24/solid/index.js';
import { STORE_RATING_STEP } from '@/Composables/Enums.js';

import CircleProgressbar from '@/Components/Common/ProgressBar/CircleProgressbar.vue';
import ConfirmationModal from '@/Components/Modal/ConfirmationModal.vue';
import LocationMarker from '@/Components/Icon/LocationMarker.vue';
import MainLayout from '@/Layouts/Admin/MainLayout.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import QuestionCard from '@/Components/StoreRatings/QuestionCard.vue';
import RatingBar from '@/Components/Common/RatingBar/RatingBar.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import StoreIcon from '@/Components/Icon/StoreIcon.vue';
import VueEasyLightbox from 'vue-easy-lightbox';

const props = defineProps({
    storeRating: Object,
    questionnaires: Object,
});

function handleExit() {
    router.visit(route('stores.show', props.storeRating.store?.id));
}

const form = useForm({
    step: props.storeRating?.step,
    is_draft: true,
});

// Manage the current step index
const currentStepIndex = computed(() => {
    return steps.value.findIndex((step) => step.value === props.storeRating.step);
});

// Steps array with dynamic statuses
const steps = ref([
    { name: 'Authorized Products', value: STORE_RATING_STEP.AuthorizedProducts },
    {
        name: 'Cleanliness, Sanitation and Maintenance',
        value: STORE_RATING_STEP.CleanlinessSanitationMaintenance,
    },
    { name: 'Production Quality', value: STORE_RATING_STEP.ProductionQuality },
    {
        name: 'Operational Excellence and Food Safety',
        value: STORE_RATING_STEP.OperationalExcellenceFoodSafety,
    },
    {
        name: 'Customer Experience',
        value: STORE_RATING_STEP.CustomerExperience,
    },
]);

// Lightbox State
const showLightbox = ref(false);
let lightboxIndex = toRef(ref(0));
const lightboxImages = computed(
    () =>
        props.storeRating.photos?.map((photo) => ({
            src: photo.preview,
            title: photo.description || '',
        })) || []
);

const openLightbox = (index) => {
    lightboxIndex = index;
    showLightbox.value = true;
};

// Handle Next Button Click
function handleNext() {
    form.step = steps.value[currentStepIndex.value + 1].value;
    form.post(route('storeRatings.update', props.storeRating.id));
}

// Handle Back Button Click
function handleBack() {
    form.step = steps.value[currentStepIndex.value - 1].value;
    form.post(route('storeRatings.update', props.storeRating.id));
}

function handleSubmit() {
    confirmationModal.open = true;
    confirmationModal.header = 'Submit Your Review?';
    confirmationModal.message =
        "You're about to submit your review. Once submitted, you won’t be able to make changes. Would you like to proceed?";
    confirmationModal.action_label = 'Submit';
    confirmationModal.action = route('storeRatings.update', props.storeRating.id);
    form.step = STORE_RATING_STEP.Finished;
    form.is_draft = false;
    confirmationModal.data = form;
}

function handleSuccess() {
    confirmationModal.open = false;
}

const confirmationModal = reactive({
    open: false,
    header: null,
    data: null,
    message: null,
    icon: 'information',
    action_label: null,
    action: null, // Correctly set the action
});

const filteredQuestionnaires = computed(() => {
    let questionKey = steps.value[currentStepIndex.value].name;
    return props.questionnaires[questionKey];
});

function goToStore(tab) {
    router.visit(route('stores.show', props.storeRating.store_id), {
        data: { tab: tab },
    });
}

function handleDelete() {
    confirmationModal.open = true;
    confirmationModal.header = 'Delete Review';
    confirmationModal.message =
        'Are you sure you want to delete your ongoing store review? This action cannot be undone.';
    confirmationModal.icon = 'delete';
    confirmationModal.action_label = 'Delete Review';
    confirmationModal.action = route('storeRatings.delete', props.storeRating.id);
}

function getStarClipPath(index, ratingValue) {
    const r = parseFloat(ratingValue) || 0;
    const fullIndex = index;
    const prevIndex = index - 1;

    if (r >= fullIndex) return 'inset(0 0 0 0)';
    if (r <= prevIndex) return 'inset(0 100% 0 0)';

    const fillPercent = (r - prevIndex) * 100;
    const rightClip = 100 - fillPercent;
    return `inset(0 ${rightClip}% 0 0)`;
}
</script>

<template>
    <Head title="Store Rating Create" />

    <MainLayout
        :show-button="storeRating.step !== STORE_RATING_STEP.Finished"
        :show-delete="storeRating.step !== STORE_RATING_STEP.Finished"
        :show-location-details="true"
        :title="storeRating.store?.jbs_name"
        buttonText="Save & Exit"
        deleteText="Delete Review"
        subTitle="RATE STORE"
        @action="handleExit"
        @delete="handleDelete"
    >
        <div
            v-if="storeRating.step !== STORE_RATING_STEP.Finished"
            class="bg-gray-50 min-h-[calc(100vh-150px)]"
        >
            <div class="h-[88px] bg-[#FEF8EF] items-center flex">
                <div class="max-w-7xl mx-auto w-full">
                    <CircleProgressbar :current-index="currentStepIndex" :steps="steps" />
                </div>
            </div>
            <div class="py-8 max-w-7xl mx-auto w-full">
                <div class="space-y-4">
                    <QuestionCard
                        :questionnaires="filteredQuestionnaires"
                        :storeRating="storeRating"
                        @openLightBox="openLightbox"
                    />
                </div>

                <div class="mt-4 pt-4 border-t border-gray-200 flex justify-end gap-4">
                    <SecondaryButton
                        :disabled="currentStepIndex === 0"
                        class="!bg-white"
                        @click="handleBack"
                    >
                        Back
                    </SecondaryButton>
                    <div class="flex gap-3">
                        <PrimaryButton
                            v-if="currentStepIndex < steps.length - 1"
                            @click="handleNext"
                        >
                            Next
                        </PrimaryButton>

                        <!-- Show Submit button only if it's the last step -->
                        <PrimaryButton
                            v-if="currentStepIndex === steps.length - 1"
                            @click="handleSubmit"
                        >
                            Submit
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </div>
        <div v-else>
            <div class="bg-gray-50 mx-auto min-h-[calc(100vh-150px)]">
                <div class="max-w-6xl mx-auto pt-8">
                    <div class="space-y-4">
                        <div class="bg-white p-6 rounded-2xl border border-gray-200 flex gap-4">
                            <svg
                                fill="none"
                                height="60"
                                viewBox="0 0 60 60"
                                width="60"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <rect
                                    height="58"
                                    rx="29"
                                    stroke="#A32130"
                                    stroke-width="2"
                                    width="58"
                                    x="1"
                                    y="1"
                                />
                                <path
                                    clip-rule="evenodd"
                                    d="M40.0607 22.9393C40.6464 23.5251 40.6464 24.4749 40.0607 25.0607L28.0607 37.0607C27.4749 37.6464 26.5251 37.6464 25.9393 37.0607L19.9393 31.0607C19.3536 30.4749 19.3536 29.5251 19.9393 28.9393C20.5251 28.3536 21.4749 28.3536 22.0607 28.9393L27 33.8787L37.9393 22.9393C38.5251 22.3536 39.4749 22.3536 40.0607 22.9393Z"
                                    fill="#A32130"
                                    fill-rule="evenodd"
                                />
                            </svg>
                            <div>
                                <h4 class="text-gray-900 font-sans font-semibold">
                                    Review Submitted!
                                </h4>
                                <p class="text-gray-500 font-medium text-sm mt-2">
                                    Your feedback has been submitted successfully. Lorem ipsum dolor
                                    sit amet consectetur. Leo eget faucibus vitae eleifend.
                                </p>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-white border border-gray-200 flex-1 gap-4 p-6">
                            <div class="flex justify-between items-center gap-4">
                                <h1 class="text-xl font-sans font-semibold">
                                    Overall Store Rating
                                </h1>
                            </div>

                            <div class="grid grid-cols-3 mt-4">
                                <div class="flex-1 flex flex-col gap-6">
                                    <div>
                                        <p class="text-5xl font-bold text-gray-700">
                                            {{ storeRating.overall_rating }}
                                        </p>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <template v-for="i in 5" :key="i">
                                                    <div class="relative w-5 h-5">
                                                        <StarIcon
                                                            class="absolute text-gray-200 w-5 h-5"
                                                        />
                                                        <StarIcon
                                                            :style="{
                                                                clipPath: getStarClipPath(
                                                                    i,
                                                                    storeRating.overall_rating?.toString()
                                                                ),
                                                            }"
                                                            class="absolute text-yellow-400 w-5 h-5"
                                                        />
                                                    </div>
                                                </template>
                                            </div>
                                            <p class="text-sm text-gray-500 mt-1">
                                                {{
                                                    (
                                                        (storeRating.overall_rating / 5) *
                                                        100
                                                    ).toFixed(2)
                                                }}% scored out of 100%
                                            </p>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">
                                            Rated by {{ storeRating.reviewed_by?.name }}
                                        </p>
                                        <p class="text-[#A32130] font-medium">
                                            ID: {{ storeRating.reviewerId }}
                                        </p>
                                    </div>
                                    <Link :href="route('storeRatings.show', storeRating.id)">
                                        <SecondaryButton class="!bg-red-50 !text-[#A32130]">
                                            More Details
                                        </SecondaryButton>
                                    </Link>
                                </div>

                                <div class="col-span-2 space-y-3 bg-gray-50 rounded-2xl p-4">
                                    <RatingBar
                                        v-for="(value, key) in storeRating.ratings_per_type"
                                        :label="key"
                                        :rating="parseFloat(value)"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 pt-4">
                            <PrimaryButton class="!font-medium" @click="goToStore('Rating')">
                                View Rating History
                            </PrimaryButton>
                            <SecondaryButton
                                class="!bg-rose-50 !text-primary !ring-transparent !font-medium"
                                @click="goToStore(null)"
                            >
                                Go to Store
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmationModal
            :action="confirmationModal.action"
            :action_label="confirmationModal.action_label"
            :data="confirmationModal.data"
            :header="confirmationModal.header"
            :icon="confirmationModal.icon"
            :message="confirmationModal.message"
            :open="confirmationModal.open"
            @close="confirmationModal.open = false"
            @success="handleSuccess"
        />
    </MainLayout>

    <!-- Lightbox Component -->
    <VueEasyLightbox
        :imgs="lightboxImages"
        :index="lightboxIndex"
        :visible="showLightbox"
        @hide="showLightbox = false"
        @on-prev="lightboxIndex = Math.max(lightboxIndex.value - 1, 0)"
        @on-next="
            lightboxIndex = Math.min(lightboxIndex + 1, (storeRating.photos?.length || 1) - 1)
        "
    />

    <Teleport to="#teleport-location">
        <div class="flex gap-2 text-gray-500 text-sm">
            <span class="flex items-center gap-2">
                <StoreIcon />
                {{ storeRating.store?.store_group_label }}
            </span>
            <span>•</span>
            <span>Branch</span>
            <span>•</span>
            <span class="flex items-center gap-1">
                <LocationMarker class="h-5 w-5" />
                {{ storeRating.store?.region }} · {{ storeRating.store?.area }} ·
                {{ storeRating.store?.district }}
            </span>
        </div>
    </Teleport>
</template>
