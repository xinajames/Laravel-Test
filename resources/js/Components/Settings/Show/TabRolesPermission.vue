<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import RolePermissionNavItem from './RolePermissionNavItem.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import EyeOffIcon from '@/Components/Icon/EyeOffIcon.vue';
import EyeIcon from '@/Components/Icon/EyeIcon.vue';
import PencilOutlineIcon from '@/Components/Icon/PencilOutlineIcon.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import RenameModal from '@/Components/Modal/RenameModal.vue';
import DeleteRoleModal from '@/Components/Modal/DeleteRoleModal.vue';

const roles = ref([]);

const selectedRole = ref(null);
const renameModal = ref(false);
const deleteModal = ref(false);

const form = useForm({
    checkedPermissions: [],
});

const permissions = ref([]);

const isSuperAdmin = computed(() => selectedRole.value?.name === 'Super Admin');

function hasNoAccess(moduleIndex) {
    // Check if no permission is checked within the module
    let has_access = true;
    permissions.value[moduleIndex].permissions.forEach((permission) => {
        if (permission.checked) {
            has_access = false;
        }
    });
    return has_access;
}

function handleNoAccess(moduleIndex) {
    permissions.value[moduleIndex].permissions.forEach((permission) => {
        permission.checked = false;
    });
}

function handleModulePermission(moduleIndex, permissionCheck, name) {
    permissions.value[moduleIndex].permissions.forEach((permission) => {
        permission.checked = permission.name === name;
    });
}

function handlePermissionIcon(name) {
    switch (name) {
        case 'View Only':
            return EyeIcon;
        case 'Editor':
            return PencilOutlineIcon;
        default:
            return '';
    }
}

function handleRoleSelected(role) {
    selectedRole.value = role;
    let url = route('userRoles.getPermissionList', selectedRole.value.id);
    axios.get(url).then((response) => {
        permissions.value = response.data;
    });
}

function save() {
    form.checkedPermissions = permissions.value
        .flatMap((module) => module.permissions.filter((permission) => permission.checked))
        .map((permission) => permission.slug);

    let currentRole = selectedRole.value;
    form.post(route('rolePermissions.update', selectedRole.value.id), {
        onSuccess: () => {
            selectedRole.value = currentRole;
            getRoles(selectedRole.value);
        },
    });
}

function getRoles() {
    let url = route('userRoles.getDataList');
    axios.get(url).then((response) => {
        roles.value = response.data;
        if (roles.value[0] && !selectedRole.value) {
            selectedRole.value = roles.value[0];
            handleRoleSelected(selectedRole.value);
        }
    });
}

function handleRoleUpdated(role) {
    selectedRole.value = role;
    getRoles();
    renameModal.value = false;
}

function handleRoleDeleted() {
    deleteModal.value = false;
    selectedRole.value = null;
    getRoles();
}

onMounted(() => {
    getRoles();
});

const canUpdateRolesPermissions = computed(() => {
    return usePage().props.auth.permissions.includes('update-settings-roles-permissions');
});
</script>

<template>
    <div class="p-4 sm:p-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Roles Sidebar -->
            <div class="bg-white border border-gray-200 rounded-2xl p-4 md:p-6">
                <RolePermissionNavItem
                    :roles="roles"
                    :selected-role="selectedRole"
                    @roleSelected="handleRoleSelected"
                    @reload-data="getRoles"
                />
            </div>

            <!-- Main Content -->
            <div class="col-span-3 space-y-6 md:space-y-8">
                <div v-if="selectedRole" class="px-2">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                        <p class="font-semibold text-xl">{{ selectedRole.name }}</p>
                        <SecondaryButton
                            v-if="canUpdateRolesPermissions && !isSuperAdmin"
                            class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130] !bg-transparent !shadow-none hover:!bg-transparent hover:!shadow-none focus:!outline-none"
                            @click="renameModal = true"
                        >
                            Rename
                        </SecondaryButton>
                    </div>
                    <p class="mt-2 text-sm text-gray-600">
                        Customize and manage which modules each user role can access, ensuring the
                        right team members have appropriate permissions.
                    </p>
                </div>

                <!-- Permissions Section -->
                <div
                    :class="{ 'opacity-50 pointer-events-none': isSuperAdmin }"
                    class="bg-white border border-gray-200 rounded-2xl divide-y overflow-auto p-4 sm:p-6"
                >
                    <div
                        v-for="(module, mIndex) in permissions"
                        :key="module.name"
                        class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-gray-200 py-4"
                    >
                        <h5 class="text-gray-500 font-medium capitalize w-full sm:w-auto">
                            {{ module.name }}
                        </h5>

                        <div class="flex-shrink-0 rounded-lg bg-gray-100 w-full sm:w-auto">
                            <div
                                class="grid grid-cols-2 sm:flex sm:gap-2 sm:flex-wrap px-2 py-1 items-center"
                            >
                                <!-- Permission Buttons -->
                                <div
                                    v-for="(permission, pIndex) in module.permissions"
                                    :key="permission.name"
                                    :class="[
                                        permission.checked
                                            ? 'bg-white border border-gray-300 drop-shadow-md'
                                            : '',
                                    ]"
                                    class="inline-flex gap-2 items-center justify-center px-3 py-2 rounded-lg cursor-pointer hover:bg-white w-full sm:w-auto"
                                    @click="
                                        !isSuperAdmin &&
                                        handleModulePermission(
                                            mIndex,
                                            permission.checked,
                                            permission.name
                                        )
                                    "
                                >
                                    <component
                                        :is="handlePermissionIcon(permission.name)"
                                        class="size-4 shrink-0 text-gray-500 !stroke-1"
                                    />
                                    <p class="font-medium text-xs text-gray-700 text-center">
                                        {{ permission.name }}
                                    </p>
                                </div>

                                <!-- No Access Button -->
                                <div
                                    v-if="module.name !== 'Royalty'"
                                    :class="[
                                        hasNoAccess(mIndex)
                                            ? 'bg-white border border-gray-300 drop-shadow-md'
                                            : '',
                                    ]"
                                    class="inline-flex gap-2 items-center justify-center px-3 py-2 rounded-lg cursor-pointer hover:bg-white w-full sm:w-auto"
                                    @click="!isSuperAdmin && handleNoAccess(mIndex)"
                                >
                                    <EyeOffIcon class="size-4 shrink-0 text-gray-500" />
                                    <p class="font-medium text-xs text-gray-700 text-center">
                                        No Access
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Action Buttons -->
                <div
                    class="flex flex-col sm:flex-row justify-between items-center w-full gap-2 sm:gap-4"
                    v-if="canUpdateRolesPermissions && !isSuperAdmin"
                >
                    <PrimaryButton
                        class="flex gap-2 text-white !font-medium !text-sm px-4 py-2 w-full sm:w-auto"
                        :disabled="form.processing"
                        @click="save"
                    >
                        Save Changes
                    </PrimaryButton>
                    <SecondaryButton
                        @click="deleteModal = true"
                        class="w-full !font-medium !text-gray-700 sm:w-auto"
                    >
                        Delete Role
                    </SecondaryButton>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <RenameModal
            :open="renameModal"
            :role="selectedRole"
            @close="renameModal = false"
            @success="handleRoleUpdated($event)"
        />
        <DeleteRoleModal
            :role="selectedRole"
            :open="deleteModal"
            @close="deleteModal = false"
            @deleted="handleRoleDeleted"
        />
    </div>
</template>
