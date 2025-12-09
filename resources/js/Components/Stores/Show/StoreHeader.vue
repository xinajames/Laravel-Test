<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import Avatar from '@/Components/Common/Avatar/Avatar.vue';
import BanIcon from '@/Components/Icon/BanIcon.vue';
import ConfirmationModal from '@/Components/Modal/ConfirmationModal.vue';
import DotsVertical from '@/Components/Icon/DotsVertical.vue';
import LocationMarker from '@/Components/Icon/LocationMarker.vue';
import MapIcon from '@/Components/Icon/MapIcon.vue';
import PencilIcon from '@/Components/Icon/PencilIcon.vue';
import RefreshIcon from '@/Components/Icon/RefreshIcon.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import TrashIcon from '@/Components/Icon/TrashIcon.vue';
import VueEasyLightbox from 'vue-easy-lightbox';
import UploadIcon from '@/Components/Icon/UploadIcon.vue';
import UploadStorePhotoModal from '@/Components/Modal/UploadStorePhotoModal.vue';

const props = defineProps({
    store: Object,
});

function handleEdit() {
    router.visit(route('stores.edit', props.store.id));
}

const layoutType = computed(() => {
    const photos = props.store.store_photos || [];
    if (photos.length === 1) return 'single';
    if (photos.length === 2) return 'double';
    return 'triple';
});

// Lightbox State
const showLightbox = ref(false);
const lightboxIndex = ref(0);
const lightboxImages = computed(
    () =>
        props.store.store_photos?.map((photo) => ({
            src: photo.preview,
            title: photo.description || '',
        })) || []
);

const openUploadPhotoModal = ref(false);

const openLightbox = (index) => {
    lightboxIndex.value = index;
    showLightbox.value = true;
};

// Computed property to get the caption of the current image
const currentCaption = computed(() => {
    return props.store.store_photos[lightboxIndex.value]?.caption || '';
});

const confirmationModal = reactive({
    open: false,
    header: 'Franchisee Application In Progress',
    message:
        'You have an existing franchisee application in progress. Would you like to continue where you left off or start a new one? Starting over will permanently erase your previously entered information.',
    icon: 'document',
    action_label: 'Start Over',
    action: null,
});

function handleMap(link) {
    link = link.trim();

    const domainRegex = /^((https?:\/\/)?[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})(\/\S*)?$/;

    if (!domainRegex.test(link)) {
        return;
    }

    let hasProtocol = /^(https?:\/\/)/i.test(link);
    let safeUrl = hasProtocol ? link : `https://${link}`;

    // Ensure only "http" and "https" protocols are used
    if (safeUrl.startsWith('javascript:') || safeUrl.startsWith('data:')) {
        return;
    }

    window.open(safeUrl, '_blank');
}

function handleAction(type, id) {
    if (type === 'delete') {
        confirmationModal.header = 'Delete Store';
        confirmationModal.message =
            'Are you sure you want to delete this store? This action cannot be undone.';
        confirmationModal.icon = 'delete';
        confirmationModal.action_label = 'Delete';
        confirmationModal.action = route('stores.delete', id);
    } else if (type === 'deactivate') {
        confirmationModal.header = 'Deactivate Store';
        confirmationModal.message = 'Are you sure you want to deactivate this store?';
        confirmationModal.icon = 'deactivate';
        confirmationModal.action_label = 'Deactivate';
        confirmationModal.action = route('stores.deactivate', id);
    } else if (type === 'reactivate') {
        confirmationModal.header = 'Reactivate Store';
        confirmationModal.message = 'Are you sure you want to reactivate this store?';
        confirmationModal.icon = 'reactivate';
        confirmationModal.action_label = 'Reactivate';
        confirmationModal.action = route('stores.activate', id);
    }
    confirmationModal.open = true;
}

const canUpdateStores = computed(() => {
    return usePage().props.auth.permissions.includes('update-stores');
});

function onSuccess() {
    router.visit(route('stores.show', props.store.id));
}
</script>

