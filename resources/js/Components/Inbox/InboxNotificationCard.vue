<script setup>
import BellIcon from '@/Components/Icon/BellIcon.vue';
import InfiniteLoading from 'v3-infinite-loading';
import NotificationDetailsModal from '@/Components/Modal/NotificationDetailsModal.vue';
import { router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const emits = defineEmits(['loadMore']);

const props = defineProps({
    notifications: {
        type: Array,
        default: () => [],
    },
});

const showModal = ref(false);
const selectedNotification = ref(null);

// Truncation constants
const TRUNCATE_LENGTH = 150;

// Helper function to truncate text
const truncateText = (text, length = TRUNCATE_LENGTH) => {
    if (!text || text.length <= length) return text;
    return text.slice(0, length) + '...';
};

// Helper function to check if text needs truncation
const needsTruncation = (text) => {
    return text && text.length > TRUNCATE_LENGTH;
};

function handleNotificationClick(event, notification) {
    // Prevent default click behavior
    event.stopPropagation();

    // If the notification has a URL, mark as read and navigate
    if (notification.url) {
        markAsRead(notification);
        return;
    }

    // If no URL and message needs truncation, show modal
    if (needsTruncation(notification.message)) {
        selectedNotification.value = notification;
        showModal.value = true;
        markAsRead(notification);
        return;
    }

    // Otherwise just mark as read
    markAsRead(notification);
}

function markAsRead(notification) {
    router.post(
        route('notifications.read', notification.id),
        {},
        {
            onFinish: () => {
                if (notification.url) {
                    router.visit(notification.url);
                }
            },
        }
    );
}

function closeModal() {
    showModal.value = false;
    selectedNotification.value = null;
}
</script>

<template>
    <div>
        <div v-if="notifications.length > 0">
            <div
                v-for="notification in notifications"
                :key="notification.id"
                class="bg-white rounded-2xl flex gap-4 p-4 border border-gray-200 mb-4 cursor-pointer hover:bg-gray-50 transition"
                @click="handleNotificationClick($event, notification)"
            >
                <!-- Icon -->
                <div
                    class="w-11 h-11 flex-shrink-0 flex items-center justify-center bg-gray-50 rounded-full border border-gray-200 self-start sm:self-center"
                >
                    <BellIcon class="text-gray-200 w-5 h-5" />
                </div>

                <!-- Content -->
                <div class="flex-1 flex flex-col justify-between">
                    <div>
                        <p class="text-sm font-bold text-gray-900">
                            {{ notification.header }}
                        </p>
                        <div class="mt-1 text-sm text-gray-700">
                            <div
                                v-html="
                                    truncateText(notification.message)
                                        .replace(/\n/g, '<br>')
                                        .replace(/• /g, '&bull; ')
                                "
                            ></div>
                            <button
                                v-if="!notification.url && needsTruncation(notification.message)"
                                class="text-primary hover:text-primary-dark text-sm font-medium mt-1 inline-block"
                                @click.stop="
                                    selectedNotification = notification;
                                    showModal = true;
                                    markAsRead(notification);
                                "
                            >
                                Read more
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-3 sm:mt-4">
                        {{ notification.date }}
                    </p>
                </div>

                <!-- Unread Indicator -->
                <div class="flex items-center">
                    <span
                        v-if="!notification.isRead"
                        class="w-3 h-3 rounded-full ring-2 ring-white bg-green-400"
                    ></span>
                </div>
            </div>
            <InfiniteLoading class="invisible" @infinite="emits('loadMore')" />
        </div>
        <div
            v-else
            class="flex flex-col items-center justify-center py-10 text-center bg-white rounded-2xl border border-gray-200"
        >
            <p class="text-gray-500">No notifications found yet — check back soon!</p>
        </div>

        <!-- Notification Details Modal -->
        <NotificationDetailsModal
            :open="showModal"
            :notification="selectedNotification"
            @close="closeModal"
        />
    </div>
</template>
