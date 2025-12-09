<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import Alert from '@/Components/Common/Alert/Alert.vue';
import DragAndDropFileUpload from '@/Components/Common/File/DragAndDropFileUpload.vue';
import Card from '@/Components/Shared/Card.vue';
import axios from 'axios';

const uploadError = ref(null);
const successMessage = ref(null);
const isProcessing = ref(false);

const form = useForm({
    franchisee_branch_master_file: null,
    import_type: 'store_data', // Default to existing functionality
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
    successMessage.value = null;
    form.franchisee_branch_master_file = file;
}

function handleRemoveFile() {
    form.franchisee_branch_master_file = null;
    uploadError.value = null;
    successMessage.value = null;
}

function submitImport() {
    if (!form.franchisee_branch_master_file) {
        uploadError.value = 'Please select a file to import.';
        return;
    }

    isProcessing.value = true;
    uploadError.value = null;
    successMessage.value = null;

    // Use axios for this specific endpoint since it returns JSON
    const formData = new FormData();
    formData.append('franchisee_branch_master_file', form.franchisee_branch_master_file);
    formData.append('import_type', form.import_type);

    axios
        .post(route('settings.import'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        })
        .then((res) => {
            isProcessing.value = false;
            form.reset();
            uploadError.value = null;
            successMessage.value = res.data.message;
        })
        .catch((error) => {
            isProcessing.value = false;
            const errorMessage = error.response?.data?.message || 'An error occurred during upload';
            uploadError.value = errorMessage;
        });
}
</script>

<template>
    <div class="p-4 sm:p-8 space-y-6">
        <div>
            <p class="text-xl font-semibold text-gray-900">Data Import</p>
            <p class="mt-1 text-sm text-gray-500">
                Import franchisee and store data from Excel files. This tool allows you to bulk
                import franchisee profiles and store information from your master files.
            </p>
        </div>

        <Alert
            v-if="$page.props.flash.success"
            :show="true"
            type="success"
            :message="$page.props.flash.success"
        />

        <Alert v-if="successMessage" :show="true" type="success" :message="successMessage" />

        <Alert
            v-if="$page.props.flash.error"
            :show="true"
            type="error"
            :message="$page.props.flash.error"
        />

        <Card padding="p-0">
            <template #header>
                <h3 class="text-lg font-medium text-gray-900">
                    {{ form.import_type === 'store_data' ? 'Upload Master File' : 'Upload JBMIS History File' }}
                </h3>
                <p class="text-sm text-gray-500">
                    {{ 
                        form.import_type === 'store_data' 
                            ? 'Upload an Excel file containing franchisee and store data to import.' 
                            : 'Upload an Excel file containing JBMIS code history data to import.'
                    }}
                </p>
            </template>

            <template #content>
                <div class="space-y-6">
                    <!-- Import Type Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Import Type
                        </label>
                        <select
                            v-model="form.import_type"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                        >
                            <option value="store_data">Store Data</option>
                            <option value="jbmis_history">JBMIS Code History</option>
                        </select>
                    </div>

                    <!-- File Upload Section -->
                    <div>
                        <div v-if="!form.franchisee_branch_master_file">
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
                                            {{ form.franchisee_branch_master_file.name }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            {{
                                                (
                                                    form.franchisee_branch_master_file.size /
                                                    1024 /
                                                    1024
                                                ).toFixed(2)
                                            }}
                                            MB
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
                        <ul v-if="form.import_type === 'store_data'" class="text-sm text-yellow-700 space-y-1 list-disc list-inside">
                            <li>
                                Expected Excel file with two sheets: "Updated Franchisee Profile"
                                and "Updated Store Profile"
                            </li>
                            <li>
                                <strong>Franchisee Logic:</strong>
                                Existing franchisees (same franchisee_code) will be overwritten with
                                new data. New franchisee_codes will create new records.
                            </li>
                            <li>
                                <strong>Store Logic:</strong>
                                Existing stores (same store_code under same franchisee) will be
                                overwritten with new data. Different store_codes under same
                                franchisee will create new records.
                            </li>
                            <li>
                                Only records with valid franchisee codes and store codes will be
                                imported
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
                        <ul v-else-if="form.import_type === 'jbmis_history'" class="text-sm text-yellow-700 space-y-1 list-disc list-inside">
                            <li>
                                Expected Excel file with columns: Cluster Code, Sales Point Code, JBMIS Code, Effectivity Date
                            </li>
                            <li>
                                <strong>Processing Logic:</strong>
                                Creates store history entries for JBMIS code changes based on cluster and sales point codes
                            </li>
                            <li>
                                If multiple stores match the same cluster/sales point combination, priority is given to: Branch > Express > Junior > Outlet
                            </li>
                            <li>
                                Required fields: cluster_code, sales_point_code, jbmis_code (effectivity_date is optional)
                            </li>
                            <li>
                                If effectivity_date is empty, current date will be used
                            </li>
                            <li>
                                Rows with JBMIS codes that match the store's current data will be skipped
                            </li>
                            <li>
                                Each row is processed independently - errors in one row won't affect other rows
                            </li>
                            <li>
                                The import process runs in the background - you'll receive a notification when complete
                            </li>
                        </ul>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <PrimaryButton
                            :disabled="!form.franchisee_branch_master_file || isProcessing"
                            @click="submitImport"
                        >
                            <span v-if="isProcessing">Processing...</span>
                            <span v-else>Start Import</span>
                        </PrimaryButton>
                    </div>
                </div>
            </template>
        </Card>
    </div>
</template>
