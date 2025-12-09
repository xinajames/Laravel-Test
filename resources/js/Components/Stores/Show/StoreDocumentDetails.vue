<script setup>
import { router } from '@inertiajs/vue3';

import DocumentTextIcon from '@/Components/Icon/DocumentTextIcon.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';

const props = defineProps({
    store: Object,
});

function goToDocumentsTab() {
    router.visit(route('stores.show', { store: props.store.id, tab: 'Documents' }));
}
</script>

<template>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mt-6">
        <h1 class="font-sans text-xl font-semibold text-gray-900">Recent Documents</h1>
        <div
            v-if="store.recent_documents && store.recent_documents.length > 0"
            class="flex gap-2 sm:gap-4 flex-wrap"
        >
            <SecondaryButton
                class="!rounded-md !text-gray-700 !font-medium"
                @click="goToDocumentsTab"
            >
                All Documents
            </SecondaryButton>
        </div>
    </div>

    <div
        v-if="store.recent_documents && store.recent_documents.length > 0"
        class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4"
    >
        <div
            v-for="document in store.recent_documents"
            :key="document.id"
            class="bg-white p-4 rounded-xl border border-gray-200 flex flex-col"
        >
            <DocumentTextIcon class="h-6 w-6 text-gray-500" />
            <div class="mt-4 flex flex-col justify-between flex-grow">
                <a
                    :href="document.preview"
                    class="text-gray-700 font-medium text-sm sm:text-base hover:underline"
                    rel="noopener noreferrer"
                    target="_blank"
                >
                    {{ document.document_name }}
                </a>
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    <p>{{ document.formatted_created_at }}</p>
                    <span class="text-gray-500 hidden sm:inline">•</span>
                    <p>{{ document.formatted_file_size }}</p>
                </div>
            </div>
        </div>
    </div>

    <div
        v-else
        class="flex flex-col justify-center items-center bg-white py-6 border border-gray-200 rounded-xl mt-4"
    >
        <svg
            fill="none"
            height="82"
            viewBox="0 0 82 82"
            width="82"
            xmlns="http://www.w3.org/2000/svg"
        >
            <ellipse cx="41" cy="62" fill="#F3F4F6" rx="41" ry="15" />
            <path
                clip-rule="evenodd"
                d="M23.78 23.78C23.78 20.6099 26.3499 18.04 29.52 18.04H42.6812C44.2036 18.04 45.6636 18.6448 46.74 19.7212L56.5388 29.52C57.6153 30.5965 58.22 32.0565 58.22 33.5788V58.22C58.22 61.3902 55.6501 63.96 52.48 63.96H29.52C26.3499 63.96 23.78 61.3902 23.78 58.22V23.78ZM29.52 41C29.52 39.415 30.805 38.13 32.39 38.13H49.61C51.1951 38.13 52.48 39.415 52.48 41C52.48 42.5851 51.1951 43.87 49.61 43.87H32.39C30.805 43.87 29.52 42.5851 29.52 41ZM32.39 49.61C30.805 49.61 29.52 50.895 29.52 52.48C29.52 54.0651 30.805 55.35 32.39 55.35H49.61C51.1951 55.35 52.48 54.0651 52.48 52.48C52.48 50.895 51.1951 49.61 49.61 49.61H32.39Z"
                fill="#9CA3AF"
                fill-rule="evenodd"
                stroke="#9CA3AF"
                stroke-linejoin="round"
                stroke-width="2.5"
            />
        </svg>
        <div class="text-center cursor-pointer" @click="goToDocumentsTab">
            <h5 class="font-semibold text-base">No documents uploaded yet</h5>
            <p class="text-gray-600 text-sm">Start by uploading the required store documents.</p>
        </div>
    </div>
</template>
