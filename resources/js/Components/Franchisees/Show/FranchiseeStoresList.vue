<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { PlusIcon } from '@heroicons/vue/24/outline';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import StatusBadge from '@/Components/Common/Badge/StatusBadge.vue';
import LocationMarker from '@/Components/Icon/LocationMarker.vue';
import StorePagination from '@/Components/Shared/StorePagination.vue';
import StorePlaceholder from '@/Components/Stores/StorePlaceholder.vue';
import { StarIcon } from '@heroicons/vue/24/solid';
import RatingBar from '@/Components/Common/RatingBar/RatingBar.vue';

const props = defineProps({
    franchisee: Object,
});

const currentPage = ref(1);
const itemsPerPage = 5;

const paginatedStores = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    return props.franchisee.stores.slice(start, start + itemsPerPage);
});

const date = new Date().toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
});

function goToStore(storeId) {
    router.visit(route('stores.show', storeId));
}

function goToStoreRatings(storeId) {
    router.visit(route('stores.show', storeId) + '?tab=Rating');
}

function handleAddStore() {
    router.get(route('stores.create'), { franchisee_id: props.franchisee.id });
}

const canUpdateFranchisees = computed(() => {
    return usePage().props.auth.permissions.includes('update-franchisees');
});

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

const ratingsPerStore = computed(() => {
    return (
        props.franchisee.stores?.map((store) => {
            const ratings = store?.ratings?.[0];
            try {
                return {
                    storeId: store.id,
                    ratingsPerType: ratings?.ratings_per_type
                        ? JSON.parse(ratings.ratings_per_type)
                        : {},
                };
            } catch (e) {
                console.error(`Failed to parse ratings_per_type for store ${store.id}:`, e);
                return {
                    storeId: store.id,
                    ratingsPerType: {},
                };
            }
        }) || []
    );
});

function getRatingsPerType(storeId, type) {
    const storeRatings = ratingsPerStore.value.find((s) => s.storeId === storeId);
    return storeRatings?.ratingsPerType?.[type] ?? 0;
}
</script>

