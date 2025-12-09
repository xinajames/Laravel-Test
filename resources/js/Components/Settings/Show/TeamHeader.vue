<script setup>
import { reactive, ref } from 'vue';
import Avatar from '@/Components/Common/Avatar/Avatar.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import TrashIcon from '@/Components/Icon/TrashIcon.vue';
import LockOpenIcon from '@/Components/Icon/LockOpenIcon.vue';
import ConfirmationModal from '@/Components/Modal/ConfirmationModal.vue';
import ResetPasswordModal from '@/Components/Modal/ResetPasswordModal.vue';

const props = defineProps({
    team: Object,
});

function handleDelete() {
    confirmationModal.open = true;
}

function handleSuccess() {
    confirmationModal.open = false;
}

const confirmationModal = reactive({
    open: false,
    header: 'Delete Team Member',
    message:
        'Are you sure you want to delete this team member? This will permanently remove the user and revoke their access to the application. This action cannot be undone.',
    icon: 'delete',
    action_label: 'Delete',
    action: route('teams.delete', props.team.id),
});

const resetPasswordModal = ref(false);
</script>

<template>
    <div class="w-full bg-white">
        <div class="h-auto sm:h-[112px] py-6 px-4 sm:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 items-center">
                <!-- Avatar & Name -->
                <div class="flex flex-col sm:flex-row sm:col-span-2 items-center gap-4">
                    <Avatar
                        :image-url="team.profile_photo_url"
                        custom-class="w-[64px] h-[64px]"
                        image-class="w-full h-full rounded-full object-cover"
                    />
                    <h1
                        class="text-xl sm:text-2xl font-bold text-center sm:text-left flex items-center"
                    >
                        {{ team.name }}
                    </h1>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-3 items-center sm:justify-end w-full">
                    <SecondaryButton class="w-full sm:w-auto" @click="handleDelete">
                        <TrashIcon class="w-4 h-4" />
                        Delete
                    </SecondaryButton>

                    <SecondaryButton class="w-full sm:w-auto" @click="resetPasswordModal = true">
                        <LockOpenIcon class="w-4 h-4" />
                        Reset Password
                    </SecondaryButton>
                </div>
            </div>
        </div>

        <ConfirmationModal
            :action="confirmationModal.action"
            :action_label="confirmationModal.action_label"
            :header="confirmationModal.header"
            :icon="confirmationModal.icon"
            :message="confirmationModal.message"
            :open="confirmationModal.open"
            @close="confirmationModal.open = false"
            @success="handleSuccess"
        />

        <ResetPasswordModal
            :open="resetPasswordModal"
            :team-id="team.id"
            @close="resetPasswordModal = false"
        />
    </div>
</template>
