<script setup>
import { Link } from '@inertiajs/vue3';
import { StarIcon } from '@heroicons/vue/24/solid';
import RatingBar from '@/Components/Common/RatingBar/RatingBar.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import { computed } from 'vue';

const props = defineProps({
    store_rating: Object,
});

const rating = computed(() => parseFloat(props.store_rating.overall_rating) || 0);

function getStarClipPath(index) {
    const r = rating.value;
    const fullIndex = index;
    const prevIndex = index - 1;

    if (r >= fullIndex) return 'inset(0 0 0 0)'; // fully filled
    if (r <= prevIndex) return 'inset(0 100% 0 0)'; // not filled

    // partial fill
    const fillPercent = (r - prevIndex) * 100;
    const rightClip = 100 - fillPercent;
    return `inset(0 ${rightClip}% 0 0)`;
}
</script>

<template>
    <div
        class="p-6 bg-white rounded-2xl flex items-center gap-6 justify-between mt-4 sm:grid sm:grid-cols-10"
    >
        <div class="flex-1 sm:flex flex-col gap-6 col-span-4">
            <div>
                <p class="text-5xl font-bold text-gray-700">
                    {{ rating }}
                </p>

                <!-- Stars -->
                <div class="flex items-center gap-2 mt-1">
                    <template v-for="i in 5" :key="i">
                        <div class="relative w-5 h-5">
                            <!-- Empty background star -->
                            <StarIcon class="absolute text-gray-200 w-5 h-5" />

                            <!-- Yellow star with dynamic clip -->
                            <StarIcon
                                class="absolute text-yellow-400 w-5 h-5"
                                :style="{ clipPath: getStarClipPath(i) }"
                            />
                        </div>
                    </template>
                </div>

                <p class="text-sm text-gray-900 mt-1">
                    Scored {{ (rating * 20).toFixed(2) }}% out of 100%
                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500">
                    Rated by {{ store_rating.reviewed_by || 'N/A' }}
                </p>
                <p class="text-[#A32130] font-medium">ID: {{ store_rating.id }}</p>
            </div>
            <Link :href="route('storeRatings.show', store_rating.id)">
                <SecondaryButton class="!bg-red-50 !text-[#A32130]">More Details</SecondaryButton>
            </Link>
        </div>

        <div class="col-span-6 p-3 bg-gray-50 h-full rounded-2xl">
            <div class="flex flex-col gap-4 mt-4">
                <RatingBar
                    v-for="(value, key) in store_rating.ratings_per_type"
                    :label="key"
                    :rating="parseFloat(value)"
                />
            </div>
        </div>
    </div>
</template>
