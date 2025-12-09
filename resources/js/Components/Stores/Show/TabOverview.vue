<script setup>
import { computed, onMounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';

import axios from 'axios';
import Avatar from '@/Components/Common/Avatar/Avatar.vue';
import BriefcaseIcon from '@/Components/Icon/BriefcaseIcon.vue';
import HashtagIcon from '@/Components/Icon/HashtagIcon.vue';
import MailIcon from '@/Components/Icon/MailIcon.vue';
import OfficeBuildingIcon from '@/Components/Icon/OfficeBuildingIcon.vue';
import PhoneIcon from '@/Components/Icon/PhoneIcon.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import StoreRecentActivities from '@/Components/Stores/Show/StoreRecentActivities.vue';
import UserIcon from '@/Components/Icon/UserIcon.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import StarOutlineIcon from '@/Components/Icon/StarOutlineIcon.vue';
import StoreDocumentDetails from '@/Components/Stores/Show/StoreDocumentDetails.vue';
import RatingItem from '@/Components/Stores/RatingItem.vue';
import ReminderBellIcon from '@/Components/Icon/ReminderBellIcon.vue';

const emits = defineEmits(['updateTab']);

const props = defineProps({
    store: Object,
    storeRatings: { type: Array, default: () => [] },
    activities: { type: Array, default: () => [] },
    ongoingStoreRating: Object,
});

const todayReminders = ref([]);

const fetchTodayReminders = async () => {
    try {
        const url = route('reminders.getTodayReminders', {
            type: 'store',
            id: props.store.id,
        });

        const response = await axios.get(url);

        // Ensure response.data is an array before filtering
        const data = Array.isArray(response.data) ? response.data : [];
        todayReminders.value = data.filter((r) => !r.dismissed && !r.deleted);
    } catch (error) {
        console.error('Failed to fetch today reminders:', error);
        todayReminders.value = [];
    }
};

const notificationReminders = ref([]);

const fetchNotificationReminders = async () => {
    try {
        const url = route('reminders.getNotificationReminders', {
            type: 'store',
            id: props.store.id,
        });

        const response = await axios.get(url);

        // Ensure response.data is an array before filtering
        const data = Array.isArray(response.data) ? response.data : [];
        notificationReminders.value = data.filter((r) => !r.dismissed && !r.deleted);
    } catch (error) {
        console.error('Failed to fetch notification reminders:', error);
        notificationReminders.value = [];
    }
};

onMounted(async () => {
    await fetchTodayReminders();
    await fetchNotificationReminders();
});

const dismissReminder = (id) => {
    todayReminders.value = todayReminders.value.filter((reminder) => reminder.id !== id);
    notificationReminders.value = notificationReminders.value.filter(
        (reminder) => reminder.id !== id
    );
};

const profileDetails = computed(() => {
    return [
        {
            label: 'Branch Code',
            icon: BriefcaseIcon,
            detail: props.store.store_code || '—',
        },
        {
            label: 'Cluster Code',
            icon: BriefcaseIcon,
            detail: props.store.cluster_code || '—',
        },
        {
            label: 'Store Group',
            icon: OfficeBuildingIcon,
            detail: props.store.store_group_label || '—',
        },
        {
            label: 'Store Type',
            icon: HashtagIcon,
            detail: props.store.store_type_label || '—',
        },
        {
            label: 'Point Person',
            icon: UserIcon,
            detail: props.store.om_district_manager || '—',
        },
    ];
});

function goToFranchisee(franchiseeId) {
    router.visit(route('franchisees.show', franchiseeId));
}

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
        <div
            v-if="todayReminders.length || notificationReminders.length"
            class="bg-yellow-50 rounded-lg p-2 border border-gray-200"
        >
            <!-- Loop: Today Reminders -->
            <div
                v-for="reminder in todayReminders"
                :key="'today-' + reminder.id"
                class="flex items-center justify-between py-3 border-b last:border-none gap-4"
            >
                <div class="flex items-center gap-3 flex-1">
                    <ReminderBellIcon class="text-yellow-400 w-5 h-5" />
                    <p class="text-yellow-700 text-sm">
                        <span class="font-medium">{{ reminder.title }}.</span>
                        {{ reminder.description }}
                    </p>
                </div>
                <button
                    class="text-sm font-medium text-yellow-700 hover:underline px-2"
                    @click="dismissReminder(reminder.id)"
                >
                    Dismiss
                </button>
            </div>

            <!-- Loop: Notification Reminders -->
            <div
                v-for="reminder in notificationReminders"
                :key="'notif-' + reminder.id"
                class="flex items-center justify-between py-3 border-b last:border-none gap-4"
            >
                <div class="flex items-center gap-3 flex-1">
                    <ReminderBellIcon class="text-yellow-400 w-5 h-5" />
                    <p class="text-yellow-700 text-sm">
                        <span class="font-medium">{{ reminder.title }}.</span>
                        {{ reminder.description }}
                    </p>
                </div>
                <button
                    class="text-sm font-medium text-yellow-700 hover:underline px-2"
                    @click="dismissReminder(reminder.id)"
                >
                    Dismiss
                </button>
            </div>
        </div>

        <div
            class="p-6 bg-white rounded-2xl flex items-center gap-4 justify-between border border-gray-200"
        >
            <div class="flex items-center gap-4">
                <Avatar
                    :image-url="store.franchisee?.franchisee_profile_photo_url"
                    custom-class="size-[80px]"
                    image-class="w-full h-full rounded-full object-cover"
                />
                <div>
                    <p class="text-sm text-gray-500">Store Owned By</p>
                    <h5 class="text-xl font-semibold">
                        {{ store.franchisee?.full_name }}
                        <span v-if="store.franchisee?.corporation_name">
                            - {{ store.franchisee?.corporation_name }}
                        </span>
                    </h5>
                    <div class="flex gap-8 mt-1.5">
                        <p class="text-sm text-gray-500 font-medium flex items-center gap-1.5">
                            <BriefcaseIcon class="w-4 h-4 text-gray-400" />
                            {{ store.franchisee?.franchisee_code || '—' }}
                        </p>
                        <p class="text-sm text-gray-500 font-medium flex items-center gap-1.5">
                            <MailIcon class="w-4 h-4 text-gray-400" />
                            {{ store.franchisee?.email || '—' }}
                        </p>
                        <p class="text-sm text-gray-500 font-medium flex items-center gap-1.5">
                            <PhoneIcon class="w-4 h-4 text-gray-400" />
                            {{ store.franchisee?.contact_number || '—' }}
                        </p>
                    </div>
                </div>
            </div>
            <SecondaryButton
                class="!bg-rose-50 !text-primary !ring-transparent"
                @click="goToFranchisee(store.franchisee.id)"
            >
                View Profile
            </SecondaryButton>
        </div>
        <div>
            <div class="flex justify-between items-center gap-4">
                <h1 class="text-xl font-sans font-semibold">Store Details</h1>
                <SecondaryButton
                    class="!rounded-md !text-gray-700 !font-medium"
                    @click="emits('updateTab', 'Details')"
                >
                    All Details
                </SecondaryButton>
            </div>
            <div class="mt-4">
                <div
                    class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 xl:grid-cols-5 gap-4"
                >
                    <div
                        v-for="profile in profileDetails"
                        class="bg-white p-4 rounded-2xl border border-gray-200"
                    >
                        <div
                            class="h-8 w-8 flex items-center justify-center rounded-full bg-gray-100 border border-gray-200"
                        >
                            <component
                                :is="profile.icon"
                                class="w-5 h-5 text-gray-400 flex-shrink-0"
                                type="solid"
                            />
                        </div>
                        <div class="mt-4">
                            <p class="text-sm text-gray-600">{{ profile.label }}</p>
                            <h2 class="text-lg font-sans font-medium text-gray-600">
                                {{ profile.detail }}
                            </h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="storeRatings && storeRatings.length > 0">
            <div class="flex justify-between items-center gap-4">
                <div>
                    <h1 class="text-xl font-sans font-semibold">Overall Store Rating</h1>
                    <p class="text-sm text-gray-900">as of {{ storeRatings[0]?.rated_at }}</p>
                </div>
                <div class="flex gap-3">
                    <SecondaryButton @click="emits('updateTab', 'Rating')">
                        Rating History
                    </SecondaryButton>
                    <PrimaryButton @click="handleRating">
                        <StarOutlineIcon class="h-4 w-4" />
                        Rate Store
                    </PrimaryButton>
                </div>
            </div>
            <div>
                <RatingItem :store_rating="storeRatings[0]" />
            </div>
        </div>
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

        <StoreDocumentDetails :store="store" />

        <StoreRecentActivities
            :activities="activities"
            :store="store"
            @update-tab="emits('updateTab', $event)"
        />
    </div>
</template>

<style scoped></style>
