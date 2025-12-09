<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowRightStartOnRectangleIcon,
    BanknotesIcon,
    BuildingOfficeIcon,
    ChartBarSquareIcon,
    ChevronUpDownIcon,
    ClockIcon,
    Cog8ToothIcon,
    DocumentTextIcon,
    HomeIcon,
    InboxIcon,
    UserGroupIcon,
    UserIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';

const userPermissions = computed(() => usePage().props.auth.permissions);
const userRole = computed(() => usePage().props.auth.user?.admin_type);

const navigationItems = [
    { name: 'Dashboard', href: 'dashboard', icon: HomeIcon },
    { name: 'Inbox', href: 'inbox', icon: InboxIcon },
    {
        name: 'Franchisee',
        href: 'franchisees',
        icon: UsersIcon,
        permissions: ['read-franchisees', 'update-franchisees'],
    },
    {
        name: 'Stores',
        href: 'stores',
        icon: BuildingOfficeIcon,
        permissions: ['read-stores', 'update-stores'],
    },
    { name: 'Documents', href: 'documents', icon: DocumentTextIcon },
    { name: 'Team', href: 'teams', icon: UserGroupIcon, permissions: ['read-team', 'update-team'] },
    {
        name: 'Royalty',
        href: 'royalty',
        icon: BanknotesIcon,
        permissions: ['read-royalty', 'update-royalty'],
    },
    {
        name: 'Reports',
        href: 'reports',
        icon: ChartBarSquareIcon,
        permissions: ['read-reports', 'update-reports'],
    },
    { name: 'Settings', href: 'settings', icon: Cog8ToothIcon },
    { name: 'Activity', href: 'activities', icon: ClockIcon, superAdminOnly: true },
];

// Filter navigation based on user permissions and roles
const navigation = computed(() => {
    return navigationItems.filter((item) => {
        // Check if item is restricted to Super Admin only
        if (item.superAdminOnly) {
            return userRole.value === 'Super Admin';
        }

        // Check permissions for other items
        if (!item.permissions) return true; // No permissions required
        return !item.permissions.every((permission) => !userPermissions.value.includes(permission));
    });
});

const canViewRolesPermissions = computed(() => {
    return (
        !usePage().props.auth.permissions.includes('read-franchisees') &&
        !usePage().props.auth.permissions.includes('update-franchisees')
    );
});

const email = computed(() => usePage().props.auth.user.email);
const name = computed(() => usePage().props.auth.user.name);
const profile_photo_url = computed(() => usePage().props.auth.user.profile_photo_url);
const unreadNotificationsCount = computed(() => usePage().props.auth.unreadNotificationsCount);

const handleAction = (action) => {
    router.post('/logout');
};

const isActive = (href) => route().current().startsWith(href);
</script>

<template>
    <div class="relative flex grow flex-col gap-y-5 bg-white border-r border-gray-200">
        <div class="flex h-10 shrink-0 items-center px-6 mt-6">
            <img alt="Julie's Logo" class="h-9" src="/img/julies_logo.png" />
        </div>

        <nav class="flex flex-1 flex-col px-6">
            <ul class="flex flex-1 flex-col gap-y-7" role="list">
                <li>
                    <ul class="-mx-2 space-y-1" role="list">
                        <li v-for="item in navigation" :key="item.name">
                            <Link
                                :aria-current="isActive(item.href) ? 'page' : undefined"
                                :class="[
                                    isActive(item.href)
                                        ? 'bg-primary text-white'
                                        : 'text-gray-600 hover:bg-gray-100',
                                    'group flex items-center justify-between rounded-md p-2 text-sm font-medium',
                                ]"
                                :href="route(item.href)"
                            >
                                <div class="flex items-center gap-x-3">
                                    <component
                                        :is="item.icon"
                                        :class="[
                                            isActive(item.href) ? 'text-white' : 'text-gray-400',
                                            'size-6 shrink-0',
                                        ]"
                                        aria-hidden="true"
                                    />
                                    <p>{{ item.name }}</p>
                                </div>
                                <div
                                    v-if="item.name === 'Inbox' && unreadNotificationsCount > 0"
                                    class="rounded-full px-3 py-px text-xs font-medium font-[Inter]"
                                    :class="
                                        isActive(item.href)
                                            ? 'bg-white text-gray-900'
                                            : 'bg-primary text-white'
                                    "
                                >
                                    {{ unreadNotificationsCount }}
                                </div>
                            </Link>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>
        <div>
            <img
                alt=""
                class="absolute bottom-12 object-cover pointer-events-none"
                src="/img/nav_bg.png"
            />
            <Menu as="div" class="mt-auto">
                <div>
                    <MenuButton
                        class="border-t border-gray-200 flex items-center justify-between w-full p-4 text-sm font-semibold leading-6 text-gray-700"
                    >
                        <div class="inline-flex items-center text-gray-700 gap-2">
                            <img
                                v-if="profile_photo_url"
                                :src="profile_photo_url"
                                class="h-8 w-8 rounded-full"
                            />
                            <div v-else class="bg-gray-500 w-8 h-8 rounded-full bg-auto" />
                            <span aria-hidden="true">{{ name }}</span>
                        </div>
                        <ChevronUpDownIcon class="text-gray-400 w-8 h-8" />
                    </MenuButton>
                </div>
                <transition
                    enter-active-class="transition ease-out duration-100"
                    enter-from-class="transform opacity-0 scale-95"
                    enter-to-class="transform opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-75"
                    leave-from-class="transform opacity-100 scale-100"
                    leave-to-class="transform opacity-0 scale-95"
                >
                    <MenuItems
                        class="absolute bottom-14 left-2 z-30 mt-2 w-60 origin-top-right rounded-lg bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                    >
                        <MenuItem v-slot="{ active }">
                            <div class="space-y-2 text-gray-700">
                                <div class="border-b px-4 py-2">
                                    <p class="text-base text-gray-700 font-semibold">
                                        {{ name }}
                                    </p>
                                    <p>{{ email }}</p>
                                </div>
                                <div class="space-y-2">
                                    <button
                                        class="flex items-center gap-2 cursor-pointer py-1 px-4 text-sm pb-2"
                                        @click="handleAction(action)"
                                    >
                                        <ArrowRightStartOnRectangleIcon
                                            class="w-5 h-5 !stroke-1 !text-black"
                                        />
                                        Logout
                                    </button>
                                </div>
                            </div>
                        </MenuItem>
                    </MenuItems>
                </transition>
            </Menu>
        </div>
    </div>
</template>
