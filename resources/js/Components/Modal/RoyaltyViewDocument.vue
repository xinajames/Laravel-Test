<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import Modal from '@/Components/Shared/Modal.vue';
import CalendarIcon from '@/Components/Icon/CalendarIcon.vue';
import UserIcon from '@/Components/Icon/UserIcon.vue';
import DocumentTextIcon from '@/Components/Icon/DocumentTextIcon.vue';
import DownloadIcon from '@/Components/Icon/DownloadIcon.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import ConfirmationModal from '@/Components/Modal/ConfirmationModal.vue';

const emits = defineEmits(['close', 'success']);

const props = defineProps({
    royalty: Object,
    open: Boolean,
});

const showConfirmation = ref(false);
const isInvalidating = ref(false);

function handleViewDocument(url) {
    if (url) {
        window.open(url, '_blank');
    }
}

const handleDownload = (url, filename = 'downloaded-file') => {
    if (!url) return;

    const encodedUrl = encodeURI(url);

    const link = document.createElement('a');
    link.href = encodedUrl;
    link.download = filename;
    link.rel = 'noopener';

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

// Parse errors from JSON string
const parsedErrors = computed(() => {
    if (!props.royalty?.errors) return [];

    try {
        const errors = JSON.parse(props.royalty.errors);
        return Array.isArray(errors) ? errors : [errors];
    } catch (e) {
        // Fallback for non-JSON error strings
        return [{ message: props.royalty.errors, severity: 'error' }];
    }
});

// Format timestamp to readable format
const formatTimestamp = (timestamp) => {
    if (!timestamp) return '';
    try {
        return new Date(timestamp).toLocaleString();
    } catch (e) {
        return timestamp;
    }
};

// Get severity styling
const getSeverityClass = (severity) => {
    switch (severity?.toLowerCase()) {
        case 'critical':
        case 'error':
            return 'bg-red-50 border-red-200 text-red-800';
        case 'warning':
            return 'bg-yellow-50 border-yellow-200 text-yellow-800';
        case 'info':
            return 'bg-blue-50 border-blue-200 text-blue-800';
        default:
            return 'bg-red-50 border-red-200 text-red-800';
    }
};

const getSeverityBadgeClass = (severity) => {
    switch (severity?.toLowerCase()) {
        case 'critical':
        case 'error':
            return 'bg-red-100 text-red-800';
        case 'warning':
            return 'bg-yellow-100 text-yellow-800';
        case 'info':
            return 'bg-blue-100 text-blue-800';
        default:
            return 'bg-red-100 text-red-800';
    }
};

// Check if royalty can be invalidated (successful or failed)
const canInvalidate = computed(() => {
    const status = props.royalty?.status;

    // Allow invalidation only for successful (3) or failed (4) royalties
    // Backend will handle checking if already soft deleted
    // Accept both string and number formats
    return status == 3 || status == 4;
});

function confirmInvalidation() {
    showConfirmation.value = true;
}

function cancelInvalidation() {
    showConfirmation.value = false;
}

function handleConfirmationSuccess() {
    emits('success');
    emits('close');
}

function handleConfirmationClose() {
    showConfirmation.value = false;
}

// Get the invalidation route URL
const invalidationRoute = computed(() => {
    return props.royalty?.id ? route('royalty.invalidate', props.royalty.id) : '';
});
</script>

<template>
    <Modal max-width="lg" :open="open" @close="emits('close')">
        <template v-slot:content>
            <div class="py-4 px-6 items-center bg-white">
                <h5 class="text-lg font-medium text-gray-900 mt-1">{{ royalty?.title }}</h5>
                <p class="text-gray-400 text-sm mt-2">{{ royalty?.remarks }}</p>
                <p v-if="royalty?.status == 3" class="flex gap-2 mt-4 text-gray-500">
                    <CalendarIcon class="text-gray-400" />
                    Generated on {{ royalty?.completed_date }}
                </p>
                <p class="flex gap-2 mt-2 text-gray-500">
                    <UserIcon type="solid" class="text-gray-400 bg-text-gray-400" />
                    By {{ royalty?.user_name }}
                </p>
                <div v-if="royalty?.status == 4 && parsedErrors.length > 0" class="mt-4 space-y-3">
                    <div
                        v-for="(error, index) in parsedErrors"
                        :key="index"
                        :class="['p-4 border rounded-lg', getSeverityClass(error.severity)]"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <!-- <span 
                                        :class="['px-2 py-1 text-xs font-medium rounded-full', getSeverityBadgeClass(error.severity)]"
                                    >
                                        {{ error.severity?.toUpperCase() || 'ERROR' }}
                                    </span> -->
                                    <span v-if="error.timestamp" class="text-xs text-gray-500">
                                        {{ formatTimestamp(error.timestamp) }}
                                    </span>
                                </div>
                                <p class="text-sm font-medium mb-1">{{ error.message }}</p>
                                <!-- <p v-if="error.context" class="text-xs text-gray-600">{{ error.context }}</p> -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-6 space-y-6 bg-[#F9FAFB]">
                <!-- Generated Files -->
                <div v-if="royalty?.macro_outputs">
                    <p class="text-gray-900 font-medium text-sm">Generated</p>
                    <div
                        v-for="(file, index) in royalty.macro_outputs"
                        :key="index"
                        class="bg-white border border-gray-200 rounded-2xl flex gap-4 items-center py-3 px-4 justify-between mt-2 cursor-pointer"
                        @click="openFile(file.view_link)"
                    >
                        <DocumentTextIcon />
                        <div class="flex-1">
                            <p class="text-gray-900 font-medium">{{ file.file_name }}</p>
                            <p class="text-gray-500">{{ file.size }} KB</p>
                        </div>
                        <DownloadIcon
                            class="text-gray-600 cursor-pointer"
                            @click.stop="
                                handleDownload(
                                    route('royalty.download.output', file.id),
                                    file.file_name
                                )
                            "
                        />
                    </div>
                </div>

                <!-- Raw Files -->
                <div>
                    <p class="text-gray-900 font-medium text-sm">Raw Files</p>
                    <div v-if="royalty?.macro_uploads && royalty.macro_uploads.length > 0">
                        <div
                            v-for="(file, index) in royalty.macro_uploads"
                            :key="index"
                            class="bg-white border border-gray-200 rounded-2xl flex gap-4 items-center py-3 px-4 justify-between mt-2 cursor-pointer"
                            @click="openFile(file.view_link)"
                        >
                            <DocumentTextIcon />
                            <div class="flex-1">
                                <p class="text-gray-900 font-medium">{{ file.file_name }}</p>
                                <p class="text-gray-500">{{ file.size }} KB</p>
                            </div>
                            <DownloadIcon
                                class="text-gray-600 cursor-pointer"
                                @click.stop="
                                    handleDownload(
                                        route('royalty.download.upload', file.id),
                                        file.file_name
                                    )
                                "
                            />
                        </div>
                    </div>
                    <p v-else class="text-gray-500 text-sm mt-2">No Raw files uploaded</p>
                </div>
            </div>

            <!-- Modal Footer with Invalidate Button -->
            <div v-if="canInvalidate" class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                <SecondaryButton @click="emits('close')">
                    Close
                </SecondaryButton>
                <PrimaryButton
                    variant="danger"
                    @click="confirmInvalidation"
                    :disabled="isInvalidating"
                >
                    {{ isInvalidating ? 'Invalidating...' : 'Invalidate Royalty' }}
                </PrimaryButton>
            </div>
        </template>
    </Modal>

    <!-- Confirmation Modal -->
    <ConfirmationModal
        :open="showConfirmation"
        header="Invalidate Royalty"
        message="Are you sure you want to invalidate this royalty? This action will mark the generation as failed and cannot be undone."
        icon="warning"
        action_label="Invalidate"
        cancel_label="Cancel"
        :action="invalidationRoute"
        @success="handleConfirmationSuccess"
        @close="handleConfirmationClose"
    />
</template>
