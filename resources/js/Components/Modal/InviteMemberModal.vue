<script setup>
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Shared/Modal.vue';
import TextInput from '@/Components/Common/Input/TextInput.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SearchInputDropdown from '@/Components/Common/Select/SearchInputDropdown.vue';
import { onMounted, ref } from 'vue';

const emits = defineEmits(['close', 'success']);

const props = defineProps({
    open: false,
});

const form = useForm({
    name: null,
    email: null,
    user_role_id: null,
});

const roles = ref([]);

function getRoles() {
    let url = route('userRoles.getDataList');
    axios.get(url).then((response) => {
        roles.value = response.data.map((role) => ({
            value: role.id,
            label: role.name,
            members: role.membersCount,
        }));
    });
}

onMounted(() => {
    getRoles();
});

function save() {
    form.post(route('teams.invite'), {
        onSuccess: () => {
            form.reset();
            emits('success');
            emits('close');
        },
        preserveScroll: true,
    });
}

const handleUpdateRole = (value) => {
    form.user_role_id = value?.value ?? null;
};
</script>

<template>
    <Modal :open="open" max-width="lg" @close="emits('close')">
        <template v-slot:content>
            <form @submit.prevent="save">
                <div class="py-4 px-6 border-b">
                    <h5 class="text-lg font-medium text-gray-900">Invite Member</h5>
                </div>
                <div class="p-6 space-y-4">
                    <TextInput
                        v-model="form.name"
                        input-class="!border-gray-300"
                        label="Full Name"
                        :required="true"
                    />

                    <TextInput
                        v-model="form.email"
                        input-class="!border-gray-300"
                        label="Email"
                        :required="true"
                    />

                    <SearchInputDropdown
                        :modelValue="roles.find((r) => r.value === form.user_role_id)?.label || ''"
                        :with-image="false"
                        label="Role"
                        @update-data="handleUpdateRole"
                        :dataList="roles"
                        :required="true"
                    />
                </div>
                <div class="border-t p-4 flex justify-end gap-2">
                    <SecondaryButton
                        :disabled="form.processing"
                        type="button"
                        @click="emits('close')"
                    >
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton :disabled="form.processing" type="submit">
                        Invite Member
                    </PrimaryButton>
                </div>
            </form>
        </template>
    </Modal>
</template>
