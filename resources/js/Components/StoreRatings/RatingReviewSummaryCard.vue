<script setup>
import { computed, ref, toRef } from 'vue';
import RatingBar from '@/Components/Common/RatingBar/RatingBar.vue';
import { StarIcon } from '@heroicons/vue/24/solid';
import StorePlaceholder from '@/Components/Stores/StorePlaceholder.vue';
import VueEasyLightbox from 'vue-easy-lightbox';

const props = defineProps({
    storeRating: Object,
});

// Compute layout type based on available images
const layoutType = computed(() => {
    const photos = props.storeRating.photos || [];
    if (photos.length === 1) return 'single';
    if (photos.length === 2) return 'double';
    return 'triple';
});

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
    if (lightboxImages.value.length > 0) {
        lightboxIndex.value = index;
        showLightbox.value = true;
    }
};

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
    <div>
        <div class="flex justify-between items-center gap-4 mb-4">
            <h1 class="text-xl font-sans font-semibold text-gray-900">Review Summary</h1>
        </div>

        <div class="grid grid-cols-3 gap-10">
            <!-- Rating Summary -->
            <div class="bg-white p-6 rounded-2xl">
                <div class="flex gap-4 items-center">
                    <p class="text-2xl font-bold text-gray-700 p-2.5 rounded-2xl bg-gray-100">
                        {{ storeRating.overall_rating }}
                    </p>
                    <div>
                        <div class="flex items-center gap-2">
                            <template v-for="i in 5" :key="i">
                                <div class="relative w-5 h-5">
                                    <StarIcon class="absolute text-gray-200 w-5 h-5" />
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
                        <p class="text-base text-gray-900 mt-1">
                            {{ ((storeRating.overall_rating / 5) * 100).toFixed(2) }}% scored out of
                            100%
                        </p>
                    </div>
                </div>
                <div class="flex flex-col gap-8 mt-8">
                    <RatingBar
                        v-for="(value, key) in storeRating.ratings_per_type"
                        :key="key"
                        :label="key"
                        :rating="parseFloat(value) || 0"
                    />
                </div>
            </div>

            <!-- Store Photos Layout -->
            <div class="rounded-xl h-full overflow-hidden relative col-span-2">
                <div v-if="storeRating.photos && storeRating.photos.length > 0">
                    <!-- Single Image Layout -->
                    <div v-if="layoutType === 'single'">
                        <img
                            :alt="storeRating.photos[0].description || 'Store Image'"
                            :src="storeRating.photos[0].preview"
                            class="w-full h-[420px] object-cover rounded-lg cursor-pointer"
                            @click="openLightbox(0)"
                        />
                    </div>

                    <!-- Double Image Layout -->
                    <div v-else-if="layoutType === 'double'" class="grid grid-cols-2 gap-2">
                        <img
                            v-for="(photo, index) in storeRating.photos"
                            :key="index"
                            :alt="photo.description || 'Store Image'"
                            :src="photo.preview"
                            class="w-full h-[420px] object-cover rounded-lg cursor-pointer"
                            @click="openLightbox(index)"
                        />
                    </div>

                    <!-- Triple Image Layout -->
                    <div v-else class="grid grid-cols-2 gap-2">
                        <div class="col-span-2">
                            <img
                                :alt="storeRating.photos[0].description"
                                :src="storeRating.photos[0].preview"
                                class="w-full h-[202px] object-cover rounded-lg cursor-pointer"
                                @click="openLightbox(0)"
                            />
                        </div>
                        <img
                            v-if="storeRating.photos[1]"
                            :alt="storeRating.photos[1].description"
                            :src="storeRating.photos[1].preview"
                            class="w-full h-[202px] object-cover rounded-lg cursor-pointer"
                            @click="openLightbox(1)"
                        />
                        <div class="relative">
                            <img
                                :alt="storeRating.photos[2].description"
                                :src="storeRating.photos[2].preview"
                                class="w-full h-[202px] object-cover rounded-lg cursor-pointer"
                                @click="openLightbox(2)"
                            />
                            <button
                                class="absolute bottom-2 right-2 bg-white text-gray-700 text-xs font-medium px-3 py-1 rounded-md shadow-md hover:bg-gray-200"
                                @click="showLightbox = true"
                            >
                                See all photos
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Store Placeholder if No Photos -->
                <div v-else>
                    <div class="absolute inset-0">
                        <StorePlaceholder />
                    </div>
                </div>
            </div>
        </div>
    </div>

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
</template>
