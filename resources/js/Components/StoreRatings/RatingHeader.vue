<script setup>
import { router } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline/index.js';

import Avatar from '@/Components/Common/Avatar/Avatar.vue';
import CalendarIcon from '@/Components/Icon/CalendarIcon.vue';

const props = defineProps({
    storeRating: Object,
});

const handleBack = () => {
    router.visit(route('stores.show', { store: props.storeRating.store_id, tab: 'Rating' }));
};
</script>

<template>
    <div class="w-full relative bg-white py-6 px-8">
        <div class="flex gap-4">
            <div
                class="bg-rose-50 p-2 rounded-md cursor-pointer w-fit h-fit flex items-center justify-center"
                @click="handleBack"
            >
                <ArrowLeftIcon class="w-4 h-4 text-gray-900 stroke-2" />
            </div>
            <div class="flex-1">
                <p class="text-sm text-primary font-medium">ID: {{ storeRating.id }}</p>
                <p class="text-2xl font-bold mt-4">Store Rating</p>

                <div class="flex gap-6 mt-2">
                    <div class="flex gap-2 items-center">
                        <Avatar
                            :image-url="storeRating.reviewed_by?.profile_photo_url"
                            custom-class="size-5"
                            image-class="w-full h-full rounded-full object-cover"
                        />
                        <p class="text-gray-500 font-medium text-sm">
                            Audited by {{ storeRating.reviewed_by?.name }}
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-gray-500">
                        <CalendarIcon />
                        <p>{{ storeRating.rated_at }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
