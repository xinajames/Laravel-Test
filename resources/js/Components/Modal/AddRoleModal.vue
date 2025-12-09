<script setup>
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Shared/Modal.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import TextInput from '@/Components/Common/Input/TextInput.vue';

const emits = defineEmits(['close', 'success']);

const props = defineProps({
    open: Boolean,
});

const form = useForm({
    type: null,
});

function save() {
    form.post(route('userRoles.store'), {
        onSuccess: () => {
            form.reset();
            emits('success');
        },
    });
}
</script>
<template>
    <Modal max-width="lg" :open="open" @close="emits('close')">
        <template v-slot:content>
            <form @submit.prevent="save">
                <div class="py-4 px-6 border-b">
                    <h5 class="text-lg font-medium text-gray-900">Add Role</h5>
                </div>
                <div class="p-6 space-y-4">
                    <TextInput
                        v-model="form.type"
                        input-class="!border-gray-300"
                        label="Role Name"
                    />
                </div>
                <div class="border-t p-4 flex justify-end gap-2">
                    <SecondaryButton type="button" @click="emits('close')">Cancel</SecondaryButton>
                    <PrimaryButton :disabled="form.processing" type="submit">
                        Add Role
                    </PrimaryButton>
                </div>
            </form>
        </template>
    </Modal>
</template>
