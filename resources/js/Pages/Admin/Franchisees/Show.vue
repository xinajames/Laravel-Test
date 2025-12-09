<script setup>
import { onMounted, ref } from 'vue';
import { Head } from '@inertiajs/vue3';

import Breadcrumbs from '@/Components/Shared/Breadcrumbs.vue';
import FranchiseeHeader from '@/Components/Franchisees/Show/FranchiseeHeader.vue';
import Layout from '@/Layouts/Admin/Layout.vue';
import TabActivity from '@/Components/Franchisees/Show/TabActivity.vue';
import TabDocument from '@/Components/Franchisees/Show/TabDocument.vue';
import TabOverview from '@/Components/Franchisees/Show/TabOverview.vue';
import TabProfile from '@/Components/Franchisees/Show/TabProfile.vue';

const props = defineProps({
    activities: { type: Array, default: () => [] },
    franchisee: Object,
});

const activeTab = ref('Overview');

const tabs = [
    { name: 'Overview', key: 'Overview' },
    { name: 'Profile', key: 'Profile' },
    { name: 'Documents', key: 'Documents' },
    { name: 'Activity', key: 'Activity' },
];

function changeTab(tabKey) {
    if (activeTab.value === tabKey) return;

    activeTab.value = tabKey;

    const url = new URL(window.location.href);
    url.searchParams.set('tab', tabKey);

    window.history.replaceState({}, '', url);
}

onMounted(() => {
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
    <Head title="Franchisees" />

    <Layout :content-no-padding="true">
        <template #header>
            <FranchiseeHeader :franchisee="franchisee" />

            <!-- Tabs -->
            <div class="bg-white sticky top-1 overflow-y-auto z-20 pt-8">
                <div class="border-b border-gray-200">
                    <nav aria-label="Tabs" class="-mb-px flex space-x-8 ml-8">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            :class="[
                                activeTab === tab.key
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
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
                :franchisee="franchisee"
                @update-tab="activeTab = $event"
            />

            <TabProfile v-if="activeTab === 'Profile'" :franchisee="franchisee" />

            <TabDocument v-if="activeTab === 'Documents'" :franchisee="franchisee" />

            <TabActivity v-if="activeTab === 'Activity'" :franchisee="franchisee" />
        </div>
    </Layout>

    <!-- Breadcrumbs -->
    <Teleport to="#portal-breadcrumb">
        <Breadcrumbs
            :level1="{ name: 'Franchisees', route: 'franchisees' }"
            :level2="{
                name: franchisee.first_name + ' ' + franchisee.last_name,
                route: 'franchisees.show',
                route_id: franchisee.id,
            }"
            :levels="2"
        />
    </Teleport>
</template>
