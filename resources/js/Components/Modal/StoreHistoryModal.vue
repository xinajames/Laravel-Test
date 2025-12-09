<script setup>
import { ref, watch, computed, reactive } from 'vue';
import axios from 'axios';
import HistoryDatatable from '@/Components/Shared/HistoryDatatable.vue';
import HistoryModal from '@/Components/Shared/HistoryModal.vue';
import AddClusterJbmisHistoryModal from '@/Components/Modal/AddClusterJbmisHistoryModal.vue';

const emits = defineEmits(['close']);
const props = defineProps({
    open: Boolean,
    storeId: Number,
    selectedType: String,
    customTitle: {
        type: String,
        required: false,
    },
    histories: {
        type: Array,
        required: false,
        default: () => [],
    },
});

const isLoading = ref(false);
const localHistories = ref([]);
const activeTab = ref('view');
const isSubmitting = ref(false);
const addCoordinatedHistoryModalOpen = ref(false);

const supportedFields = ['jbmis_code', 'cluster_code', 'cluster_jbmis_codes'];

const formData = reactive({
    value: '',
    effective_at: '',
});

const errors = ref({});

const modalTitle = computed(() => {
    if (activeTab.value === 'add') {
        if (isCoordinatedField.value) {
            return 'Add History Entry: Cluster & JBMIS Codes';
        }
        return `Add History Entry: ${props.selectedType ? formatFieldName(props.selectedType) : '[Data]'}`;
    }
    
    if (isCoordinatedField.value) {
        return 'History: Cluster & JBMIS Codes';
    }
    return `History: ${props.customTitle || (props.selectedType ? formatFieldName(props.selectedType) : '[Data]')}`;
});

const canAddHistory = computed(() => {
    return supportedFields.includes(props.selectedType);
});

const isCoordinatedField = computed(() => {
    return props.selectedType === 'cluster_jbmis_codes';
});

const isSingleCoordinatedField = computed(() => {
    return ['cluster_code', 'jbmis_code'].includes(props.selectedType);
});

const fetchHistory = async (field) => {
    if (!props.storeId) {
        console.error('Store ID is missing');
        return;
    }

    isLoading.value = true;
    try {
        let url;
        if (field === 'cluster_jbmis_codes') {
            // Fetch both cluster_code and jbmis_code histories
            url = route('stores.getStoreHistory', { store: props.storeId, field: 'cluster_code,jbmis_code' });
        } else {
            url = route('stores.getStoreHistory', { store: props.storeId, field });
        }
        const response = await axios.get(url);

        localHistories.value = response.data.history;
    } catch (error) {
        console.error('Error fetching history:', error);
    } finally {
        isLoading.value = false;
    }
};

const formatFieldName = (field) => {
    if (field === 'cluster_jbmis_codes') {
        return 'Cluster & JBMIS Codes';
    }
    return field.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
};

const openCoordinatedHistoryModal = () => {
    addCoordinatedHistoryModalOpen.value = true;
};

const handleCoordinatedHistorySuccess = () => {
    // Refresh the history list
    fetchHistory(props.selectedType);
};

const resetForm = () => {
    formData.value = '';
    formData.effective_at = '';
    errors.value = {};
};

const submitHistoryEntry = async () => {
    if (!props.storeId) {
        console.error('Store ID is missing');
        return;
    }

    if (!props.selectedType) {
        console.error('Selected type is missing');
        return;
    }

    errors.value = {};
    isSubmitting.value = true;

    try {
        const payload = {
            field: props.selectedType,
            value: formData.value,
            effective_at: formData.effective_at,
        };

        const response = await axios.post(route('stores.addStoreHistory', { store: props.storeId }), payload);
        
        if (response.data.success) {
            resetForm();
            activeTab.value = 'view';
            // Refresh the history list
            await fetchHistory(props.selectedType);
        }
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            console.error('Error adding history entry:', error);
        }
    } finally {
        isSubmitting.value = false;
    }
};

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            activeTab.value = 'view';
            resetForm();
            if (props.selectedType) {
                fetchHistory(props.selectedType);
            }
        }
    }
);
</script>

<template>
    <HistoryModal :large="true" :open="open" @close="emits('close')">
        <template #header>
            <div class="space-y-4">
                <h4 class="text-lg font-sans font-bold text-gray-900">
                    {{ modalTitle }}
                </h4>

                <!-- Tabs -->
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button
                            @click="activeTab = 'view'"
                            :class="[
                                activeTab === 'view'
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                                'whitespace-nowrap border-b-2 py-2 px-1 text-sm font-medium'
                            ]"
                        >
                            View History
                        </button>
                        <button
                            v-if="canAddHistory && !isCoordinatedField"
                            @click="activeTab = 'add'"
                            :class="[
                                activeTab === 'add'
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                                'whitespace-nowrap border-b-2 py-2 px-1 text-sm font-medium'
                            ]"
                        >
                            Add History Entry
                        </button>
                        <button
                            v-if="isCoordinatedField"
                            @click="openCoordinatedHistoryModal"
                            class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap border-b-2 py-2 px-1 text-sm font-medium"
                        >
                            Add Coordinated History
                        </button>
                    </nav>
                </div>
            </div>
        </template>

        <template #content>
            <!-- View History Tab -->
            <div v-if="activeTab === 'view'">
                <div v-if="isLoading" class="text-center py-4">Loading history...</div>
                <HistoryDatatable
                    v-else
                    :histories="localHistories.length ? localHistories : histories"
                />
            </div>

            <!-- Add History Entry Tab -->
            <div v-if="activeTab === 'add' && canAddHistory && !isCoordinatedField" class="space-y-6">
                <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-4">
                    <p class="text-sm text-blue-700">
                        You are adding a history entry for <strong>{{ formatFieldName(selectedType) }}</strong>.
                        This will not change the current store data.
                    </p>
                    <p v-if="isSingleCoordinatedField" class="text-sm text-orange-700 mt-2">
                        <strong>Note:</strong> {{ formatFieldName(selectedType) }} is typically updated together with its counterpart code for royalty processing consistency.
                    </p>
                </div>
                <form @submit.prevent="submitHistoryEntry" class="space-y-4">
                    <!-- Value -->
                    <div>
                        <label for="value" class="block text-sm font-medium text-gray-700">
                            {{ formatFieldName(selectedType) }} Value
                        </label>
                        <input
                            id="value"
                            v-model="formData.value"
                            type="text"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            :class="{ 'border-red-300': errors.value }"
                            placeholder="Enter the value"
                        />
                        <p v-if="errors.value" class="mt-1 text-sm text-red-600">
                            {{ errors.value[0] }}
                        </p>
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
                        />
                        <p v-if="errors.effective_at" class="mt-1 text-sm text-red-600">
                            {{ errors.effective_at[0] }}
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end space-x-3">
                        <button
                            type="button"
                            @click="resetForm"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            Reset
                        </button>
                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="isSubmitting">Adding...</span>
                            <span v-else>Add History Entry</span>
                        </button>
                    </div>
                </form>
            </div>
        </template>
    </HistoryModal>

    <AddClusterJbmisHistoryModal
        :open="addCoordinatedHistoryModalOpen"
        :store-id="storeId"
        @close="addCoordinatedHistoryModalOpen = false"
        @success="handleCoordinatedHistorySuccess"
    />
</template>
