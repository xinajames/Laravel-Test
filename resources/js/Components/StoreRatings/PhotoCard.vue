<script setup>
import TrashIcon from '@/Components/Icon/TrashIcon.vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    photo: Object,
});

function deletePhoto() {
    router.post(route('storeRatings.deletePhoto', props.photo.id));
}
</script>
<template>
    <div class="bg-white border border-gray-200 rounded-lg p-4">
        <div class="flex items-start gap-6">
            <img
                :src="photo.preview"
                :alt="photo.description"
                class="rounded-lg object-cover w-72"
            />
            <div class="flex-1">
                <p class="font-medium">
                    {{ photo.description }}
                </p>
            </div>
            <div class="rounded-full bg-gray-100 p-2 cursor-pointer" @click="deletePhoto">
                <trash-icon class="text-gray-400" />
            </div>
        </div>
    </div>
</template>
