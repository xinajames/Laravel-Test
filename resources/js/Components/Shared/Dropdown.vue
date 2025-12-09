<script setup>
import { Menu, MenuButton, MenuItems } from '@headlessui/vue';
import { Float } from '@headlessui-float/vue';
</script>

<template>
    <Menu as="div" class="relative">
        <Float placement="bottom" portal>
            <MenuButton>
                <slot name="trigger" />
            </MenuButton>
            <transition
                enter-active-class="transition ease-out duration-100"
                enter-from-class="transform opacity-0 scale-95"
                enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95"
            >
                <MenuItems
                    class="absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                >
                    <div class="py-1">
                        <slot name="menu" />
                    </div>
                </MenuItems>
            </transition>
        </Float>
    </Menu>
</template>
