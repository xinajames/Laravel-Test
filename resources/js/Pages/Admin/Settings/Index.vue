<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import Layout from '@/Layouts/Admin/Layout.vue';
import TabRolesPermission from '@/Components/Settings/Show/TabRolesPermission.vue';
import TabProfile from '@/Components/Settings/Show/TabProfile.vue';
import TabDataImport from '@/Components/Settings/Show/TabDataImport.vue';
import TabDocumentImport from '@/Components/Settings/Show/TabDocumentImport.vue';
import TabNotifications from '@/Components/Settings/Show/TabNotifications.vue';

const props = defineProps({
    user: Object,
    notificationSettings: Object,
});

const activeTab = ref('Profile');

const userPermissions = computed(() => usePage().props.auth.permissions);

const tabPermissions = {
    Notifications: ['read-setting-notifications', 'update-settings-notifications'],
    'Roles & Permissions': ['read-settings-roles-permissions', 'update-settings-roles-permissions'],
    'Data Import': ['read-settings-data-import', 'update-settings-data-import'],
    'Document Import': ['read-settings-data-import', 'update-settings-data-import'],
};

const hasPermission = (requiredPermissions) => {
    return requiredPermissions.some((permission) => userPermissions.value.includes(permission));
};

// Dynamically filter tabs based on permissions
const tabs = computed(() => {
    const baseTabs = [{ name: 'Profile', key: 'Profile' }];

    Object.entries(tabPermissions).forEach(([name, permissions]) => {
        if (hasPermission(permissions)) {
            baseTabs.push({ name, key: name });
        }
    });

    return baseTabs;
});
</script>

<template>
    <Head title="Settings" />

    <Layout :showTopBar="false">
        <template #header>
            <div class="bg-white">
                <div class="sm:flex sm:items-center mt-8 mb-6">
                    <div class="sm:flex-auto px-8">
                        <h1 class="text-3xl font-bold text-gray-900">Settings</h1>
                    </div>
                </div>
                <!-- Tabs -->
                <div class="bg-white sticky top-1 overflow-y-auto z-20">
                    <div class="border-b border-gray-200 px-8">
                        <nav aria-label="Tabs" class="-mb-px flex space-x-8">
                            <button
                                v-for="tab in tabs"
                                :key="tab.key"
                                :class="[
                                    activeTab === tab.key
                                        ? 'border-primary text-primary'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                                    'whitespace-nowrap border-b-2 px-4 py-2 text-sm font-medium focus:outline-none',
                                ]"
                                @click="activeTab = tab.key"
                            >
                                {{ tab.name }}
                            </button>
                        </nav>
                    </div>
                </div>
            </div>
        </template>

        <!-- Tab Content -->
        <div>
            <TabRolesPermission v-if="activeTab === 'Roles & Permissions'" />

            <TabProfile v-else-if="activeTab === 'Profile'" :user="user" />

            <TabDataImport v-if="activeTab === 'Data Import'" />

            <TabDocumentImport v-if="activeTab === 'Document Import'" />

            <TabNotifications
                v-if="activeTab === 'Notifications'"
                :notification-settings="notificationSettings"
            />
        </div>
    </Layout>
</template>
