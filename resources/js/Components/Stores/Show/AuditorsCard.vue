<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import axios from 'axios';

import SearchInput from '@/Components/Common/Input/SearchInput.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import PlusIcon from '@/Components/Icon/PlusIcon.vue';
import Avatar from '@/Components/Common/Avatar/Avatar.vue';
import TrashIcon from '@/Components/Icon/TrashIcon.vue';
import AddAuditorModal from '@/Components/Modal/AddAuditorModal.vue';
import ConfirmationModal from '@/Components/Modal/ConfirmationModal.vue';

const props = defineProps({
    store_id: {
        type: Number,
        required: true,
    },
});

const search = ref(null);

const auditors = ref([]);

const loading = ref(false);

const auditorModal = ref(false);

const confirmationModal = reactive({
    action: null,
    open: false,
    header: 'Are you sure?',
    message: '',
    action_label: 'Proceed',
});

const filteredAuditors = computed(() => {
    if (!search.value) return auditors.value;

    const keyword = search.value.toLowerCase();

    return auditors.value.filter((auditor) => {
        return (
            auditor.user.name.toLowerCase().includes(keyword) ||
            auditor.user.email.toLowerCase().includes(keyword)
        );
    });
});

async function fetchAuditors() {
    loading.value = true;
    try {
        const response = await axios.get(route('storeAuditors.dataTable'), {
            params: {
                filters: { store_id: props.store_id },
            },
        });
        auditors.value = response.data || [];
    } catch (error) {
        console.error('Error fetching auditors:', error);
    } finally {
        loading.value = false;
    }
}

function handleDelete(auditor) {
    confirmationModal.header = `Remove ${auditor.user.name} as an auditor`;
    confirmationModal.action_label = 'Remove';
    confirmationModal.icon = 'delete';
    confirmationModal.action = route('storeAuditors.delete', auditor.store_auditor_id);
    confirmationModal.message = `By removing <strong>${auditor.user.name}</strong> as an auditor, they will no longer be able to manage store ratings for this store.`;
    confirmationModal.open = true;
}

onMounted(() => {
    fetchAuditors();
});
</script>

<template>
    <div>
        <div class="space-y-4">
            <!-- Search & Add Auditor Button -->
            <div class="flex gap-4 items-center mt-4">
                <SearchInput v-model="search" class="flex-1 !rounded-md" placeholder="Search" />
                <PrimaryButton @click="auditorModal = true">
                    <PlusIcon />
                    Add Auditor
                </PrimaryButton>
            </div>

            <!-- Auditor List -->
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div v-if="loading" class="p-6 text-center text-gray-500">Loading auditors...</div>
                <div
                    v-else-if="!loading && filteredAuditors.length === 0"
                    class="p-6 text-center text-gray-500"
                >
                    No auditors found.
                </div>
                <div
                    v-for="(auditor, index) in filteredAuditors"
                    :key="auditor.store_auditor_id"
                    :class="{
                        'rounded-t-lg': index === 0,
                        'rounded-b-lg': index === filteredAuditors.length - 1,
                        'border-b': index !== filteredAuditors.length - 1,
                    }"
                    class="p-6 flex bg-white items-center justify-between border-gray-200"
                >
                    <div class="flex gap-4 items-center">
                        <Avatar
                            :image-url="auditor.user.profile_photo_url"
                            class="h-10 w-10 rounded-full"
                        />
                        <div>
                            <p class="font-medium text-sm">{{ auditor.user.name }}</p>
                            <p class="text-sm text-gray-500">{{ auditor.user.email }}</p>
                        </div>
                    </div>
                    <div class="text-gray-400 cursor-pointer" @click="handleDelete(auditor)">
                        <TrashIcon />
                    </div>
                </div>
            </div>
        </div>

        <AddAuditorModal
            :open="auditorModal"
            :store_id="store_id"
            @close="auditorModal = false"
            @success="fetchAuditors"
        />

        <ConfirmationModal
            :action="confirmationModal.action"
            :action_label="confirmationModal.action_label"
            :header="confirmationModal.header"
            :icon="confirmationModal.icon"
            :message="confirmationModal.message"
            :open="confirmationModal.open"
            @close="confirmationModal.open = false"
            @success="fetchAuditors"
        />
    </div>
</template>

<style scoped></style>
