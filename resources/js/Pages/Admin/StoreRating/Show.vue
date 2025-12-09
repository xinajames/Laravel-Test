<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { STORE_RATING_STEP } from '@/Composables/Enums.js';

import Layout from '@/Layouts/Admin/Layout.vue';
import Breadcrumbs from '@/Components/Shared/Breadcrumbs.vue';
import RatingHeader from '@/Components/StoreRatings/RatingHeader.vue';
import RatingReviewSummaryCard from '@/Components/StoreRatings/RatingReviewSummaryCard.vue';
import ShieldCheckIcon from '@/Components/Icon/ShieldCheckIcon.vue';
import SparkleIcon from '@/Components/Icon/SparkleIcon.vue';
import ThumbIcon from '@/Components/Icon/ThumbIcon.vue';
import UserIcon from '@/Components/Icon/UserIcon.vue';
import EmojiHappyIcon from '@/Components/Icon/EmojiHappyIcon.vue';
import QuestionnaireSummaryCard from '@/Components/StoreRatings/QuestionnaireSummaryCard.vue';

const props = defineProps({
    activities: { type: Array, default: () => [] },
    storeRating: Object,
    questionnaires: Object,
});

const activeTab = ref('Authorized Products');

const tabs = [
    {
        name: 'Authorized Products',
        key: STORE_RATING_STEP.AuthorizedProducts,
        icon: ShieldCheckIcon,
    },
    {
        name: 'Cleanliness, Sanitation and Maintenance',
        key: STORE_RATING_STEP.CleanlinessSanitationMaintenance,
        icon: SparkleIcon,
    },
    { name: 'Production Quality', key: STORE_RATING_STEP.ProductionQuality, icon: ThumbIcon },
    {
        name: 'Operational Excellence and Food Safety',
        key: STORE_RATING_STEP.OperationalExcellenceFoodSafety,
        icon: UserIcon,
    },
    {
        name: 'Customer Experience',
        key: STORE_RATING_STEP.CustomerExperience,
        icon: EmojiHappyIcon,
    },
];

const filteredQuestionnaires = computed(() => {
    return props.questionnaires[activeTab.value];
});
</script>

<template>
    <Head title="Stores" />

    <Layout :content-no-padding="true">
        <template #header>
            <RatingHeader :store-rating="storeRating" />
        </template>

        <div class="p-8">
            <RatingReviewSummaryCard :store-rating="storeRating" />

            <div class="border-b border-gray-200">
                <nav aria-label="Tabs" class="-mb-px flex space-x-6">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        :class="[
                            activeTab === tab.name
                                ? 'border-primary text-primary'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                            'whitespace-nowrap border-b-2 px-4 py-4 text-sm font-medium flex items-center gap-2 focus:outline-none',
                        ]"
                        @click="activeTab = tab.name"
                    >
                        <component :is="tab.icon" class="w-5 h-5 text-current" />
                        {{ tab.name }}
                    </button>
                </nav>
            </div>
            <div class="py-6 space-y-6">
                <h5 class="text-xl text-gray-900 font-semibold">
                    {{ activeTab }}
                </h5>
                <questionnaire-summary-card :questionnaires="filteredQuestionnaires" />
            </div>
        </div>
    </Layout>

    <Teleport to="#portal-breadcrumb">
        <Breadcrumbs
            :level1="{ name: 'Stores', route: 'stores' }"
            :level2="{
                name: storeRating.store.store_code,
                route: 'stores.show',
                route_id: storeRating.id,
            }"
            :level3="{
                name: `${storeRating.store.store_code}-${storeRating.store.id}`,
                route: 'stores.show',
                route_id: storeRating.id,
            }"
            :levels="3"
        />
    </Teleport>
</template>

<style scoped></style>
