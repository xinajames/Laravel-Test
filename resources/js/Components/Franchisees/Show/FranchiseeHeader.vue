<script setup>
import StatusBadge from '@/Components/Common/Badge/StatusBadge.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import PencilIcon from '@/Components/Icon/PencilIcon.vue';
import DotsVertical from '@/Components/Icon/DotsVertical.vue';
import BriefcaseIcon from '@/Components/Icon/BriefcaseIcon.vue';
import MailIcon from '@/Components/Icon/MailIcon.vue';
import PhoneIcon from '@/Components/Icon/PhoneIcon.vue';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import TrashIcon from '@/Components/Icon/TrashIcon.vue';
import BanIcon from '@/Components/Icon/BanIcon.vue';
import Avatar from '@/Components/Common/Avatar/Avatar.vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import ConfirmationModal from '@/Components/Modal/ConfirmationModal.vue';
import { FRANCHISEE_STATUS } from '@/Composables/Enums.js';
import RefreshIcon from '@/Components/Icon/RefreshIcon.vue';

const props = defineProps({
    franchisee: Object,
});

function handleEdit() {
    router.visit(route('franchisees.edit', props.franchisee.id));
}

const confirmationModal = reactive({
    open: false,
    header: null,
    message: null,
    icon: 'document',
    action_label: null,
    action: null,
});

function handleAction(type, id) {
    if (type === 'delete') {
        confirmationModal.header = 'Delete Franchisee';
        confirmationModal.message =
            'Are you sure you want to delete this franchisee? This action cannot be undone.';
        confirmationModal.icon = 'delete';
        confirmationModal.action_label = 'Delete';
        confirmationModal.action = route('franchisees.delete', id);
    } else if (type === 'deactivate') {
        confirmationModal.header = 'Deactivate Franchisee';
        confirmationModal.message = 'Are you sure you want to deactivate this franchisee?';
        confirmationModal.icon = 'deactivate';
        confirmationModal.action_label = 'Deactivate';
        confirmationModal.action = route('franchisees.deactivate', id);
    } else if (type === 'reactivate') {
        confirmationModal.header = 'Reactivate Franchisee';
        confirmationModal.message = 'Are you sure you want to reactivate this franchisee?';
        confirmationModal.icon = 'reactivate';
        confirmationModal.action_label = 'Reactivate';
        confirmationModal.action = route('franchisees.activate', id);
    }
    confirmationModal.open = true;
}

const canUpdateFranchisees = computed(() => {
    return usePage().props.auth.permissions.includes('update-franchisees');
});
</script>

