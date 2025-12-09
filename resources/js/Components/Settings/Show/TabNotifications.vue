<script setup>
import NotificationsCard from '@/Components/Settings/NotificationsCard.vue';

const props = defineProps({
    notificationSettings: Object,
});
</script>

<template>
    <div>
        <NotificationsCard :notification-settings="notificationSettings" />
    </div>
</template>
