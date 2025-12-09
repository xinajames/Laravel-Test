<script setup>
import { ref, watch } from 'vue';
import Modal from '@/Components/Shared/Modal.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import DragAndDropFileUpload from '@/Components/Common/File/DragAndDropFileUpload.vue';
import XCloseIcon from '@/Components/Icon/XCloseIcon.vue';

const emits = defineEmits(['close', 'success']);

const props = defineProps({
    open: Boolean,
    storeId: Number,
    storePhotos: {
        type: Array,
        default: () => [],
    },
});

const disabled = ref(false);
const uploadError = ref(null);

const form = useForm({
    photos: props.storePhotos.map((photo) => ({
        id: photo.id,
        preview: photo.preview,
        description: photo.description,
        file: null,
    })),
    delete_photo_id: [],
});

function upload() {
    disabled.value = true;
    form.post(route('stores.update', props.storeId), {
        onSuccess: () => {
            emits('success');
            emits('close');
        },
        onFinish: () => {
            disabled.value = false;
        },
    });
}

function handleUpload(files) {
    // Clear previous error
    uploadError.value = null;

    // Handle both array of files (multiple) and single file
    const fileArray = Array.isArray(files) ? files : [files];

    const currentCount = form.photos.length;
    const availableSlots = 10 - currentCount;
    if (availableSlots <= 0) {
        uploadError.value = 'Maximum of 10 photos allowed.';
        return;
    }

    // Filter only valid image files first
    const validFileTypes = ['image/jpg', 'image/jpeg', 'image/png'];
    const validFiles = fileArray.filter((file) => {
        if (!validFileTypes.includes(file.type)) {
            return false;
        }
        return true;
    });

    if (validFiles.length === 0) {
        uploadError.value = 'Invalid file type. Please upload only .png, .jpeg, or .jpg files.';
        return;
    }

    if (validFiles.length !== fileArray.length) {
        uploadError.value =
            'Some files were skipped. Please upload only .png, .jpeg, or .jpg files.';
    }

    const filesToAdd = validFiles.slice(0, availableSlots);

    if (filesToAdd.length < validFiles.length) {
        uploadError.value = `Only ${filesToAdd.length} photos were added due to the maximum limit of 10 photos.`;
    }

    filesToAdd.forEach((file) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            form.photos.push({
                file: file,
                description: '',
                id: null,
                preview: e.target.result,
            });
        };
        reader.readAsDataURL(file);
    });
}

function removePhoto(index) {
    const photo = form.photos[index];
    if (photo.id) {
        form.delete_photo_id.push(photo.id);
    }
    form.photos.splice(index, 1);
}

function cancel() {
    emits('close');
}

watch(
    () => props.open,
    (newVal) => {
        if (newVal) {
            // Reset form with current store photos when modal opens
            form.photos = props.storePhotos.map((photo) => ({
                id: photo.id,
                preview: photo.preview,
                description: photo.description,
                file: null,
            }));
            form.delete_photo_id = [];
            uploadError.value = null;
        } else {
            form.reset();
            uploadError.value = null;
        }
    }
);
</script>

<template>
    <Modal max-width="lg" :open="open" @close="cancel">
        <template v-slot:content>
            <div class="px-6 py-4">
                <h4 class="font-semibold font-sans">Upload Photos</h4>
            </div>
            <div class="border-t border-gray-200">
                <div class="p-6 space-y-6 max-h-[480px] overflow-y-auto">
                    <div>
                        <DragAndDropFileUpload
                            custom-class="p-6"
                            icon-class="text-gray-400"
                            label="Upload a file"
                            label-class="text-indigo-600"
                            help-text="PNG, JPG up to 10MB"
                            file-types=".jpg,.jpeg,.png"
                            @uploaded="handleUpload($event)"
                            :required="false"
                        />
                        <p v-if="uploadError" class="text-red-500 text-sm mt-1">
                            {{ uploadError }}
                        </p>
                    </div>

                    <!-- Uploaded Photos Section -->
                    <div v-if="form.photos.length > 0" class="space-y-2">
                        <div
                            v-for="(photo, index) in form.photos"
                            :key="index"
                            class="flex border border-gray-200 bg-gray-100 rounded-2xl p-3 gap-4 items-start"
                        >
                            <div class="rounded-lg w-20 h-20 bg-gray-300 flex-shrink-0">
                                <img
                                    v-if="photo.preview"
                                    :src="photo.preview"
                                    alt="Photo preview"
                                    class="w-full h-full object-cover rounded-lg"
                                />
                            </div>
                            <textarea
                                type="text"
                                v-model="photo.description"
                                placeholder="Add caption here..."
                                class="flex-1 p-0 self-stretch text-sm rounded-md bg-transparent border-none focus:outline-none focus:ring-0 placeholder:text-gray-400 resize-none"
                            />
                            <button
                                type="button"
                                @click="removePhoto(index)"
                                class="text-gray-500 hover:text-gray-700"
                            >
                                <XCloseIcon />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 py-4 px-6 border-t border-gray-200">
                    <SecondaryButton class="!text-gray-700 !font-medium" @click="cancel">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton class="!font-medium" :disabled="disabled" @click="upload">
                        Upload
                    </PrimaryButton>
                </div>
            </div>
        </template>
    </Modal>
</template>