<template>
    <div class="w-full relative bg-white">
        <!-- Header Image -->
        <div class="bg-integrity lg:h-[108px] h-20">
            <img
                alt="banner_image"
                class="w-full h-full object-cover"
                src="/img/franchisee_banner.png"
            />
        </div>

        <!-- Main Content -->
        <div class="flex flex-col lg:flex-row items-center lg:items-start lg:mx-12">
            <!-- Avatar -->
            <div
                class="w-[96px] h-[96px] lg:w-[130px] lg:h-[130px] -mt-12 lg:-mt-16 rounded-full border-1 border-white"
            >
                <Avatar
                    :image-url="franchisee.franchisee_profile_photo"
                    custom-class="w-[96px] h-[96px] lg:w-[130px] lg:h-[130px]"
                    image-class="w-full h-full rounded-full object-cover"
                />
            </div>

            <!-- Franchisee Details -->
            <div class="flex flex-col gap-2 lg:ml-4 w-full text-center lg:text-left">
                <div
                    class="flex flex-col lg:flex-row justify-between items-center lg:items-start w-full"
                >
                    <div class="mt-4">
                        <div
                            class="flex flex-wrap items-center justify-center lg:justify-start gap-2"
                        >
                            <h3 class="font-bold text-gray-900">
                                {{ franchisee.last_name }}, {{ franchisee.first_name }}
                                {{ franchisee.middle_name }}
                            </h3>
                            <StatusBadge
                                :category="'franchiseeStatus'"
                                :type="franchisee.status === 1 ? 'Active' : 'Inactive'"
                                class="!rounded-full [&_svg]:hidden"
                            >
                                {{ franchisee.status_description }}
                            </StatusBadge>
                        </div>
                        <p class="text-sm font-medium text-gray-400 mt-2">
                            {{ franchisee.full_residential_address }}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div v-if="canUpdateFranchisees" class="flex items-center gap-2 mt-4 lg:mt-4">
                        <SecondaryButton class="!text-gray-700" @click="handleEdit()">
                            <PencilIcon class="size-5" />
                            Edit
                        </SecondaryButton>
                        <Menu as="div" class="relative inline-block text-left">
                            <MenuButton
                                class="inline-flex justify-center gap-x-1.5 rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                            >
                                <DotsVertical />
                            </MenuButton>

                            <MenuItems
                                class="absolute right-0 mt-2 w-56 origin-top-right z-30 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                            >
                                <MenuItem v-slot="{ active }">
                                    <button
                                        :class="[
                                            active
                                                ? 'bg-gray-100 text-gray-900 outline-none'
                                                : 'text-gray-700',
                                            'group flex items-center w-full px-4 py-2 text-sm text-left',
                                        ]"
                                        @click="handleAction('delete', franchisee.id)"
                                    >
                                        <TrashIcon
                                            :class="[
                                                active ? 'text-gray-500' : '',
                                                'mr-3 size-5 text-gray-400',
                                            ]"
                                            aria-hidden="true"
                                        />
                                        Delete
                                    </button>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <button
                                        :class="[
                                            active
                                                ? 'bg-gray-100 text-gray-900 outline-none'
                                                : 'text-gray-700',
                                            'group flex items-center w-full px-4 py-2 text-sm text-left',
                                        ]"
                                        @click="
                                            handleAction(
                                                franchisee.status === FRANCHISEE_STATUS.Active
                                                    ? 'deactivate'
                                                    : 'reactivate',
                                                franchisee.id
                                            )
                                        "
                                    >
                                        <BanIcon
                                            v-if="franchisee.status === FRANCHISEE_STATUS.Active"
                                            :class="[
                                                active ? 'text-gray-500' : '',
                                                'mr-3 size-5 text-gray-400',
                                            ]"
                                            aria-hidden="true"
                                        />
                                        <RefreshIcon
                                            v-else
                                            :class="[
                                                active ? 'text-gray-500' : '',
                                                'mr-3 size-5 text-gray-400',
                                            ]"
                                            aria-hidden="true"
                                        />
                                        {{
                                            franchisee.status === FRANCHISEE_STATUS.Active
                                                ? 'Deactivate'
                                                : 'Reactivate'
                                        }}
                                    </button>
                                </MenuItem>
                            </MenuItems>
                        </Menu>
                    </div>
                </div>

                <!-- Contact Information -->
                <div
                    class="flex flex-wrap justify-center lg:justify-start items-center mt-2 gap-4 text-sm font-medium text-gray-500"
                >
                    <div class="flex items-center gap-1">
                        <BriefcaseIcon class="size-5 flex-shrink-0 text-gray-400" />
                        <p>{{ franchisee.franchisee_code }}</p>
                    </div>
                    <div class="flex items-center gap-1">
                        <MailIcon class="size-5 flex-shrink-0 text-gray-400" />
                        <p>{{ franchisee.email }}</p>
                    </div>
                    <div class="flex items-center gap-1">
                        <PhoneIcon class="size-5 flex-shrink-0 text-gray-400" />
                        <p>{{ franchisee.contact_number }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <ConfirmationModal
        :action="confirmationModal.action"
        :action_label="confirmationModal.action_label"
        :header="confirmationModal.header"
        :icon="confirmationModal.icon"
        :message="confirmationModal.message"
        :open="confirmationModal.open"
        @close="confirmationModal.open = false"
    />
</template>
