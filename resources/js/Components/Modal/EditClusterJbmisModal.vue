<script setup>
import { ref, reactive, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import TextInput from '@/Components/Common/Input/TextInput.vue';
import Modal from '@/Components/Shared/Modal.vue';

const emits = defineEmits(['close', 'success']);
const props = defineProps({
    open: Boolean,
    store: Object,
});

const isSubmitting = ref(false);

const formData = reactive({
    cluster_code: '',
    jbmis_code: '',
    application_step: 'basic-details',
});

const errors = ref({});

const hasChanges = computed(() => {
    return formData.cluster_code !== props.store?.cluster_code || 
           formData.jbmis_code !== props.store?.jbmis_code;
});

const bothOriginalValuesExist = computed(() => {
    return props.store?.cluster_code && props.store?.jbmis_code;
});

const clusterCodeRequired = computed(() => {
    // Required if both original values exist and JBMIS code is changing
    if (bothOriginalValuesExist.value) {
        return formData.jbmis_code !== props.store?.jbmis_code;
    }
    return false;
});

const jbmisCodeRequired = computed(() => {
    // Required if both original values exist and cluster code is changing
    if (bothOriginalValuesExist.value) {
        return formData.cluster_code !== props.store?.cluster_code;
    }
    return false;
});

const resetForm = () => {
    formData.cluster_code = props.store?.cluster_code || '';
    formData.jbmis_code = props.store?.jbmis_code || '';
    formData.application_step = 'basic-details';
    errors.value = {};
};

const handleSubmit = async () => {
    if (!hasChanges.value) {
        emits('close');
        return;
    }

    isSubmitting.value = true;
    errors.value = {};

    router.post(route('stores.update', props.store.id), formData, {
        preserveScroll: true,
        onSuccess: () => {
            emits('success');
            emits('close');
        },
        onError: (serverErrors) => {
            errors.value = serverErrors;
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
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
                            Edit Cluster & JBMIS Codes
                        </h3>
                        <p class="mt-1 text-sm text-gray-500" v-if="bothOriginalValuesExist">
                            Both cluster code and JBMIS code must be updated together as they are linked for royalty processing.
                        </p>
                        <p class="mt-1 text-sm text-gray-500" v-else>
                            You can update individual codes since one or both are not currently set. Once both codes are filled, they must be updated together.
                        </p>
                    </div>

                    <!-- Warning for data implications -->
                    <div class="mb-4 bg-yellow-50 border border-yellow-200 rounded-md p-3">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">
                                    Important Notice
                                </h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <p v-if="bothOriginalValuesExist">Changing these codes will affect royalty calculations. Make sure both codes are correct before saving.</p>
                                    <p v-else>Setting these codes will establish their values for royalty calculations. Once both are set, they must be updated together.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="handleSubmit" class="space-y-4">
                        <!-- Current Values Display -->
                        <div class="bg-gray-50 p-3 rounded-md">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Current Values:</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600">Cluster Code:</span>
                                    <span class="ml-2 font-medium">{{ store?.cluster_code || 'Not set' }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">JBMIS Code:</span>
                                    <span class="ml-2 font-medium">{{ store?.jbmis_code || 'Not set' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- New Values Input -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <TextInput
                                    v-model="formData.cluster_code"
                                    label="New Cluster Code"
                                    placeholder="Enter cluster code"
                                    :error="errors.cluster_code"
                                    input-class="border-gray-300"
                                    :required="clusterCodeRequired"
                                />
                            </div>
                            <div>
                                <TextInput
                                    v-model="formData.jbmis_code"
                                    label="New JBMIS Code"
                                    placeholder="Enter JBMIS code"
                                    :error="errors.jbmis_code"
                                    input-class="border-gray-300"
                                    :required="jbmisCodeRequired"
                                />
                            </div>
                        </div>

                        <!-- Error Display -->
                        <div v-if="errors.cluster_code || errors.jbmis_code" class="bg-red-50 border border-red-200 rounded-md p-3">
                            <h4 class="text-sm font-medium text-red-800 mb-1">Validation Errors:</h4>
                            <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                                <li v-if="errors.cluster_code">{{ errors.cluster_code }}</li>
                                <li v-if="errors.jbmis_code">{{ errors.jbmis_code }}</li>
                            </ul>
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
                            <PrimaryButton
                                type="submit"
                                :disabled="isSubmitting || !hasChanges"
                                class="min-w-[100px]"
                            >
                                <span v-if="isSubmitting">Saving...</span>
                                <span v-else>Save Changes</span>
                            </PrimaryButton>
                        </div>
                    </form>
            </div>
        </template>
    </Modal>
</template>