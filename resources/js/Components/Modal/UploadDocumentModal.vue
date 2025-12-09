<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

import Modal from '@/Components/Shared/Modal.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import DragAndDropFileUpload from '@/Components/Common/File/DragAndDropFileUpload.vue';
import XCloseIcon from '@/Components/Icon/XCloseIcon.vue';
import DocumentTextIcon from '@/Components/Icon/DocumentTextIcon.vue';

const emits = defineEmits(['close', 'success']);

const props = defineProps({
    open: Boolean,
    id: Number,
    model: String,
});

const disabled = ref(false);
const uploadError = ref(null);
const MAX_FILES = 10;
const VALID_TYPES = [
    'application/pdf',
    'application/msword', // .doc
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
    'image/jpeg', // .jpg, .jpeg
    'image/jpg', // .jpg
    'image/png', // .png
];

const form = useForm({
    documents: [],
    model_id: props.id,
    model: props.model,
});

function formatBytes(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function handleUpload(files) {
    const arr = files.value ?? files;

    const remaining = MAX_FILES - form.documents.length;
    if (remaining <= 0) {
        uploadError.value = `Maximum of ${MAX_FILES} documents allowed.`;
        return;
    }

    Array.from(arr)
        .slice(0, remaining)
        .forEach((file) => {
            if (!VALID_TYPES.includes(file.type)) {
                uploadError.value = 'Invalid file type. Please upload PDF, DOC, DOCX, JPG or PNG.';
                return;
            }

            if (file.size > 10 * 1024 * 1024) {
                uploadError.value = 'Each file must be 10 MB or smaller.';
                return;
            }

            form.documents.push(file);
        });
}

const removeDocument = (idx) => form.documents.splice(idx, 1);

function upload() {
    if (form.documents.length === 0) {
        uploadError.value = 'Please add at least one document.';
        return;
    }

    disabled.value = true;
    uploadError.value = null;

    form.post(route('documents.upload'), {
        forceFormData: true,
        onSuccess() {
            emits('success');
            emits('close');
        },
        onFinish() {
            disabled.value = false;
        },
    });
}

function cancel() {
    emits('close');
}

watch(
    () => props.open,
    (open) => {
        if (!open) {
            form.reset();
            form.clearErrors();
            uploadError.value = null;
        }
    }
);
</script>

<template>
    <Modal :open="open" max-width="lg" @close="emits('close')">
        <template #content>
            <div class="px-6 py-4">
                <p class="text-lg leading-6 font-medium">Upload Document</p>
            </div>

            <div class="border-t border-gray-200">
                <div class="p-6 space-y-6 max-h-[480px] overflow-y-auto">
                    <div>
                        <DragAndDropFileUpload
                            :required="false"
                            custom-class="p-6"
                            file-types=".doc,.docx,.pdf,.jpg,.jpeg,.png"
                            help-text="PDF, DOC, DOCX, JPG, PNG • up to 10 MB • max 10 files"
                            icon-class="text-gray-400"
                            icon-type="document"
                            label="Upload a file"
                            label-class="text-indigo-600"
                            @uploaded="handleUpload"
                        />
                        <p v-if="uploadError" class="text-red-500 text-sm mt-1">
                            {{ uploadError }}
                        </p>
                    </div>

                    <div v-if="form.documents.length" class="space-y-2">
                        <div
                            v-for="(file, i) in form.documents"
                            :key="i"
                            class="flex border border-gray-200 bg-gray-100 rounded-2xl px-4 py-3 gap-4 items-center"
                        >
                            <div
                                class="rounded-lg flex-shrink-0 flex items-center justify-center p-2"
                            >
                                <DocumentTextIcon class="h-6" />
                            </div>

                            <div class="flex flex-col flex-1 min-w-0">
                                <p class="text-sm font-medium truncate text-gray-900">
                                    {{ file.name.replace(/\.[^/.]+$/, '') }}
                                </p>
                                <p class="text-sm text-gray-500 truncate">
                                    {{ file.type.split('/').pop().toUpperCase() }} •
                                    {{ formatBytes(file.size) }}
                                </p>
                            </div>

                            <button class="flex-shrink-0" type="button" @click="removeDocument(i)">
                                <XCloseIcon class="size-5 text-gray-500 hover:text-gray-700" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 py-4 px-6 border-t border-gray-200">
                    <SecondaryButton class="!text-gray-700" @click="emits('close')">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton :disabled="disabled" @click="upload">Upload</PrimaryButton>
                </div>
            </div>
        </template>
    </Modal>
</template>
