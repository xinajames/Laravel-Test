<script setup>
import { onMounted, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import FilterModal from '@/Components/Shared/FilterModal.vue';
import SearchInputDropdown from '@/Components/Common/Select/SearchInputDropdown.vue';

const emits = defineEmits(['close', 'applyFilters', 'resetFilters']);

const props = defineProps({
    open: Boolean,
    activeFilters: Array,
});

const form = useForm({
    role: null,
    status: null,
});

const roles = ref([]);

const statuses = ref([]);

const selectedStatus = ref(null);
const selectedRole = ref(null);

const handleUpdateStatus = (value) => {
    selectedStatus.value = value;
    form.status = value?.value ?? null;
};

const handleUpdateRole = (value) => {
    selectedRole.value = value;
    form.role = value?.value ?? null;
};

const activeFilters = ref([]);

const applyFilters = () => {
    const filters = [];

    if (form.status) {
        const selectedStatusItem = statuses.value.find((stat) => stat.value === form.status);
        filters.push({
            column: 'status',
            operator: '=',
            label: selectedStatusItem ? selectedStatusItem.label : form.status,
            value: selectedStatusItem ? selectedStatusItem.value : form.status,
        });
    }

    if (form.role) {
        const selectedRole = roles.value.find((role) => role.value === form.role);
        filters.push({
            column: 'user_role_id',
            operator: '=',
            label: selectedRole ? selectedRole.label : form.role,
            value: selectedRole ? selectedRole.value : form.role,
        });
    }

    activeFilters.value = filters;
    emits('applyFilters');
};

function resetFilters() {
    form.role = null;
    form.status = null;
    selectedStatus.value = null;
    selectedRole.value = null;
    form.reset();
    activeFilters.value = [];
    emits('resetFilters');
}

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

function getEnums(enum_key, target) {
    let url = route('enums.getDataList', { key: enum_key });
    axios.get(url).then((response) => {
        target.value = response.data;
    });
}

onMounted(() => {
    getRoles();
    getEnums('user-status-enum', statuses);
});

defineExpose({
    activeFilters,
    resetFilters,
});
</script>

<template>
    <FilterModal :open="open" @close="emits('close')">
        <template #content>
            <div class="border-t border-light-gray mt-2 p-6 space-y-6">
                <!-- Form Fields -->
                <SearchInputDropdown
                    :dataList="roles"
                    :modelValue="selectedRole?.label || ''"
                    label="Role"
                    @update-data="handleUpdateRole"
                />

                <SearchInputDropdown
                    :dataList="statuses"
                    :modelValue="selectedStatus?.label || ''"
                    label="Status"
                    @update-data="handleUpdateStatus"
                />
            </div>

            <!-- Footer Actions -->
            <div class="border-t border-light-gray p-6 bg-gray-50">
                <div class="flex flex-col sm:flex-row gap-4 justify-between">
                    <SecondaryButton class="!font-medium w-full sm:w-auto" @click="resetFilters">
                        Clear All
                    </SecondaryButton>
                    <div class="flex gap-2 justify-end w-full sm:w-auto">
                        <SecondaryButton
                            class="!font-medium w-full sm:w-auto"
                            @click="emits('close')"
                        >
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton class="!font-medium w-full sm:w-auto" @click="applyFilters">
                            Apply
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </template>
    </FilterModal>
</template>
