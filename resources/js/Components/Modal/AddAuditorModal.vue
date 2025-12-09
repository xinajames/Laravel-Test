<script setup>
import { nextTick, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';

import Modal from '@/Components/Shared/Modal.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SearchInputDropdown from '@/Components/Common/Select/SearchInputDropdown.vue';

const emits = defineEmits(['close', 'success']);

const props = defineProps({
    open: Boolean,
    store_id: Number,
});

const form = useForm({
    user_id: null,
    store_id: props.store_id,
});

const auditors = ref([]);
const selectedAuditor = ref(null);

async function getAuditors() {
    if (!props.store_id) return;

    // Clear data before fetching new data
    auditors.value = [];
    selectedAuditor.value = null;
    form.user_id = null;

    let url = route('storeAuditors.getDataList', [props.store_id]);

    try {
        const response = await axios.get(url);

        await nextTick();

        auditors.value = response.data.map((auditor) => ({
            label: auditor.name, // Format: "John Doe (john@example.com)"
            value: auditor.id, // User ID
        }));

        if (auditors.value.length > 0) {
            selectedAuditor.value = auditors.value[0];
            form.user_id = selectedAuditor.value.value;
        }
    } catch (error) {
        console.error('Error fetching auditors:', error);
    }
}

const handleUpdateAuditor = (value) => {
    selectedAuditor.value = value;
    form.user_id = value?.value ?? null;
};

// Watch for store_id changes and refresh auditors
watch(() => props.store_id, getAuditors, { immediate: true });

// Watch for modal open and ensure fresh data every time
watch(
    () => props.open,
    async (isOpen) => {
        if (isOpen) {
            await nextTick();
            getAuditors();
        }
    }
);

function save() {
    if (!form.user_id) {
        alert('Please select an auditor!');
        return;
    }

    form.post(route('storeAuditors.store'), {
        onSuccess: () => {
            form.reset();
            emits('success');
            emits('close');
        },
    });
}
</script>

<template>
    <Modal :open="open" max-width="lg" @close="emits('close')">
        <template v-slot:content>
            <form class="overflow-y-auto" @submit.prevent="save">
                <div class="py-4 px-6 border-b">
                    <h5 class="text-lg font-medium text-gray-900">Add Auditor</h5>
                </div>
                <div class="p-6 space-y-4">
                    <SearchInputDropdown
                        :dataList="auditors"
                        :modelValue="selectedAuditor?.label || ''"
                        label="Choose Team Member"
                        @update-data="handleUpdateAuditor"
                    />
                </div>
                <div class="border-t p-4 flex justify-end gap-2">
                    <SecondaryButton type="button" @click="emits('close')">Cancel</SecondaryButton>
                    <PrimaryButton :disabled="form.processing" type="submit">
                        Add Auditor
                    </PrimaryButton>
                </div>
            </form>
        </template>
    </Modal>
</template>
