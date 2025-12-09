<script setup>
import Modal from '@/Components/Shared/Modal.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';

const emits = defineEmits(['close', 'exit', 'save']);

const props = defineProps({
    open: Boolean,
    isProcessing: Boolean,
});
</script>

<template>
    <Modal :open="open" max-width="lg" @close="emits('close')">
        <template v-slot:content>
            <div class="p-6">
                <div class="flex justify-center mb-5">
                    <img class="h-16" src="/icons/done-gray-icon.gif" />
                </div>
                <h5 class="text-center px-6">
                    Looks like you’ve made some changes.
                    <br />
                    Want to save your edits?
                </h5>
                <div class="flex gap-4 items-center mt-6">
                    <secondary-button class="w-full justify-center" @click="emits('exit')">
                        Exit Without Saving
                    </secondary-button>
                    <primary-button
                        :disabled="isProcessing"
                        class="w-full justify-center"
                        @click="emits('save')"
                    >
                        Save and Exit
                    </primary-button>
                </div>
            </div>
        </template>
    </Modal>
</template>
