<script setup>
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Shared/Modal.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import TextArea from '@/Components/Common/Input/TextArea.vue';

const emits = defineEmits(['close', 'save']);

const props = defineProps({
    open: Boolean,
});

const form = useForm({
    remarks: null,
});

function save() {
    emits('save', form.remarks); // Emit the remarks value to the parent component
    form.reset(); // Reset the form after submission
    emits('close'); // Close the modal
}
</script>

<template>
    <Modal max-width="lg" :open="open" @close="emits('close')">
        <template v-slot:content>
            <form @submit.prevent="save">
                <div class="py-4 px-6 border-b">
                    <h5 class="text-lg font-medium text-gray-900">Add Sub Comment</h5>
                </div>
                <div class="p-6 space-y-4">
                    <TextArea
                        v-model="form.remarks"
                        input-class="!border-gray-300"
                        label="Comment"
                    />
                </div>
                <div class="border-t p-4 flex justify-end gap-2">
                    <SecondaryButton type="button" @click="emits('close')">Cancel</SecondaryButton>
                    <PrimaryButton :disabled="form.processing" type="submit">
                        Add Comment
                    </PrimaryButton>
                </div>
            </form>
        </template>
    </Modal>
</template>
