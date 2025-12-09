<script setup>
import InboxRemindersCard from '@/Components/Inbox/InboxRemindersCard.vue';

const props = defineProps({
    store: Object,
});
</script>

<template>
    <div class="p-8 space-y-6">
        <InboxRemindersCard :model_id="props.store.id" model_type="store" />
    </div>
</template>
