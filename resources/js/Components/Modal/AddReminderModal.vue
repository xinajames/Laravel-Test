<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, toRefs } from 'vue';

import Modal from '@/Components/Shared/Modal.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import TextInput from '@/Components/Common/Input/TextInput.vue';
import DatePicker from '@/Components/Common/DatePicker/DatePicker.vue';
import SearchSelectModal from '@/Components/Common/Select/SearchSelectModal.vue';
import moment from 'moment';

const emits = defineEmits(['close', 'success', 'add-reminder']);

const props = defineProps({
    open: Boolean,
    stores: {
        type: Array,
        default: () => [],
    },
    model_id: {
        type: [String, Number],
        default: null,
    },
    model_type: {
        type: String,
        default: 'store',
    },
});

const { stores, model_id, model_type } = toRefs(props);

const form = useForm({
    title: null,
    message: null,
    schedule_date: null,
    model_name: null,
    model_id: model_id.value ?? null,
    model_type: model_type.value,
});

// Transform store data for dropdown
const storeOptions = computed(() => {
    return (stores.value || []).map((store) => ({
        label: store.jbs_name || `Store ${store.id}`,
        value: store.id,
        id: store.id,
    }));
});

function updateData(selectedOption) {
    form.model_id = selectedOption.value;
    form.model_name = selectedOption.label;
}

const save = () => {
    const formattedDate = form.schedule_date
        ? moment(form.schedule_date).format('YYYY-MM-DD')
        : null;

    // Fallback to initial model_id if not changed through dropdown
    if (!form.model_id) {
        form.model_id = model_id.value;
    }

    const newReminder = {
        title: form.title,
        description: form.message,
        date: formattedDate,
        model_id: form.model_id,
        model_type: form.model_type,
        dismissed: false,
        deleted: false,
    };

    emits('add-reminder', newReminder);
    emits('close');

    form.reset();
    form.model_id = model_id.value;
    form.model_type = model_type.value;
};
</script>

<template>
    <Modal :open="open" max-width="lg" @close="emits('close')">
        <template v-slot:content>
            <form @submit.prevent="save">
                <div class="py-4 px-6 border-b">
                    <h5 class="text-lg font-medium text-gray-900">Add Reminder</h5>
                </div>
                <div class="p-6 space-y-4">
                    <TextInput
                        v-model="form.title"
                        input-class="!border-gray-300"
                        label="Reminder Title"
                        required
                    />

                    <TextInput
                        v-model="form.message"
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

                    <SearchSelectModal
                        v-if="stores.length"
                        v-model="form.model_name"
                        :label="
                            model_type === 'store'
                                ? 'Tag Store'
                                : `Tag ${model_type.charAt(0).toUpperCase() + model_type.slice(1)}`
                        "
                        :options="storeOptions"
                        :required="false"
                        :value="form.model_id"
                        @option:selected="updateData"
                    />
                </div>

                <div class="border-t p-4 flex justify-end gap-2">
                    <SecondaryButton type="button" @click="emits('close')">Cancel</SecondaryButton>
                    <PrimaryButton :disabled="form.processing" type="submit">
                        Add Reminder
                    </PrimaryButton>
                </div>
            </form>
        </template>
    </Modal>
</template>
