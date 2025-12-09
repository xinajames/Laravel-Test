<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

import axios from 'axios';
import InboxNotificationCard from '@/Components/Inbox/InboxNotificationCard.vue';
import InboxRemindersCard from '@/Components/Inbox/InboxRemindersCard.vue';
import Layout from '@/Layouts/Admin/Layout.vue';

const props = defineProps({
    stores: Array,
});

const activeTab = ref('Notifications');
const subTab = ref('all');
const unreadCount = ref(0);
const remindersCount = ref(0);

const tabs = computed(() => [
    {
        name: 'Notifications',
        key: 'Notifications',
        badge: unreadCount.value > 0 ? unreadCount.value : null,
    },
    {
        name: 'Reminders',
        key: 'Reminders',
        badge: remindersCount.value > 0 ? remindersCount.value : null,
    },
]);

const unreadTabs = computed(() => [
    { name: 'All', key: 'all' },
    { name: 'Unread', key: 'unread' },
]);

const notifications = ref([]);
const limit = ref(6);

// Fetch notifications with dynamic limit
const fetchNotifications = async (lastIndex, unread, loadMore) => {
    try {
        const url = route('notifications.getNotifications', {
            lastIndex,
            unread,
            limit: limit.value,
        });
        const response = await axios.get(url);

        if (loadMore) {
            notifications.value.push(...response.data);
        } else {
            notifications.value = response.data;
        }
    } catch (error) {
        console.error('Failed to fetch notifications:', error);
    }
};

// Fetch unread notifications count
const fetchUnreadCount = async () => {
    try {
        const url = route('notifications.getUnreadCount');
        const response = await axios.get(url);
        unreadCount.value = response.data;
    } catch (error) {
        console.error('Failed to fetch unread count:', error);
    }
};

// Fetch reminders count
const fetchRemindersCount = async () => {
    try {
        const url = route('reminders.getTodayRemindersCount');
        const response = await axios.get(url);
        remindersCount.value = response.data;
    } catch (error) {
        console.error('Failed to fetch reminders count:', error);
    }
};

function handleNotifTab(tab) {
    subTab.value = tab.key;
    notifications.value = [];
    fetchNotifications(notifications.value.length, tab.key === 'unread', false);
}

function markAllAsRead() {
    router.post(
        route('notifications.read'),
        {},
        {
            onSuccess: () => {
                fetchUnreadCount();
            },
        }
    );
}

// Function to reload all counts
const reloadCounts = () => {
    fetchUnreadCount();
    fetchRemindersCount();
};

// Watch for tab changes to update data
watch(activeTab, (newTab) => {
    if (newTab === 'Notifications') {
        fetchUnreadCount();
    } else if (newTab === 'Reminders') {
        fetchRemindersCount();
    }
});

onMounted(() => {
    fetchNotifications(notifications.value.length, false, false);
    reloadCounts();
});
</script>

<template>
    <Head title="Inbox" />

    <Layout :showTopBar="false">
        <template #header>
            <div class="bg-white">
                <div class="flex justify-between items-center mt-8 mb-6 px-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Inbox</h1>
                    </div>

                    <!-- Show Sub Tabs only when Notifications tab is active -->
                    <div v-if="activeTab === 'Notifications'" class="flex items-center gap-4">
                        <!-- Sub Tabs -->
                        <div class="bg-gray-100 p-1.5 rounded-lg flex space-x-1">
                            <button
                                v-for="tab in unreadTabs"
                                :key="tab.key"
                                :class="[
                                    subTab === tab.key
                                        ? 'bg-white shadow text-gray-900 border border-gray-300'
                                        : 'text-gray-500 hover:text-gray-700',
                                    'px-3 py-1 text-sm font-medium rounded-lg transition',
                                ]"
                                @click="handleNotifTab(tab)"
                            >
                                {{ tab.name }}
                            </button>
                        </div>

                        <!-- Mark all as Read -->
                        <div
                            class="cursor-pointer text-red-700 font-medium text-sm"
                            @click="markAllAsRead"
                        >
                            Mark all as Read
                        </div>
                    </div>
                </div>

                <!-- Main Tabs -->
                <div class="border-b border-gray-200 px-8 sticky top-1 z-20 bg-white">
                    <nav aria-label="Tabs" class="-mb-px flex space-x-8">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            :class="[
                                activeTab === tab.key
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                                'whitespace-nowrap border-b-2 px-4 py-2 text-sm font-medium focus:outline-none inline-flex items-center gap-1',
                            ]"
                            @click="activeTab = tab.key"
                        >
                            {{ tab.name }}
                            <span
                                v-if="tab.badge"
                                class="bg-primary px-2.5 py-0.5 ml-1 text-white text-xs rounded-full flex items-center justify-center"
                            >
                                {{ tab.badge }}
                            </span>
                        </button>
                    </nav>
                </div>
            </div>
        </template>

        <!-- Tab Content -->
        <div class="p-8">
            <InboxNotificationCard
                v-if="activeTab === 'Notifications'"
                :notifications="notifications"
                @load-more="fetchNotifications(notifications.length, subTab === 'unread', true)"
            />

            <InboxRemindersCard
                v-if="activeTab === 'Reminders'"
                :stores="stores"
                @reload="fetchRemindersCount"
            />
        </div>
    </Layout>
</template>
