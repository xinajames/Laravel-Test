<script setup>
import { router, useForm } from '@inertiajs/vue3';
import { BriefcaseIcon, EnvelopeIcon, PhoneIcon } from '@heroicons/vue/24/solid/index.js';

import Avatar from '@/Components/Common/Avatar/Avatar.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';

const props = defineProps({
    franchisee: Object,
});

const form = useForm({
    current_step: 'finished',
});

function handleActions(type) {
    if (type === 'profile') {
        router.visit(route('franchisees.show', props.franchisee.id));
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
            <p class="text-gray-900 font-bold text-xl">Franchisee successfully created!</p>
            <p class="text-gray-500 font-medium text-sm">
                Successfully created store profile. Continue managing it under the Franchisees
                module - add stores, edit further details, upload photos, and more.
            </p>
            <div class="rounded-2xl bg-gray-50 border border-gray-200 flex gap-4 p-4">
                <Avatar
                    :image-url="franchisee.franchisee_profile_photo"
                    image-class="!size-28 object-cover rounded-full"
                />
                <div class="space-y-2">
                    <p class="font-medium text-gray-500">
                        Franchisee
                        <span v-if="franchisee?.franchisee_code">
                            {{ franchisee.francisee_code }}
                        </span>
                    </p>
                    <p class="text-lg font-semibold">
                        {{ franchisee.last_name }}, {{ franchisee.first_name }}
                        {{ franchisee.middle_name }}
                    </p>
                    <div
                        v-if="franchisee.franchisee_code"
                        class="mr-4 inline-flex items-center gap-2"
                    >
                        <BriefcaseIcon class="w-5 h-5 text-gray-400" />
                        <p class="font-medium text-gray-500">
                            {{ franchisee.franchisee_code }}
                        </p>
                    </div>
                    <div v-if="franchisee.email" class="inline-flex items-center gap-2">
                        <EnvelopeIcon class="w-5 h-5 text-gray-400" />
                        <p class="font-medium text-gray-500">{{ franchisee.email }}</p>
                    </div>
                    <div v-if="franchisee.contact_number" class="flex items-center gap-2">
                        <PhoneIcon class="w-5 h-5 text-gray-400" />
                        <p class="font-medium text-gray-500">
                            {{ franchisee.contact_number }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4 pt-4">
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
