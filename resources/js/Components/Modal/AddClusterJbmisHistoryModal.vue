<script setup>
import { ref, reactive, watch } from 'vue';
import axios from 'axios';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import TextInput from '@/Components/Common/Input/TextInput.vue';
import Modal from '@/Components/Shared/Modal.vue';

const emits = defineEmits(['close', 'success']);
const props = defineProps({
    open: Boolean,
    storeId: Number,
});

const isSubmitting = ref(false);

const formData = reactive({
    cluster_code: '',
    jbmis_code: '',
    effective_at: '',
});

const errors = ref({});

const resetForm = () => {
    formData.cluster_code = '';
    formData.jbmis_code = '';
    formData.effective_at = '';
    errors.value = {};
};

const handleSubmit = async () => {
    if (!props.storeId) {
        console.error('Store ID is missing');
        return;
    }

    errors.value = {};
    isSubmitting.value = true;

    try {
        const payload = {
            cluster_code: formData.cluster_code,
            jbmis_code: formData.jbmis_code,
            effective_at: formData.effective_at,
        };

        const response = await axios.post(
            route('stores.addCoordinatedHistory', { store: props.storeId }), 
            payload
        );
        
        if (response.data.success) {
            resetForm();
            emits('success');
            emits('close');
        }
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            console.error('Error adding coordinated history entries:', error);
            // Show generic error
            errors.value = { general: ['An error occurred while adding the history entries.'] };
        }
    } finally {
        isSubmitting.value = false;
    }
};

const handleClose = () => {
    resetForm();
    emits('close');
};

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            resetForm();
        }
    }
);
</script>

<template>
    <Modal :open="open" max-width="2xl" @close="handleClose">
        <template #content>
            <div class="p-6">
                    <div class="mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Add Coordinated History Entry
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Add historical cluster code and JBMIS code values that were effective at a specific date.
                        </p>
                    </div>

                    <!-- Info Notice -->
                    <div class="mb-4 bg-blue-50 border border-blue-200 rounded-md p-3">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800">
                                    Historical Data Entry
                                </h3>
                                <div class="mt-2 text-sm text-blue-700">
                                    <p>This will add historical data for royalty processing without changing current store values. Both codes will be linked with the same effective date.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="handleSubmit" class="space-y-4">
                        <!-- Coordinated Code Inputs -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <TextInput
                                    v-model="formData.cluster_code"
                                    label="Historical Cluster Code"
                                    placeholder="Enter cluster code"
                                    :error="errors.cluster_code"
                                    input-class="border-gray-300"
                                    required
                                />
                            </div>
                            <div>
                                <TextInput
                                    v-model="formData.jbmis_code"
                                    label="Historical JBMIS Code"
                                    placeholder="Enter JBMIS code"
                                    :error="errors.jbmis_code"
                                    input-class="border-gray-300"
                                    required
                                />
                            </div>
                        </div>

                        <!-- Effective Date -->
                        <div>
                            <label for="effective_at" class="block text-sm font-medium text-gray-700">
                                Effectivity Date <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="effective_at"
                                v-model="formData.effective_at"
                                type="date"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                :class="{ 'border-red-300': errors.effective_at }"
                                required
                            />
                            <p v-if="errors.effective_at" class="mt-1 text-sm text-red-600">
                                {{ errors.effective_at[0] }}
                            </p>
                        </div>

                        <!-- General Error Display -->
                        <div v-if="errors.general" class="bg-red-50 border border-red-200 rounded-md p-3">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">
                                        Error
                                    </h3>
                                    <div class="mt-2 text-sm text-red-700">
                                        <p>{{ errors.general[0] }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex justify-end space-x-3 pt-4 border-t">
                            <SecondaryButton
                                type="button"
                                @click="handleClose"
                                :disabled="isSubmitting"
                            >
                                Cancel
                            </SecondaryButton>
                            <SecondaryButton
                                type="button"
                                @click="resetForm"
                                :disabled="isSubmitting"
                            >
                                Reset
                            </SecondaryButton>
                            <PrimaryButton
                                type="submit"
                                :disabled="isSubmitting"
                                class="min-w-[120px]"
                            >
                                <span v-if="isSubmitting">Adding...</span>
                                <span v-else>Add History Entry</span>
                            </PrimaryButton>
                        </div>
                    </form>
            </div>
        </template>
    </Modal>
</template>