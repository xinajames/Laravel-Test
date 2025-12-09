<script setup>
import { router, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import Modal from '@/Components/Shared/Modal.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SearchInputDropdown from '@/Components/Common/Select/SearchInputDropdown.vue';

const emits = defineEmits(['close', 'success']);

const props = defineProps({
    open: false,
    roleId: Number,
    userId: Number,
});

const form = useForm({
    user_role_id: null,
});

const roles = ref([]);

const processing = ref(false);

const handleUpdateRole = (value) => {
    form.user_role_id = value?.value ?? null;
};

function save() {
    processing.value = true;
    form.post(route('teams.update', props.userId), {
        onSuccess: () => {
            emits('success');
            emits('close');
        },
        onFinish: () => {
            processing.value = false;
        },
        preserveScroll: true,
    });
}

function getRoles() {
    let url = route('userRoles.getDataList');
    axios.get(url).then((response) => {
        roles.value = response.data.map((role) => ({
            value: role.id,
            label: role.name,
            members: role.membersCount,
        }));

        const initialRole = roles.value.find((role) => role.value === props.roleId);
        form.user_role_id = initialRole ? initialRole.value : null;
    });
}

onMounted(() => {
    getRoles();
});
</script>
<template>
    <Modal max-width="lg" :open="open" @close="emits('close')">
        <template v-slot:content>
            <form @submit.prevent="save">
                <div class="py-4 px-6 border-b">
                    <h5 class="text-lg font-medium text-gray-900">Change Role</h5>
                </div>
                <div class="p-6 space-y-4">
                    <SearchInputDropdown
                        :modelValue="roles.find((r) => r.value === form.user_role_id)?.label || ''"
                        @update-data="handleUpdateRole"
                        :with-image="false"
                        label="Role"
                        :dataList="roles"
                    />
                </div>
                <div class="border-t p-4 flex justify-end gap-2">
                    <SecondaryButton type="button" @click="emits('close')">Cancel</SecondaryButton>
                    <PrimaryButton :disabled="form.processing" type="submit">
                        Change Role
                    </PrimaryButton>
                </div>
            </form>
        </template>
    </Modal>
</template>
