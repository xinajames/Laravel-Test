<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Shared/Modal.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import XCircleIcon from '@/Components/Icon/XCircleIcon.vue';

const emits = defineEmits(['close', 'deleted']);

const props = defineProps({
    open: Boolean,
    role: Object,
});

const disabled = ref(false);

function save() {
    disabled.value = true;
    router.post(route('userRoles.delete', props.role.id), null, {
        onSuccess: () => {
            disabled.value = false;
            emits('close');
            emits('deleted');
        },
    });
}
</script>
<template>
    <Modal max-width="lg" :open="open" @close="emits('close')" :closeable="false">
        <template v-slot:content>
            <div class="py-4 px-6">
                <h5 class="text-xl font-semibold text-gray-900">Delete {{ role?.name }}?</h5>
                <p class="text-gray-500 mt-2">
                    Are you sure you want to delete this role? This action cannot be undone.
                </p>

                <div v-if="role?.membersCount > 0" class="p-4 bg-red-50 flex gap-3 mt-4 rounded-md">
                    <div>
                        <XCircleIcon class="h-5 w-5" />
                    </div>
                    <p class="text-red-800">
                        This role cannot be deleted as it currently has 1 assigned member. Please
                        reassign the member to a different role before you can proceed with the
                        deletion —
                        <Link :href="route('teams')" class="font-medium underline cursor-pointer">
                            View Members
                        </Link>
                    </p>
                </div>
            </div>
            <div class="border-t p-4 flex justify-end gap-2">
                <SecondaryButton type="button" @click="emits('close')">Cancel</SecondaryButton>
                <PrimaryButton :disabled="role?.membersCount > 0 || disabled" @click="save">
                    Delete role
                </PrimaryButton>
            </div>
        </template>
    </Modal>
</template>
