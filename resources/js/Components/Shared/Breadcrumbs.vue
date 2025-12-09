<script setup>
import { Link } from '@inertiajs/vue3';
import HomeIcon from '@/Components/Icon/HomeIcon.vue';
import BreadCrumbSeparatorIcon from '@/Components/Icon/BreadcrumbSeparatorIcon.vue';

const props = defineProps({
    levels: Number,
    level1: { name: String, route: String, route_id: String },
    level2: { name: String, route: String, route_id: String },
    level3: {
        name: String,
        route: String,
        route_id: { type: String, default: null },
    },
    level4: { name: String, route: String, route_id: String },
});

const truncate = (name) => {
    return name.length < 20 ? name : name.substring(0, 20) + '...';
};
</script>

<template>
    <nav aria-label="Breadcrumb" class="flex">
        <ol class="flex items-center space-x-4">
            <!-- Home -->
            <li>
                <div class="flex items-center space-x-4">
                    <Link :href="route('dashboard')" class="cursor-pointer">
                        <home-icon class="w-5 h-5 text-primary" />
                    </Link>
                    <bread-crumb-separator-icon class="text-gray-200" />
                    <Link
                        :href="route(level1.route, level1.route_id)"
                        class="text-sm font-medium font-heading text-gray-500 hover:text-gray-700"
                    >
                        {{ truncate(level1.name) }}
                    </Link>
                </div>
            </li>

            <!-- Level 2 -->
            <li v-if="levels >= 2">
                <div class="flex items-center space-x-4">
                    <bread-crumb-separator-icon class="text-gray-200" />
                    <Link
                        :href="route(level2.route, level2.route_id)"
                        class="text-sm font-medium font-heading text-gray-500 hover:text-gray-700"
                    >
                        {{ truncate(level2.name) }}
                    </Link>
                </div>
            </li>

            <!-- Level 3 -->
            <li v-if="levels >= 3">
                <div class="flex items-center space-x-4">
                    <bread-crumb-separator-icon class="text-gray-200" />
                    <Link
                        :href="
                            level3.route_id
                                ? route(level3.route, [level3.route_id])
                                : route(level3.route)
                        "
                        class="text-sm font-medium font-heading text-gray-500 hover:text-gray-700"
                    >
                        {{ truncate(level3.name) }}
                    </Link>
                </div>
            </li>

            <!-- Level 4 -->
            <li v-if="levels === 4">
                <div class="flex items-center space-x-2">
                    <bread-crumb-separator-icon class="text-gray-200" />
                    <Link
                        :href="route(level4.route, level4.route_id)"
                        class="text-sm font-medium font-heading text-gray-500 hover:text-gray-700"
                    >
                        {{ truncate(level4.name) }}
                    </Link>
                </div>
            </li>
        </ol>
    </nav>
</template>
