<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';

import axios from 'axios';
import Breadcrumbs from '@/Components/Shared/Breadcrumbs.vue';
import ConfirmationModal from '@/Components/Modal/ConfirmationModal.vue';
import FloatingStoreRating from '@/Components/StoreRatings/FloatingStoreRating.vue';
import Layout from '@/Layouts/Admin/Layout.vue';
import StoreHeader from '@/Components/Stores/Show/StoreHeader.vue';
import TabActivity from '@/Components/Stores/Show/TabActivity.vue';
import TabDocument from '@/Components/Stores/Show/TabDocument.vue';
import TabDetails from '@/Components/Stores/Show/TabDetails.vue';
import TabOverview from '@/Components/Stores/Show/TabOverview.vue';
import TabRating from '@/Components/Stores/Show/TabRating.vue';
import TabNotifications from '@/Components/Stores/Show/TabNotifications.vue';
import TabReminders from '@/Components/Stores/Show/TabReminders.vue';

const props = defineProps({
    activities: { type: Array, default: () => [] },
    store: Object,
    ongoingStoreRating: Object,
});

const activeTab = ref('Overview');

const tabs = [
    { name: 'Overview', key: 'Overview' },
    { name: 'Details', key: 'Details' },
    { name: 'Documents', key: 'Documents' },
    { name: 'Rating', key: 'Rating' },
    { name: 'Notifications', key: 'Notifications' },
    { name: 'Reminders', key: 'Reminders' },
    { name: 'Activity', key: 'Activity' },
];

const confirmationModal = reactive({
    open: false,
    header: 'Store Rating In Progress',
    message:
        'You have an existing store rating review in progress. Would you like to continue where you left off or start a new review? Starting over will permanently erase your previously entered information.',
    icon: 'star',
    action_label: 'Continue Review',
    action: route('storeRatings.create', props.store.id),
    data: null,
    cancel_label: 'Start Over',
    cancel_action: true,
});

const storeRatings = reactive([]);

const userPermissions = computed(() => usePage().props.auth.permissions);

const fetchRatings = async (lastIndex) => {
    {
        try {
            let url = route('stores.getStoreRatings', [props.store.id, lastIndex]);
            const response = await axios.get(url);
            if (response.data) {
                response.data.forEach((storeRating) => {
                    if (!storeRatings.includes(storeRating)) {
                        storeRatings.push(storeRating);
                    }
                });
            }
        } catch (error) {
            console.error('Error fetching history:', error);
        }
    }
};

function startOver() {
    router.visit(route('storeRatings.create', { store: props.store.id, start: true }));
}

function changeTab(tabKey) {
    if (activeTab.value === tabKey) return;

    activeTab.value = tabKey;

    const url = new URL(window.location.href);
    url.searchParams.set('tab', tabKey);

    window.history.replaceState({}, '', url);
}

onMounted(() => {
    fetchRatings(0);

    const url = new URL(window.location.href);
    const tabParam = url.searchParams.get('tab');

    const tabKeys = tabs.map((t) => t.key);
    if (tabParam && tabKeys.includes(tabParam)) {
        activeTab.value = tabParam;
    } else {
        activeTab.value = 'Overview';
    }
});
</script>

<template>
    <Head title="Stores" />

    <Layout :content-no-padding="true">
        <template #header>
            <StoreHeader :store="store" />

            <!-- Floating - For ongoing store rating -->
            <FloatingStoreRating
                v-if="ongoingStoreRating"
                :store-rating="ongoingStoreRating"
                @continue="confirmationModal.open = true"
            />

            <!-- Tabs -->
            <div class="bg-white sticky top-10 overflow-y-auto z-20 pt-4">
                <div class="border-b border-gray-200">
                    <nav aria-label="Tabs" class="-mb-px flex space-x-8 ml-8">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            :class="[
                                activeTab === tab.key
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                                (tab.key === 'Notifications' || tab.key === 'Reminders') &&
                                !userPermissions.includes('read-stores-notifications-reminders') &&
                                !userPermissions.includes('update-stores-notifications-reminders')
                                    ? 'hidden'
                                    : 'block',
                                'whitespace-nowrap border-b-2 px-4 py-4 text-sm font-medium focus:outline-none',
                            ]"
                            @click="changeTab(tab.key)"
                        >
                            {{ tab.name }}
                        </button>
                    </nav>
                </div>
            </div>
        </template>

        <div>
            <TabOverview
                v-if="activeTab === 'Overview'"
                :activities="activities"
                :store="store"
                :store-ratings="storeRatings"
                :ongoing-store-rating="ongoingStoreRating"
                @update-tab="activeTab = $event"
                @get-more-ratings="fetchRatings(storeRatings.length)"
                @show-rating-modal="confirmationModal.open = true"
            />

            <TabDetails
                v-if="activeTab === 'Details'"
                :store="store"
                @update-tab="activeTab = $event"
            />

            <TabDocument v-if="activeTab === 'Documents'" :store="store" />

            <TabActivity
                v-if="activeTab === 'Activity'"
                :store="store"
                @update-tab="activeTab = $event"
            />

            <TabRating
                v-if="activeTab === 'Rating'"
                :activities="activities"
                :store="store"
                :store-ratings="storeRatings"
                :ongoing-store-rating="ongoingStoreRating"
                @update-tab="activeTab = $event"
                @get-more-ratings="fetchRatings(storeRatings.length)"
                @show-rating-modal="confirmationModal.open = true"
            />

            <TabNotifications
                v-if="activeTab === 'Notifications'"
                :store="store"
                @update-tab="activeTab = $event"
            />

            <TabReminders
                v-if="activeTab === 'Reminders'"
                :store="store"
                @update-tab="activeTab = $event"
            />
        </div>
    </Layout>

    <!-- Breadcrumbs -->
    <Teleport to="#portal-breadcrumb">
        <Breadcrumbs
            :level1="{ name: 'Stores', route: 'stores' }"
            :level2="{
                name: store.jbs_name,
                route: 'stores.show',
                route_id: store.id,
            }"
            :levels="2"
        />
    </Teleport>

    <ConfirmationModal
        :action="confirmationModal.action"
        :action_label="confirmationModal.action_label"
        :cancel_action="confirmationModal.cancel_action"
        :cancel_label="confirmationModal.cancel_label"
        :data="confirmationModal.data"
        :header="confirmationModal.header"
        :icon="confirmationModal.icon"
        :message="confirmationModal.message"
        :open="confirmationModal.open"
        method="visit"
        @close="confirmationModal.open = false"
        @success="confirmationModal.open = false"
        @cancel-action="startOver"
    />
</template>
