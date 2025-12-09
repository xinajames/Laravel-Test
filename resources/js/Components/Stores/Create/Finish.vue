<script setup>
import { BriefcaseIcon } from '@heroicons/vue/24/solid/index.js';
import { router, useForm } from '@inertiajs/vue3';

import Avatar from '@/Components/Common/Avatar/Avatar.vue';
import LocationMarker from '@/Components/Icon/LocationMarker.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import StorePlaceholder from '@/Components/Stores/StorePlaceholder.vue';

const props = defineProps({
    store: Object,
});

const form = useForm({
    current_step: 'finished',
});

function handleActions(type) {
    if (type === 'profile') {
        router.visit(route('stores.show', props.store.id));
    } else {
        router.visit(route('stores.create'));
    }
}
</script>

<template>
    <div class="bg-white shadow sm:rounded-2xl p-6">
        <div class="space-y-4">
            <svg
                fill="none"
                height="60"
                viewBox="0 0 60 60"
                width="60"
                xmlns="http://www.w3.org/2000/svg"
            >
                <rect
                    height="58"
                    rx="29"
                    stroke="#A32130"
                    stroke-width="2"
                    width="58"
                    x="1"
                    y="1"
                />
                <path
                    clip-rule="evenodd"
                    d="M40.0607 22.9393C40.6464 23.5251 40.6464 24.4749 40.0607 25.0607L28.0607 37.0607C27.4749 37.6464 26.5251 37.6464 25.9393 37.0607L19.9393 31.0607C19.3536 30.4749 19.3536 29.5251 19.9393 28.9393C20.5251 28.3536 21.4749 28.3536 22.0607 28.9393L27 33.8787L37.9393 22.9393C38.5251 22.3536 39.4749 22.3536 40.0607 22.9393Z"
                    fill="#A32130"
                    fill-rule="evenodd"
                />
            </svg>
            <p class="text-gray-900 font-bold text-xl">Store profile successfully created!</p>
            <p class="text-gray-500 font-medium text-sm">
                Successfully created store profile. Continue managing it under the Stores module -
                edit further details, upload store photos, add ratings, and more.
            </p>
            <div class="rounded-2xl bg-gray-50 border border-gray-200 gap-4 p-4">
                <div class="space-y-2">
                    <p class="text-lg font-semibold">
                        {{ store.jbs_name }}
                    </p>
                    <div class="grid md:grid-cols-1 lg:flex lg:gap-6">
                        <div v-if="store.store_code" class="flex items-center gap-2">
                            <p class="text-sm font-medium text-gray-500">#{{ store.store_code }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <Avatar
                                :image-url="store.franchisee?.franchisee_profile_photo"
                                image-class="!size-5 object-cover rounded-full"
                            />
                            <p class="text-sm font-medium text-gray-500">
                                {{ store.franchisee?.full_name }}
                                <span v-if="store.franchisee?.corporation_name">
                                    -
                                    {{ store.franchisee?.corporation_name }}
                                </span>
                            </p>
                        </div>
                        <div v-if="store.branch_code" class="flex items-center gap-2">
                            <BriefcaseIcon class="size-5 text-gray-400" />
                            <p class="text-sm font-medium text-gray-500">
                                {{ store.branch_code || '—' }}
                            </p>
                        </div>
                        <div
                            v-if="store.region || store.area || store.district"
                            class="flex items-center gap-2"
                        >
                            <LocationMarker />
                            <p class="font-medium text-gray-500">{{ store.region || '—' }}</p>
                            <span class="text-gray-500">•</span>
                            <p class="font-medium text-gray-500">{{ store.area || '—' }}</p>
                            <span class="text-gray-500">•</span>
                            <p class="font-medium text-gray-500">{{ store.district || '—' }}</p>
                        </div>
                    </div>

                    <div class="relative">
                        <div class="aspect-[550/243] rounded-lg overflow-hidden">
                            <StorePlaceholder />
                        </div>

                        <img
                            v-if="store.store_photos && store.store_photos.length > 0"
                            alt="Store image"
                            class="aspect-[550/243] rounded-lg object-cover w-full border-2 border-white absolute inset-0 overflow-hidden"
                            :src="store.store_photos[0].preview"
                        />
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton class="!font-medium" @click="handleActions('profile')">
                    Go to Profile
                </PrimaryButton>
                <SecondaryButton
                    class="!bg-rose-50 !text-primary !ring-transparent !font-medium"
                    @click="handleActions('store')"
                >
                    Add a Store
                </SecondaryButton>
            </div>
        </div>
    </div>
</template>