<template>
    <div class="w-full relative bg-white">
        <div class="flex flex-col lg:flex-row items-center lg:items-start lg:mx-12">
            <!-- Store Details -->
            <div class="flex flex-col gap-2 w-full text-center lg:text-left">
                <div
                    class="flex flex-col lg:flex-row justify-between items-center lg:items-start w-full"
                >
                    <div class="mt-4">
                        <div class="flex flex-col justify-center lg:justify-start gap-2">
                            <h3 class="font-bold text-gray-900">
                                {{ store.jbs_name }}
                            </h3>

                            <div class="flex gap-6">
                                <div class="flex gap-2 items-center">
                                    <Avatar
                                        :image-url="store.franchisee?.franchisee_profile_photo_url"
                                        custom-class="size-5"
                                        image-class="w-full h-full rounded-full object-cover"
                                    />
                                    <p class="text-gray-500 font-medium">
                                        {{ store.franchisee?.full_name }}
                                    </p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 text-gray-500">
                                    <location-marker />
                                    <p>{{ store.region || '—' }}</p>
                                    <span class="hidden md:inline">•</span>
                                    <p>{{ store.area || '—' }}</p>
                                    <span class="hidden md:inline">•</span>
                                    <p class="w-full md:w-auto">{{ store.district || '—' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 mt-4 lg:mt-4">
                        <SecondaryButton
                            v-if="store.google_maps_link"
                            class="!text-gray-700"
                            @click="handleMap(store.google_maps_link)"
                        >
                            <MapIcon class="!text-gray-500" />
                            Map
                        </SecondaryButton>
                        <SecondaryButton
                            v-if="canUpdateStores"
                            class="!text-gray-700"
                            @click="handleEdit()"
                        >
                            <PencilIcon class="size-5" />
                            Edit
                        </SecondaryButton>

                        <PrimaryButton @click="openUploadPhotoModal = true">
                            <UploadIcon />
                            Upload Photo
                        </PrimaryButton>

                        <Menu
                            v-if="canUpdateStores"
                            as="div"
                            class="relative inline-block text-left"
                        >
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
                                        @click="handleAction('delete', store.id)"
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
                                                store.is_active === 1 ? 'deactivate' : 'reactivate',
                                                store.id
                                            )
                                        "
                                    >
                                        <BanIcon
                                            v-if="store.is_active === 1"
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
                                        {{ store.is_active === 1 ? 'Deactivate' : 'Reactivate' }}
                                    </button>
                                </MenuItem>
                            </MenuItems>
                        </Menu>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="store.store_photos && store.store_photos.length > 0" class="mt-6 lg:mx-12">
            <!-- Single Image Layout -->
            <div v-if="layoutType === 'single'">
                <img
                    :alt="store.store_photos[0].description"
                    :src="store.store_photos[0].preview"
                    class="w-full h-[340px] object-cover rounded-lg cursor-pointer"
                    @click="openLightbox(0)"
                />
            </div>

            <!-- Two Images Layout -->
            <div v-else-if="layoutType === 'double'" class="flex gap-4">
                <img
                    :alt="store.store_photos[0].description"
                    :src="store.store_photos[0].preview"
                    class="w-1/2 h-[340px] object-cover rounded-lg cursor-pointer"
                    @click="openLightbox(0)"
                />
                <img
                    :alt="store.store_photos[1].description"
                    :src="store.store_photos[1].preview"
                    class="w-1/2 h-[340px] object-cover rounded-lg cursor-pointer"
                    @click="openLightbox(1)"
                />
            </div>

            <!-- Three or More Images Layout -->
            <div v-else class="flex gap-4">
                <!-- Main Image -->
                <div class="basis-3/4">
                    <img
                        :alt="store.store_photos[0].description"
                        :src="store.store_photos[0].preview"
                        class="w-full h-[340px] object-cover rounded-lg"
                    />
                </div>
                <!-- Side Images -->
                <div class="basis-1/4 flex flex-col gap-4">
                    <img
                        v-if="store.store_photos[1]"
                        :alt="store.store_photos[1].description"
                        :src="store.store_photos[1].preview"
                        class="w-full h-[162px] object-cover rounded-lg"
                    />
                    <div class="relative" v-if="store.store_photos[2]">
                        <img
                            :src="store.store_photos[2].preview"
                            :alt="store.store_photos[2].description"
                            class="w-full h-[162px] object-cover rounded-lg"
                        />
                        <SecondaryButton
                            class="absolute bottom-2 right-2 bg-white text-gray-700 text-xs font-medium px-3 py-1 rounded-md shadow-md hover:bg-gray-200"
                            @click="openLightbox(0)"
                        >
                            See all photos
                        </SecondaryButton>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lightbox Component -->
    <VueEasyLightbox
        :visible="showLightbox"
        :imgs="lightboxImages"
        :index="lightboxIndex"
        @hide="showLightbox = false"
        @on-prev="lightboxIndex = Math.max(lightboxIndex - 1, 0)"
        @on-next="lightboxIndex = Math.min(lightboxIndex + 1, store.store_photos.length - 1)"
    />

    <ConfirmationModal
        :action="confirmationModal.action"
        :action_label="confirmationModal.action_label"
        :header="confirmationModal.header"
        :icon="confirmationModal.icon"
        :message="confirmationModal.message"
        :open="confirmationModal.open"
        @close="confirmationModal.open = false"
        @success="onSuccess"
    />

    <UploadStorePhotoModal
        :open="openUploadPhotoModal"
        :storeId="store.id"
        :storePhotos="store.store_photos"
        @close="openUploadPhotoModal = false"
        @success="onSuccess"
    />
</template>
