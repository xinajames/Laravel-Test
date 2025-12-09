<script setup>
import FranchiseeActivitiesDataTable from './FranchiseeActivitiesDataTable.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';

const emits = defineEmits(['updateTab']);

const props = defineProps({
    activities: { type: Array, default: () => [] },
    franchisee: Object,
});
</script>

<template>
    <div>
        <div class="flex justify-between items-center gap-4 mt-6">
            <h1 class="text-xl font-sans font-semibold text-gray-900">Recent Activities</h1>
            <div class="flex gap-4">
                <SecondaryButton
                    class="!rounded-md !text-gray-700 !font-medium"
                    @click="emits('updateTab', 'Activity')"
                >
                    All Activities
                </SecondaryButton>
            </div>
        </div>
        <FranchiseeActivitiesDataTable class="mt-5" :activities="activities" :recent="true" />
    </div>
</template>
