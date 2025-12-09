<script setup>
import { computed, onMounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import Modal from '@/Components/Shared/Modal.vue';
import SearchInputDropdown from '@/Components/Common/Select/SearchInputDropdown.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';

const props = defineProps({
    open: Boolean,
    store: Object,
    currentFranchiseeCode: String,
    page: { type: String, default: 'show' },
});

const currentFranchiseeDisplay = computed(() => {
    if (!props.store.franchisee) return 'Not assigned';
    const code = props.store.franchisee.franchisee_code || '';
    const name = props.store.franchisee.full_name || '';
    return code && name ? `${code} - ${name}` : (code || name || 'Not assigned');
});

const emits = defineEmits(['close', 'success', 'update-franchisee']);

const selectedFranchisee = ref(null);
const franchiseeOptions = ref([]);
const isLoading = ref(false);

const fullFranchiseeLabel = computed(() => {
    if (!selectedFranchisee.value) return '';
    const name = selectedFranchisee.value.franchisee_name || '';
    const corp = selectedFranchisee.value.corporation_name || '';
    return corp ? `${name} - ${corp}` : name;
});

const handleUpdateData = (value) => {
    selectedFranchisee.value = value;
};

const handleSave = () => {
    if (!selectedFranchisee.value) return;

    if (props.page === 'edit') {
        // For edit page, just emit the data to parent (form will handle save)
        emits('update-franchisee', selectedFranchisee.value);
        emits('close');
    } else {
        // For show page, save immediately
        isLoading.value = true;
        
        router.post(
            route('stores.update', props.store.id),
            { 
                franchisee_id: selectedFranchisee.value.id,
                application_step: 'basic-details' 
            },
            {
                onSuccess: () => {
                    emits('success');
                    emits('close');
                },
                onFinish: () => {
                    isLoading.value = false;
                },
            }
        );
    }
};

const handleClose = () => {
    selectedFranchisee.value = null;
    emits('close');
};

function getFranchisees() {
    let url = route('franchisees.getDataList');
    axios.get(url).then((response) => {
        franchiseeOptions.value = response.data;
    });
}

onMounted(() => {
    getFranchisees();
});
</script>

<template>
    <Modal :open="open" max-width="lg" @close="handleClose">
        <template #content>
            <div class="p-6">
                <!-- Header -->
                <div class="mb-6">
                    <p class="text-lg font-semibold text-gray-900">Change Franchisee Assignment</p>
                    <p class="text-sm text-gray-600 mt-1">
                        Change the franchisee assigned to this store
                    </p>
                </div>

                <!-- Warning -->
                <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                    <div class="flex items-start">
                        <div>
                            <p class="text-sm text-amber-700 mt-1">
                                <b>Warning:</b> Changing the franchisee assignment will reassign this store to a different franchisee. 
                                This action will be logged in the store history.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Current Franchisee -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Current Franchisee
                    </label>
                    <div class="p-3 bg-gray-50 border border-gray-200 rounded-md">
                        <span class="text-sm text-gray-900">{{ currentFranchiseeDisplay }}</span>
                    </div>
                </div>

                <!-- New Franchisee Selection -->
                <div class="mb-6">
                    <SearchInputDropdown
                        :dataList="franchiseeOptions"
                        :modelValue="fullFranchiseeLabel"
                        :required="true"
                        :with-image="true"
                        label="Select New Franchisee"
                        @update-data="handleUpdateData"
                    />
                </div>

                <!-- Selected Franchisee Details -->
                <div v-if="selectedFranchisee" class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-sm font-medium text-blue-800 mb-2">Selected Franchisee Details</p>
                    <div class="space-y-1 text-sm text-blue-700">
                        <p><strong>Code:</strong> {{ selectedFranchisee.franchisee_code || '—' }}</p>
                        <p><strong>Name:</strong> {{ selectedFranchisee.franchisee_name }}</p>
                        <p v-if="selectedFranchisee.corporation_name">
                            <strong>Corporation:</strong> {{ selectedFranchisee.corporation_name }}
                        </p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <SecondaryButton @click="handleClose" :disabled="isLoading">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton 
                        @click="handleSave" 
                        :disabled="!selectedFranchisee || isLoading"
                    >
                        <span v-if="isLoading">Updating...</span>
                        <span v-else>{{ props.page === 'edit' ? 'Select Franchisee' : 'Update Franchisee' }}</span>
                    </PrimaryButton>
                </div>
            </div>
        </template>
    </Modal>
</template> 