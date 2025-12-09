<script setup>
const props = defineProps({
    padding: { type: String, default: 'p-6' },
});
</script>

<template>
    <div class="rounded-3xl" :class="padding">
        <slot name="content"></slot>
    </div>
</template>
