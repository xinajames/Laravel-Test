<script setup>
import { router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import StarOutlineIcon from '@/Components/Icon/StarOutlineIcon.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import AuditorsCard from '@/Components/Stores/Show/AuditorsCard.vue';
import RatingsHistoryCard from '@/Components/Stores/Show/RatingsHistoryCard.vue';
import RatingItem from '@/Components/Stores/RatingItem.vue';

const emits = defineEmits(['updateTab', 'getMoreRatings', 'showRatingModal']);

const props = defineProps({
    store: Object,
    storeRatings: { type: Array, default: () => [] },
    activities: { type: Array, default: () => [] },
    ongoingStoreRating: Object,
});

function handleRating() {
    if (props.ongoingStoreRating) {
        emits('showRatingModal');
    } else {
        router.visit(route('storeRatings.create', { store: props.store.id }));
    }
}
</script>

<template>
    <div class="p-8 space-y-8">
        <!-- Current - Overall Store Rating Section -->
        <div v-if="storeRatings && storeRatings.length > 0">
            <div class="flex justify-between items-center gap-4">
                <div>
                    <h1 class="text-xl font-sans font-semibold">Overall Store Rating</h1>
                    <p class="text-sm text-gray-900">as of {{ storeRatings[0].rated_at }}</p>
                </div>
                <PrimaryButton class="ml-auto flex items-center" @click="handleRating">
                    <StarOutlineIcon class="h-4 w-4" />
                    Rate Store
                </PrimaryButton>
            </div>
            <div class="mt-4">
                <RatingItem :store_rating="storeRatings[0]" />
            </div>
        </div>

        <!-- No existing store rating found -->
        <div v-else class="flex justify-between items-center">
            <div>
                <h5 class="font-semibold text-base">No recent store ratings available.</h5>
                <p class="text-sm text-gray-600">Submit the first review to rate this store.</p>
            </div>
            <PrimaryButton @click="handleRating">
                <StarOutlineIcon class="h-4 w-4" />
                Rate Store
            </PrimaryButton>
        </div>

        <!-- Auditors Section -->
        <div>
            <div class="flex justify-between items-center gap-4">
                <h1 class="text-xl font-sans font-semibold">Auditors</h1>
            </div>
            <AuditorsCard class="mt-6" :store_id="store.id" />
        </div>

        <!-- Rating History Section - All store ratings under store -->
        <div v-if="storeRatings && storeRatings.length > 1">
            <div class="flex justify-between items-center gap-4">
                <h1 class="text-xl font-sans font-semibold">Rating History</h1>
            </div>

            <RatingsHistoryCard :store-ratings="storeRatings" class="mt-6" />

            <SecondaryButton
                class="!bg-red-50 !text-[#A32130] mt-6 !border-transparent"
                @click="$emit('getMoreRatings')"
            >
                Show more
            </SecondaryButton>
        </div>
    </div>
</template>
