<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import Alert from '@/Components/Common/Alert/Alert.vue';
import DragAndDropFileUpload from '@/Components/Common/File/DragAndDropFileUpload.vue';
import Card from '@/Components/Shared/Card.vue';

const uploadError = ref(null);
const isProcessing = ref(false);

const form = useForm({
    file: null,
});

function handleUpload(files) {
    const file = files[0] || files.value?.[0];
    if (!file) return;

    // Validate file type
    const validTypes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
        'application/vnd.ms-excel', // .xls
    ];

    if (!validTypes.includes(file.type)) {
        uploadError.value = 'Invalid file type. Please upload an Excel file (.xlsx or .xls).';
        return;
    }

    // Validate file size (10MB)
    if (file.size > 10 * 1024 * 1024) {
        uploadError.value = 'File size too large. Maximum size is 10MB.';
        return;
    }

    uploadError.value = null;
    form.file = file;
}

function handleRemoveFile() {
    form.file = null;
    uploadError.value = null;
}

function submitImport() {
    if (!form.file) {
        uploadError.value = 'Please select a file to import.';
        return;
    }

    isProcessing.value = true;

    form.post(route('admin.settings.import.sync'), {
        onSuccess: () => {
            form.reset();
            isProcessing.value = false;
        },
        onError: () => {
            isProcessing.value = false;
        },
    });
}
</script>

<template>
    <div class="p-4 sm:p-8 space-y-6">
        <div>
            <p class="text-xl font-semibold text-gray-900">Document Import</p>
            <p class="mt-1 text-sm text-gray-500">
                Import documents from external systems using Excel files. This tool allows you to
                bulk import document metadata and move files to the appropriate store directories.
            </p>
        </div>

        <Alert
            v-if="$page.props.flash.success"
            :show="true"
            type="success"
            :message="$page.props.flash.success"
        />

        <Alert
            v-if="$page.props.flash.error"
            :show="true"
            type="error"
            :message="$page.props.flash.error"
        />

        <Card padding="p-0">
            <template #header>
                <h3 class="text-lg font-medium text-gray-900">Upload Excel File</h3>
                <p class="text-sm text-gray-500">
                    Upload an Excel file containing document information to import.
                </p>
            </template>

            <template #content>
                <div class="space-y-6">
                    <!-- File Upload Section -->
                    <div>
                        <div v-if="!form.file">
                            <DragAndDropFileUpload
                                :required="true"
                                custom-class="p-8"
                                file-types=".xlsx,.xls"
                                help-text="Excel files (.xlsx, .xls) • up to 10MB"
                                icon-class="text-gray-400"
                                icon-type="document"
                                label="Choose Excel file"
                                label-class="text-primary"
                                @uploaded="handleUpload"
                            />
                        </div>

                        <div v-else class="border-2 border-dashed border-gray-300 rounded-lg p-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        <svg
                                            class="h-8 w-8 text-green-500"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                            ></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ form.file.name }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            {{ (form.file.size / 1024 / 1024).toFixed(2) }} MB
                                        </p>
                                    </div>
                                </div>
                                <SecondaryButton @click="handleRemoveFile">Remove</SecondaryButton>
                            </div>
                        </div>

                        <p v-if="uploadError" class="mt-2 text-sm text-red-600">
                            {{ uploadError }}
                        </p>
                    </div>

                    <!-- Important Notes -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <p class="text-sm font-medium text-yellow-800 mb-2">Important Notes</p>
                        <ul class="text-sm text-yellow-700 space-y-1 list-disc list-inside">
                            <li>
                                Expected Excel data columns in the following exact order: DocID,
                                OwnerCode, UploadDate, Description, FileName, FilePath, CreatedDate
                            </li>
                            <li>
                                Documents with the same filename will be overwritten for each store
                            </li>
                            <li>
                                Files will be moved from the specified path but not deleted from the
                                original location
                            </li>
                            <li>
                                Only documents for existing stores (matching OwnerCode to Store
                                Code) will be imported
                            </li>
                            <li>
                                Each row is processed independently - errors in one row won't affect
                                other rows
                            </li>
                            <li>
                                The import process runs in the background - you'll receive a
                                notification when complete
                            </li>
                        </ul>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <PrimaryButton
                            :disabled="!form.file || isProcessing || form.processing"
                            @click="submitImport"
                        >
                            <span v-if="isProcessing || form.processing">Processing...</span>
                            <span v-else>Start Import</span>
                        </PrimaryButton>
                    </div>
                </div>
            </template>
        </Card>
    </div>
</template>
