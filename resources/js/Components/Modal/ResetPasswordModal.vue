<script setup>
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Shared/Modal.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import PasswordInput from '@/Components/Common/Input/PasswordInput.vue';

const emits = defineEmits(['close']);

const props = defineProps({
    open: false,
    teamId: Number,
});

const form = useForm({
    new_password: '',
    errors: null,
});

function save() {
    form.post(route('teams.resetPassword', props.teamId), {
        onSuccess: () => {
            emits('close');
        },
        onError: (errors) => {
            form.errors = errors;
        },
    });
}
</script>
<template>
    <Modal max-width="lg" :open="open" @close="emits('close')">
        <template v-slot:content>
            <form @submit.prevent="save">
                <div class="py-4 px-6 border-b">
                    <h5 class="text-lg font-medium text-gray-900">Reset Password</h5>
                </div>
                <div class="p-6 space-y-4">
                    <PasswordInput
                        id="new_password"
                        v-model="form.new_password"
                        :error="form.errors ? form.errors.new_password : []"
                        input-class="!border-gray-300"
                        label="New Password"
                        :required="true"
                    />
                </div>
                <div class="border-t p-4 flex justify-end gap-2">
                    <SecondaryButton type="button" @click="emits('close')">Cancel</SecondaryButton>
                    <PrimaryButton :disabled="form.processing" type="submit">
                        Reset Password
                    </PrimaryButton>
                </div>
            </form>
        </template>
    </Modal>
</template>
