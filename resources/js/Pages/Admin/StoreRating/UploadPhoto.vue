<script setup>
import { ArrowLeftIcon } from '@heroicons/vue/24/outline/index.js';
import { router, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import CameraIcon from '@/Components/Icon/CameraIcon.vue';
import LocationMarker from '@/Components/Icon/LocationMarker.vue';
import MainLayout from '@/Layouts/Admin/MainLayout.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import StoreIcon from '@/Components/Icon/StoreIcon.vue';
import UploadStoreRatingPhotoModal from '@/Components/Modal/UploadStoreRatingPhotoModal.vue';
import PhotoCard from '@/Components/StoreRatings/PhotoCard.vue';

const props = defineProps({
    storeRating: Object,
});

const uploadModalOpen = ref(false);

function handleExit() {
    router.visit(route('storeRatings.continue', props.storeRating.id));
}
</script>
<template>
    <Head title="Store Rating - Upload Photos" />
    <MainLayout
        buttonText="Save & Exit"
        subTitle="RATE STORE"
        :title="storeRating.store?.jbs_name"
        @action="handleExit"
        :show-button="true"
        :show-location-details="true"
    ></MainLayout>
    <div class="bg-gray-50 min-h-[calc(100vh-150px)] py-6">
        <div class="max-w-7xl mx-auto w-full">
            <div class="inline-flex gap-4 items-center">
                <div class="p-2 bg-gray-200 rounded-md cursor-pointer" @click="handleExit">
                    <ArrowLeftIcon class="size-4 stroke-2" />
                </div>
                <h5 class="text-lg font-medium text-gray-900">Back to Rating</h5>
            </div>
            <div class="pt-6 flex justify-between items-center">
                <div>
                    <h5 class="text-xl font-semibold text-gray-900">All Photos</h5>
                    <p
                        v-if="storeRating.photos && storeRating.photos.length > 0"
                        class="text-gray-500 font-medium"
                    >
                        You’ve uploaded {{ storeRating.photos.length }} Photos
                    </p>
                </div>
                <primary-button @click="uploadModalOpen = true">
                    <CameraIcon />
                    Upload Photo
                </primary-button>
            </div>
            <div v-if="storeRating.photos.length > 0" class="space-y-6 mt-6">
                <photo-card v-for="photo in storeRating.photos" :photo="photo" />
            </div>
            <div
                v-else
                class="mt-5 flex flex-col items-center justify-center py-10 text-center bg-white rounded-2xl border border-gray-200"
            >
                <p class="text-gray-500">No uploads yet.</p>
            </div>
        </div>
    </div>

    <upload-store-rating-photo-modal
        :open="uploadModalOpen"
        :store-rating-id="storeRating.id"
        @close="uploadModalOpen = false"
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
