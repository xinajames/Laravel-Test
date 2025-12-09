<script setup>
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { BellIcon, ChevronDownIcon } from '@heroicons/vue/24/outline';
import { router } from '@inertiajs/vue3';

const userNavigation = [
    { name: 'Your profile', href: '#' },
    { name: 'Sign out', action: 'logout' },
];

const handleAction = (action) => {
    if (action === 'logout') {
        router.post('/logout');
    }
};
</script>

<template>
    <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
        <div class="grid flex-1 grid-cols-1"></div>
        <div class="flex items-center gap-x-4 lg:gap-x-6">
            <button class="-m-2.5 p-2.5 text-gray-400 hover:text-gray-500" type="button">
                <span class="sr-only">View notifications</span>
                <BellIcon aria-hidden="true" class="size-6" />
            </button>
            <div aria-hidden="true" class="hidden lg:block lg:h-6 lg:w-px lg:bg-gray-900/10" />
            <Menu as="div" class="relative">
                <MenuButton class="-m-1.5 flex items-center p-1.5">
                    <span class="sr-only">Open user menu</span>
                    <img
                        alt=""
                        class="size-8 rounded-full bg-gray-50"
                        src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                    />
                    <span class="hidden lg:flex lg:items-center">
                        <span aria-hidden="true" class="ml-4 text-sm/6 font-semibold text-gray-900">
                            Tom Cook
                        </span>
                        <ChevronDownIcon aria-hidden="true" class="ml-2 size-5 text-gray-400" />
                    </span>
                </MenuButton>
                <Transition
                    enter-active-class="transition ease-out duration-100"
                    enter-from-class="transform opacity-0 scale-95"
                    enter-to-class="transform opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-75"
                    leave-from-class="transform opacity-100 scale-100"
                    leave-to-class="transform opacity-0 scale-95"
                >
                    <MenuItems
                        class="absolute right-0 z-10 mt-2.5 w-32 origin-top-right rounded-md bg-white py-2 shadow-lg ring-1 ring-gray-900/5 focus:outline-none"
                    >
                        <MenuItem
                            v-for="item in userNavigation"
                            :key="item.name"
                            v-slot="{ active }"
                        >
                            <a
                                v-if="!item.action"
                                :class="[
                                    active ? 'bg-gray-50 outline-none' : '',
                                    'block px-3 py-1 text-sm/6 text-gray-900',
                                ]"
                                :href="item.href"
                            >
                                {{ item.name }}
                            </a>
                            <button
                                v-else
                                :class="[
                                    active ? 'bg-gray-50 outline-none' : '',
                                    'block w-full text-left px-3 py-1 text-sm/6 text-gray-900',
                                ]"
                                @click="handleAction(item.action)"
                            >
                                {{ item.name }}
                            </button>
                        </MenuItem>
                    </MenuItems>
                </Transition>
            </Menu>
        </div>
    </div>
</template>