<template>
    <div class="flex justify-between items-center gap-4">
        <h1 class="text-xl font-sans font-semibold text-gray-900">Franchisee Stores</h1>
        <PrimaryButton
            v-if="franchisee.stores && franchisee.stores.length > 0 && canUpdateFranchisees"
            @click="handleAddStore"
        >
            <PlusIcon class="h-5 w-5" />
            Add Store
        </PrimaryButton>
    </div>

    <div v-if="franchisee.stores && franchisee.stores.length > 0">
        <div
            v-for="store in paginatedStores"
            :key="store.id"
            class="mt-4 bg-white p-4 rounded-2xl border border-gray-200"
        >
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <!-- Store Image -->
                <div class="rounded-xl h-full col-span-1 overflow-hidden max-h-[248px] relative">
                    <img
                        v-if="store.image"
                        :src="store.image"
                        alt=""
                        class="rounded-xl object-cover w-full h-full border-2 border-white"
                    />
                    <StorePlaceholder v-else />
                </div>

                <!-- Store Details -->
                <div class="col-span-1 md:col-span-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-lg md:text-xl font-semibold">{{ store.jbs_name }}</p>
                        <StatusBadge
                            :category="'storeStatus'"
                            :type="store.store_status"
                            class="!rounded-full [&_svg]:hidden"
                        >
                            {{ store.store_status }}
                        </StatusBadge>
                    </div>
                    <div
                        class="flex flex-wrap items-center gap-2 mt-2 text-sm md:text-base text-gray-500"
                    >
                        <LocationMarker />
                        <p>{{ store.region }}</p>
                        <span class="hidden md:inline">•</span>
                        <p>{{ store.area || '—' }}</p>
                        <span class="hidden md:inline">•</span>
                        <p class="w-full md:w-auto">{{ store.district || '—' }}</p>
                    </div>

                    <div class="mt-4 space-y-2">
                        <div class="grid grid-cols-3 gap-3">
                            <p class="text-sm text-gray-500">Branch Code:</p>
                            <p class="text-sm text-gray-900">{{ store.store_code || '—' }}</p>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <p class="text-sm text-gray-500">Cluster Code:</p>
                            <p class="text-sm text-gray-900">{{ store.cluster_code || '—' }}</p>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <p class="text-sm text-gray-500">Store Group:</p>
                            <p class="text-sm text-gray-900">{{ store.store_group_label }}</p>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <p class="text-sm text-gray-500">Store Type:</p>
                            <p class="text-sm text-gray-900">{{ store.store_type }}</p>
                        </div>
                    </div>

                    <div class="mt-8">
                        <PrimaryButton
                            class="!bg-rose-50 !text-primary !font-medium"
                            @click="goToStore(store.id)"
                        >
                            Go to Store
                        </PrimaryButton>
                    </div>
                </div>

                <!-- Ratings -->
                <div
                    v-if="store.recent_rating?.overall_rating"
                    class="bg-gray-50 p-4 rounded-2xl border border-gray-200 col-span-1 w-full"
                >
                    <p class="text-sm font-semibold text-gray-800">Overall Rating</p>
                    <p class="text-xs text-gray-500">as of {{ date }}</p>

                    <!-- Overall Rating Section -->
                    <div
                        class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4 pb-4 border-b border-gray-900"
                    >
                        <p class="text-3xl font-bold text-gray-700 sm:col-span-1">
                            {{ store.recent_rating?.overall_rating ?? '0.00' }}
                        </p>
                        <div
                            class="flex flex-col sm:flex-row items-start sm:items-center sm:col-span-2"
                        >
                            <div>
                                <div class="flex items-center gap-1">
                                    <template v-for="i in 5" :key="i">
                                        <div class="relative w-5 h-5">
                                            <StarIcon class="absolute text-gray-200 w-5 h-5" />
                                            <StarIcon
                                                :style="{
                                                    clipPath: getStarClipPath(
                                                        i,
                                                        store.recent_rating?.overall_rating
                                                    ),
                                                }"
                                                class="absolute text-yellow-400 w-5 h-5"
                                            />
                                        </div>
                                    </template>
                                </div>
                                <p class="mt-1 text-sm text-gray-500">
                                    {{
                                        ((store.recent_rating.overall_rating / 5) * 100).toFixed(2)
                                    }}% scored out of 100%
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Ratings per type -->
                    <div class="mt-4 space-y-4">
                        <RatingBar
                            :rating="getRatingsPerType(store.id, 'Authorized Products')"
                            label="Authorized Products"
                        />
                        <RatingBar
                            :rating="
                                getRatingsPerType(
                                    store.id,
                                    'Cleanliness, Sanitation and Maintenance'
                                )
                            "
                            label="Cleanliness"
                        />
                    </div>

                    <!-- Show Summary -->
                    <div class="mt-4">
                        <p
                            class="text-primary hover:underline font-medium cursor-pointer"
                            @click="goToStoreRatings(store.id)"
                        >
                            Show Summary
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Empty State -->
    <div
        v-else
        class="flex flex-col justify-center items-center gap-6 bg-white py-6 border border-gray-200 rounded-2xl"
    >
        <svg
            fill="none"
            height="82"
            viewBox="0 0 82 82"
            width="82"
            xmlns="http://www.w3.org/2000/svg"
        >
            <ellipse cx="41" cy="62" fill="#F3F4F6" rx="41" ry="15" />
            <path
                clip-rule="evenodd"
                d="M23.7802 23.78C23.7802 20.6099 26.35 18.04 29.5202 18.04H52.4802C55.6503 18.04 58.2202 20.6099 58.2202 23.78V58.22C59.8052 58.22 61.0902 59.505 61.0902 61.09C61.0902 62.6751 59.8052 63.96 58.2202 63.96H47.8335C47.2297 63.96 46.7402 63.4705 46.7402 62.8667V55.35C46.7402 53.765 45.4552 52.48 43.8702 52.48H38.1302C36.5451 52.48 35.2602 53.765 35.2602 55.35V62.8667C35.2602 63.4705 34.7707 63.96 34.1668 63.96H23.7802C22.1951 63.96 20.9102 62.6751 20.9102 61.09C20.9102 59.505 22.1951 58.22 23.7802 58.22V23.78ZM32.3902 26.65H38.1302V32.39H32.3902V26.65ZM38.1302 38.13H32.3902V43.87H38.1302V38.13ZM43.8702 26.65H49.6102V32.39H43.8702V26.65ZM49.6102 38.13H43.8702V43.87H49.6102V38.13Z"
                fill="#9CA3AF"
                fill-rule="evenodd"
            />
        </svg>
        <div class="text-center">
            <h5 class="font-semibold text-base">No Stores yet</h5>
            <p class="text-gray-400 text-sm">Add franchise stores and photos</p>
        </div>
        <PrimaryButton class="!font-medium" @click="handleAddStore">
            <PlusIcon class="size-5" />
            Add Store
        </PrimaryButton>
    </div>

    <StorePagination
        v-if="franchisee.stores && franchisee.stores.length > itemsPerPage"
        :currentPage="currentPage"
        :itemsPerPage="itemsPerPage"
        :totalItems="props.franchisee.stores.length"
        class="mt-4"
        @update:currentPage="currentPage = $event"
    />
</template>
