<script setup>
import { reactive, ref } from 'vue';
import Avatar from '@/Components/Common/Avatar/Avatar.vue';
import TrashIcon from '@/Components/Icon/TrashIcon.vue';
import PencilAltIcon from '@/Components/Icon/PencilAltIcon.vue';
import ConfirmationModal from '@/Components/Modal/ConfirmationModal.vue';
import EditCommentModal from '@/Components/Modal/EditCommentModal.vue';

const props = defineProps({
    question: Object,
    remark: Object,
    page: { type: String, default: 'show' },
});

const editComment = ref(false);

const confirmationModal = reactive({
    action: null,
    data: null,
    open: false,
    header: 'Delete Comment',
    message: 'Are you sure you want to delete this comment?',
    icon: 'document',
    action_label: 'Delete',
});

function editRemarks() {
    editComment.value = true;
}

function deleteRemarks() {
    confirmationModal.action = route('remarks.delete', props.remark.id);
    confirmationModal.open = true;
}
</script>

<template>
    <div class="mt-4">
        <div class="mt-2 flex gap-2">
            <Avatar
                :image-url="remark.profile_photo_url"
                custom-class="w-8 h-8"
                image-class="w-full h-full rounded-full object-cover"
            />
            <div class="p-2 bg-indigo-50 max-w-lg w-full rounded-b-2xl rounded-tr-2xl">
                <p class="text-sm font-medium text-gray-900">
                    {{ remark.message }}
                </p>
                <div v-if="page === 'create'" class="flex gap-4 mt-4">
                    <button
                        class="flex text-xs gap-2 items-center text-[#A32130] hover:underline"
                        @click="editRemarks(remark)"
                    >
                        <PencilAltIcon class="!stroke-1 size-4 text-[#A32130] mb-0.5" />
                        Edit
                    </button>
                    <button
                        class="flex gap-2 text-xs items-center text-red-600 hover:underline"
                        @click="deleteRemarks(remark)"
                    >
                        <TrashIcon class="!stroke-1 size-4 text-red-600 mb-0.5" />
                        Delete
                    </button>
                </div>
            </div>
        </div>

        <EditCommentModal :open="editComment" :remark="remark" @close="editComment = false" />

        <ConfirmationModal
            :action="confirmationModal.action"
            :action_label="confirmationModal.action_label"
            :data="confirmationModal.data"
            :header="confirmationModal.header"
            :icon="confirmationModal.icon"
            :message="confirmationModal.message"
            :open="confirmationModal.open"
            @close="confirmationModal.open = false"
        />
    </div>
</template>
