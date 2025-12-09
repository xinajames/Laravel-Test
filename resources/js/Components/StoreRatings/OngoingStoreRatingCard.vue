<script setup>
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import BriefcaseIcon from '@/Components/Icon/BriefcaseIcon.vue';
import ConfirmationModal from '@/Components/Modal/ConfirmationModal.vue';
import LocationMarker from '@/Components/Icon/LocationMarker.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import TrashIcon from '@/Components/Icon/TrashIcon.vue';

const props = defineProps({
    storeRatings: Array,
});

const confirmationModal = reactive({
    action: null,
    open: false,
    header: null,
    message: null,
    icon: null,
    action_label: null,
    data: null,
    cancel_label: null,
    cancel_action: false,
    method: null,
});

const selectedId = ref(null);

function handleViewStore(id) {
    router.visit(route('stores.show', id));
}

function handleContinue(id) {
    confirmationModal.open = true;
    confirmationModal.header = 'Store Rating In Progress';
    confirmationModal.message =
        'You have an existing store rating review in progress. Would you like to continue where you left off or start a new review? Starting over will permanently erase your previously entered information.';
    confirmationModal.action_label = 'Continue Review';
    confirmationModal.icon = 'star';
    confirmationModal.cancel_action = true;
    confirmationModal.cancel_label = 'Start Over';
    confirmationModal.method = 'visit';
    confirmationModal.action = route('storeRatings.create', id);
    selectedId.value = id;
}

function handleDelete(id) {
    confirmationModal.cancel_label = 'Cancel';
    confirmationModal.open = true;
    confirmationModal.header = 'Delete Review';
    confirmationModal.message =
        'Are you sure you want to delete your ongoing store review? This action cannot be undone.';
    confirmationModal.action_label = 'Delete Review';
    confirmationModal.icon = 'delete';
    confirmationModal.method = 'post';
    confirmationModal.action = route('storeRatings.delete', id);
}

function startOver() {
    router.visit(route('storeRatings.create', { store: selectedId.value, start: true }));
}
</script>
<template>
    <div class="bg-white border border-gray-200 rounded-2xl p-4">
        <p class="text-sm text-primary font-medium">Ongoing Store Rating</p>
        <div class="divide-y space-y-6">
            <div v-for="storeRating in storeRatings">
                <div class="flex items-center gap-8 justify-between mt-4">
                    <div class="flex gap-4">
                        <div class="size-12">
                            <img
                                v-if="storeRating.thumbnail"
                                :src="storeRating.thumbnail"
                                alt=""
                                class="rounded-xl object-cover w-full h-full border-2 border-white relative overflow-hidden"
                                @error="this.style.display = 'none'"
                            />
                            <div
                                v-else
                                class="bg-gray-100 relative w-full h-full flex items-center justify-center rounded-2xl"
                            >
                                <!-- Background Image -->
                                <img
                                    alt=""
                                    class="absolute inset-0 w-full h-full object-cover rounded-2xl"
                                    src="/img/placeholder/placeholder_bg.png"
                                />
                                <!-- Store Placeholder Image -->
                                <img
                                    alt=""
                                    class="relative h-[65%] w-auto object-cover"
                                    src="/img/placeholder/placeholder_store.png"
                                />
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-semibold">{{ storeRating.store?.jbs_name }}</p>
                            <div class="flex gap-2 items-center">
                                <div class="inline-flex items-center gap-2">
                                    <BriefcaseIcon class="w-5 h-5 text-gray-400" />
                                    <p class="font-medium text-gray-500">
                                        {{ storeRating.store?.store_code }}
                                    </p>
                                </div>
                                <div
                                    class="flex flex-wrap items-center gap-2 text-sm md:text-base text-gray-500"
                                >
                                    <LocationMarker />
                                    <p>{{ storeRating.store?.region || 'N/A' }}</p>
                                    <span class="hidden md:inline">•</span>
                                    <p>{{ storeRating.store?.area || 'N/A' }}</p>
                                    <span class="hidden md:inline">•</span>
                                    <p class="w-full md:w-auto">
                                        {{ storeRating.store?.district || 'N/A' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-4 items-center">
                        <SecondaryButton @click="handleViewStore(storeRating.store.id)">
                            View Store
                        </SecondaryButton>
                        <PrimaryButton @click="handleContinue(storeRating.store.id)">
                            Continue Review
                        </PrimaryButton>
                        <div
                            class="cursor-pointer text-gray-500"
                            @click="handleDelete(storeRating.id)"
                        >
                            <trash-icon />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <ConfirmationModal
        :action="confirmationModal.action"
        :action_label="confirmationModal.action_label"
        :cancel_action="confirmationModal.cancel_action"
        :cancel_label="confirmationModal.cancel_label"
        :data="confirmationModal.data"
        :header="confirmationModal.header"
        :icon="confirmationModal.icon"
        :message="confirmationModal.message"
        :method="confirmationModal.method"
        :open="confirmationModal.open"
        @cancel-action="startOver"
        @close="confirmationModal.open = false"
        @success="confirmationModal.open = false"
    />
</template>
