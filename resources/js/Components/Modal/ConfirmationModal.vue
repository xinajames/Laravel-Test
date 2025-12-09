<script setup>
import { ref } from 'vue';
import Modal from '@/Components/Shared/Modal.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import { router } from '@inertiajs/vue3';
import Spinner3Icon from '@/Components/Icon/Spinner3Icon.vue';

const emits = defineEmits(['close', 'success', 'cancelAction']);

const props = defineProps({
    action: String,
    action_label: { type: String, default: 'Confirm' },
    cancel_label: { type: String, default: 'Cancel' },
    cancel_action: { type: Boolean, default: false },
    data: { type: [Object, Array], default: null },
    open: Boolean,
    icon: { type: String, default: null },
    header: String,
    message: String,
    method: { type: String, default: 'post' },
    alignLeft: { type: Boolean, default: false },
});

const disabled = ref(false);

function handleConfirm() {
    if (!props.action) {
        emits('close'); // If no action, just close the modal
        return;
    }

    disabled.value = true;
    if (props.method === 'visit') {
        router.visit(props.action);
    } else {
        router.post(props.action, props.data, {
            onSuccess: () => {
                emits('success');
                emits('close');
            },
            onFinish: () => {
                disabled.value = false;
            },
            preserveScroll: true,
        });
    }
}

// Fallback for cancel action
function handleCancel() {
    if (props.cancel_action) {
        emits('cancelAction');
    } else {
        emits('close'); // Default behavior if cancel_action is not provided
    }
}
</script>

<template>
    <Modal :open="open" max-width="lg" @close="emits('close')">
        <template v-slot:content>
            <div class="flex flex-col justify-center items-center text-center px-6 py-6 space-y-4">
                <div>
                    <Spinner3Icon v-if="icon === 'loading'" :size="48" />

                    <img
                        v-if="icon === 'document'"
                        class="h-16"
                        src="/icons/document-gray-icon.gif"
                    />
                    <img v-if="icon === 'delete'" class="h-16" src="/icons/delete-gray-icon.gif" />
                    <img
                        v-if="icon === 'deactivate'"
                        class="h-16"
                        src="/icons/deactivate-gray-icon.gif"
                    />
                    <img
                        v-if="icon === 'reactivate'"
                        class="h-16"
                        src="/icons/sync-gray-icon.gif"
                    />
                    <img
                        v-if="icon === 'information'"
                        class="h-16"
                        src="/icons/done-gray-icon.gif"
                    />
                    <img v-if="icon === 'star'" class="h-16" src="/icons/star-gray-icon.gif" />
                    <img
                        v-if="icon === 'notification'"
                        class="h-16"
                        src="/icons/warning-gray-icon.gif"
                    />
                </div>
                <h4 class="font-semibold font-sans">{{ header }}</h4>
                <p
                    :class="[
                        'text-gray-900 text-sm',
                        alignLeft ? 'text-left w-full' : 'text-center',
                    ]"
                    v-html="message"
                ></p>
            </div>
            <div :class="{ 'border-t border-gray-200': cancel_label }">
                <div
                    class="flex justify-end gap-4 py-4 px-6"
                    :class="{ 'justify-center': !cancel_label }"
                >
                    <!-- Conditionally render the cancel button -->
                    <SecondaryButton
                        v-if="cancel_label"
                        class="!text-gray-700 !font-medium"
                        @click="handleCancel"
                    >
                        {{ cancel_label }}
                    </SecondaryButton>
                    <PrimaryButton class="!font-medium" :disabled="disabled" @click="handleConfirm">
                        {{ action_label }}
                    </PrimaryButton>
                </div>
            </div>
        </template>
    </Modal>
</template>
