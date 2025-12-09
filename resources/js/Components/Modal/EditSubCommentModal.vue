<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import Modal from '@/Components/Shared/Modal.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import TextArea from '@/Components/Common/Input/TextArea.vue';

const emits = defineEmits(['close']);

const props = defineProps({
    open: Boolean,
    item: Object, // Accept the item to edit
});

const form = useForm({
    remarks: null,
});

// Watch for changes in item and update the form's remarks field
watch(
    () => props.item, // Correct reference
    (newItem) => {
        if (newItem) {
            form.remarks = newItem.remarks; // Ensure it updates correctly
        }
    },
    { immediate: true }
);

function save() {
    // Emit the updated remarks (or perform an API request)
    emits('close');
}
</script>

<template>
    <Modal max-width="lg" :open="open" @close="emits('close')">
        <template v-slot:content>
            <form @submit.prevent="save">
                <div class="py-4 px-6 border-b">
                    <h5 class="text-lg font-medium text-gray-900">Edit Sub Comment</h5>
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
                    <PrimaryButton :disabled="form.processing" type="submit">Update</PrimaryButton>
                </div>
            </form>
        </template>
    </Modal>
</template>
