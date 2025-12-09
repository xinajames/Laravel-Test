<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

import Modal from '@/Components/Shared/Modal.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import TextInput from '@/Components/Common/Input/TextInput.vue';
import DatePicker from '@/Components/Common/DatePicker/DatePicker.vue';
import moment from 'moment';

const emits = defineEmits(['close', 'success', 'edit-reminder']);

const props = defineProps({
    open: Boolean,
    reminder: Object,
    stores: {
        type: Array,
        default: () => [],
    },
    model_type: {
        type: String,
        default: 'store',
    },
});

// Form fields with default values
const form = useForm({
    title: '',
    description: '',
    schedule_date: '',
    model_id: null,
    model_name: '',
    model_type: props.model_type,
});

// Watch for incoming reminder to prefill form
watch(
    () => props.reminder,
    (reminder) => {
        if (reminder) {
            form.title = reminder.title || '';
            form.description = reminder.description || '';
            form.schedule_date = reminder.scheduled_at || '';
            form.model_id = reminder.remindable_id || null;
            form.model_name = reminder.model_name || '';
        }
    },
    { immediate: true }
);

// Submit and emit the updated reminder
const save = () => {
    const formattedDate = form.schedule_date
        ? moment(form.schedule_date).format('YYYY-MM-DD')
        : null;

    const updatedReminder = {
        id: props.reminder?.id || Date.now(),
        title: form.title,
        description: form.description,
        date: formattedDate,
        model_id: form.model_id,
        model_name: form.model_name,
        model_type: form.model_type,
        dismissed: props.reminder?.dismissed || false,
        deleted: props.reminder?.deleted || false,
    };

    emits('edit-reminder', updatedReminder);
    emits('close');
};
</script>

<template>
    <Modal :open="open" max-width="lg" @close="emits('close')">
        <template v-slot:content>
            <form @submit.prevent="save">
                <div class="py-4 px-6 border-b">
                    <h5 class="text-lg font-medium text-gray-900">Edit Reminder</h5>
                </div>

                <div class="p-6 space-y-4">
                    <TextInput
                        v-model="form.title"
                        input-class="!border-gray-300"
                        label="Reminder Title"
                        required
                    />

                    <TextInput
                        v-model="form.description"
                        input-class="!border-gray-300"
                        label="Message"
                        required
                    />

                    <DatePicker
                        v-model="form.schedule_date"
                        :teleport="true"
                        :text-input="true"
                        label="Scheduled Date"
                        placeholder="MM/DD/YYYY"
                        required
                    />

                    <TextInput
                        v-if="form.model_name"
                        v-model="form.model_name"
                        :label="`Tagged ${model_type.charAt(0).toUpperCase() + model_type.slice(1)}`"
                        disabled
                        input-class="!border-gray-300"
                    />
                </div>

                <div class="border-t p-4 flex justify-end gap-2">
                    <SecondaryButton type="button" @click="emits('close')">Cancel</SecondaryButton>
                    <PrimaryButton :disabled="form.processing" type="submit">
                        Save Changes
                    </PrimaryButton>
                </div>
            </form>
        </template>
    </Modal>
</template>
