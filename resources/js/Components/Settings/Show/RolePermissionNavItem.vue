<script setup>
import { ref } from 'vue';

import Card from '@/Components/Shared/Card.vue';
import PlusIcon from '@/Components/Icon/PlusIcon.vue';
import AddRoleModal from '@/Components/Modal/AddRoleModal.vue';

const emits = defineEmits(['roleSelected', 'reloadData']);

const props = defineProps({
    roles: Array,
    selectedRole: Object,
});

const roleModal = ref(false);

function handleAddSuccess() {
    emits('reloadData');
    roleModal.value = false;
}
</script>

<template>
    <Card padding="p-2">
        <template v-slot:content>
            <div class="space-y-3">
                <div v-for="(item, index) in roles" :key="index">
                    <!--Exclude Store Auditor-->
                    <div
                        v-if="item.name !== 'Store Auditor'"
                        :class="[
                            selectedRole && selectedRole.id === item.id
                                ? 'bg-[#E5E7EB] text-gray-900'
                                : 'text-dark hover:text-white hover:bg-primary',
                            'group flex gap-x-3 rounded-md p-2 cursor-pointer select-none',
                        ]"
                        @click="emits('roleSelected', item)"
                    >
                        <p class="text-sm font-medium">
                            {{ item.name }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="border-gray-200 mt-4 border-t">
                <div
                    class="flex items-center justify-center gap-2 cursor-pointer bg-rose-50 mt-4 text-[#A9414D] py-2.5 rounded-lg"
                    @click="roleModal = true"
                >
                    <PlusIcon class="w-5 h-5 text-[#A9414D]" />
                    <p class="font-semibold text-primary">Add Role</p>
                </div>
            </div>

            <AddRoleModal
                :open="roleModal"
                @close="roleModal = false"
                @success="handleAddSuccess"
            />
        </template>
    </Card>
</template>
