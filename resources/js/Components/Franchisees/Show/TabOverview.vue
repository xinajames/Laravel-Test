<script setup>
import { computed } from 'vue';

import BriefcaseIcon from '@/Components/Icon/BriefcaseIcon.vue';
import FranchiseeDocumentDetails from '@/Components/Franchisees/Show/FranchiseeDocumentDetails.vue';
import FranchiseeStoresList from './FranchiseeStoresList.vue';
import HashtagIcon from '@/Components/Icon/HashtagIcon.vue';
import OfficeBuildingIcon from '@/Components/Icon/OfficeBuildingIcon.vue';
import FranchiseeRecentActivities from './FranchiseeRecentActivities.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import UserIcon from '@/Components/Icon/UserIcon.vue';

const emits = defineEmits(['updateTab']);

const props = defineProps({
    activities: { type: Array, default: () => [] },
    franchisee: Object,
});

const profileDetails = computed(() => {
    return [
        {
            label: 'Franchise Code',
            icon: BriefcaseIcon,
            detail: props.franchisee.franchisee_code || '—',
        },
        {
            label: 'Corporation Name',
            icon: BriefcaseIcon,
            detail: props.franchisee.corporation_name || '—',
        },
        {
            label: 'No of Stores',
            icon: OfficeBuildingIcon,
            detail: props.franchisee.stores.length,
        },
        {
            label: 'TIN',
            icon: HashtagIcon,
            detail: props.franchisee.tin || '—',
        },
        {
            label: 'Point Person',
            icon: UserIcon,
            detail: props.franchisee.fm_point_person || '—',
        },
    ];
});
</script>

<template>
    <div class="p-8 space-y-8">
        <div>
            <div class="flex justify-between items-center gap-4">
                <h1 class="text-xl font-sans font-semibold">Franchisee Profile</h1>
                <SecondaryButton
                    class="!rounded-md !text-gray-700 !font-medium"
                    @click="emits('updateTab', 'Profile')"
                >
                    All Details
                </SecondaryButton>
            </div>

            <div class="mt-4">
                <div
                    class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 xl:grid-cols-5 gap-4"
                >
                    <div
                        v-for="profile in profileDetails"
                        class="bg-white p-4 rounded-2xl border border-gray-200"
                    >
                        <div
                            class="h-8 w-8 flex items-center justify-center rounded-full bg-gray-100 border border-gray-200"
                        >
                            <component
                                :is="profile.icon"
                                class="w-5 h-5 text-gray-400 flex-shrink-0"
                                type="solid"
                            />
                        </div>
                        <div class="mt-4">
                            <p class="text-sm text-gray-600">{{ profile.label }}</p>
                            <h2 class="text-lg font-sans font-medium text-gray-600">
                                {{ profile.detail }}
                            </h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <FranchiseeStoresList :franchisee="franchisee" />

        <FranchiseeDocumentDetails :franchisee="franchisee" />

        <FranchiseeRecentActivities
            :activities="activities"
            :franchisee="franchisee"
            @update-tab="emits('updateTab', $event)"
        />
    </div>
</template>
