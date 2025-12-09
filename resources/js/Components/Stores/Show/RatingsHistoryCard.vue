<script setup>
import { StarIcon } from '@heroicons/vue/24/solid';
import ChevronRightIcon from '@/Components/Icon/ChevronRightIcon.vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    auditors: { type: Array, default: () => [] },
    storeRatings: { type: Array, default: () => [] },
});
</script>

<template>
    <div class="bg-white border border-gray-200 shadow-md rounded-lg overflow-hidden">
        <div class="divide-y divide-gray-100">
            <div
                v-for="(storeRating, index) in storeRatings.slice(1)"
                :key="storeRating.id"
                class="cursor-pointer hover:bg-gray-50 transition"
                @click="router.visit(route('storeRatings.show', storeRating.id))"
            >
                <div class="flex items-center gap-6 p-4">
                    <div class="flex items-center justify-center bg-gray-100 w-16 h-20 rounded-2xl">
                        <p class="text-2xl font-bold text-gray-700 text-center">
                            {{ storeRating.overall_rating }}
                        </p>
                    </div>
                    <div class="flex-1">
                        <div class="flex">
                            <StarIcon
                                v-for="rating in [0, 1, 2, 3, 4]"
                                :key="rating"
                                :class="[
                                    (storeRating.overall_rating || 0) > rating
                                        ? 'text-yellow-400'
                                        : 'text-gray-300',
                                    'size-5 shrink-0',
                                ]"
                                aria-hidden="true"
                            />
                        </div>
                        <p class="text-sm text-gray-500 mt-1">
                            Scored {{ ((storeRating.overall_rating / 5) * 100).toFixed(2) }}% out of
                            100%
                        </p>
                        <div class="mt-1">
                            <p class="text-sm font-medium text-gray-500">
                                by {{ storeRating.reviewed_by }}
                            </p>
                            <p class="text-[#A32130] font-medium">ID: {{ storeRating.id }}</p>
                        </div>
                    </div>
                    <div class="flex items-center pl-6">
                        <div class="flex gap-4 items-center">
                            <p class="text-gray-500 text-sm">{{ storeRating.updated_at }}</p>
                            <ChevronRightIcon class="text-gray-400" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped></style>
