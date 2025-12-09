<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted, reactive, ref, watch } from 'vue';

import Breadcrumbs from '@/Components/Shared/Breadcrumbs.vue';
import Layout from '@/Layouts/Admin/Layout.vue';
import Avatar from '@/Components/Common/Avatar/Avatar.vue';
import TextInput from '@/Components/Common/Input/TextInput.vue';
import SearchInputDropdown from '@/Components/Common/Select/SearchInputDropdown.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SingleFileUpload from '@/Components/Common/File/SingleFileUpload.vue';
import StatusBadge from '@/Components/Common/Badge/StatusBadge.vue';
import ConfirmationModal from '@/Components/Modal/ConfirmationModal.vue';

const props = defineProps({
    team: Object,
});

const form = useForm({
    photo: props.team.profile_photo_url,
    name: props.team.name,
    email: props.team.email,
    user_role_id: props.team.user_role_id,
});

const roles = ref([]);
const selectedRole = ref(null);

const profilePhotoPreview = ref(null);
const uploadError = ref(null);

const confirmationModal = reactive({
    action: route('teams.update', props.team.id),
    open: false,
    header: 'Edit Team Member',
    message:
        'Are you sure you want to edit this team member? Updating the email address will reset the user account, and changing roles may affect access permissions. Please confirm to proceed.',
    icon: 'information',
    action_label: 'Save Changes',
});

const handleUpdateRole = (value) => {
    selectedRole.value = value;
    form.user_role_id = value?.value ?? null;
};

function handlePhoto(file) {
    let validFileTypes = ['image/jpg', 'image/jpeg', 'image/png'];
    if (!validFileTypes.includes(file.type)) {
        uploadError.value = 'Invalid file type. Please upload a .png, .jpeg, or .jpg file.';
    } else {
        form.photo = file;
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (e) => {
            profilePhotoPreview.value = e.target.result;
        };
        // handleUpdate(props.page === 'update' ? 'profile_photo' : null);
    }
}

function getRoles() {
    let url = route('userRoles.getDataList');
    axios.get(url).then((response) => {
        roles.value = response.data.map((role) => ({
            value: role.id,
            label: role.name,
            members: role.membersCount,
        }));

        const initialRole = roles.value.find((role) => role.value === props.team.user_role_id);
        selectedRole.value = initialRole;
        form.user_role_id = initialRole ? initialRole.value : null;
    });
}

watch(
    () => props.team,
    (value) => {
        profilePhotoPreview.value = value.profile_photo_url;
    },
    { immediate: true }
);

onMounted(() => {
    getRoles();
});
</script>

<template>
    <Head title="Edit Team" />

    <Layout :content-no-padding="true">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="p-6 sm:p-8 space-y-6">
                <p class="text-lg sm:text-xl font-semibold">Team Member Details</p>

                <div class="bg-white rounded-2xl p-6 space-y-6 shadow-sm">
                    <!-- Photo Upload Section -->
                    <div class="space-y-3">
                        <p class="text-sm font-medium">Photo</p>
                        <div class="flex flex-col sm:flex-row gap-4 sm:items-center">
                            <Avatar
                                :image-url="profilePhotoPreview"
                                custom-class="w-16 h-16 sm:w-[64px] sm:h-[64px]"
                                image-class="w-full h-full rounded-full object-cover"
                            />
                            <SingleFileUpload
                                v-model="profilePhotoPreview"
                                :button-text="form.photo ? 'Change' : 'Upload'"
                                :required="!profilePhotoPreview"
                                button-class="!py-2 !rounded-md !text-sm !font-medium !relative !cursor-pointer"
                                file-types=".jpg,.jpeg,.png"
                                type="button"
                                @uploaded="handlePhoto($event)"
                            />
                        </div>
                    </div>

                    <!-- Name Input -->
                    <TextInput
                        v-model="form.name"
                        input-class="!border-gray-300 w-full"
                        label="Name"
                    />

                    <!-- Email Input -->
                    <TextInput
                        v-model="form.email"
                        input-class="!border-gray-300 w-full"
                        label="Email Address"
                    />

                    <!-- Dropdowns for Roles -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <SearchInputDropdown
                            :dataList="roles"
                            :modelValue="selectedRole ? selectedRole.label : ''"
                            class="w-full"
                            label="Role"
                            @update-data="handleUpdateRole"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="w-full">
                            <p class="text-gray-900">
                                Current Status:
                                <StatusBadge
                                    :type="team.status_label"
                                    category="userStatus"
                                    class="!rounded-full [&_svg]:hidden"
                                >
                                    {{ team.status_label }}
                                </StatusBadge>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="flex justify-start">
                    <PrimaryButton class="w-full sm:w-auto" @click="confirmationModal.open = true">
                        Save Changes
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </Layout>

    <ConfirmationModal
        :action="confirmationModal.action"
        :action_label="confirmationModal.action_label"
        :data="form"
        :header="confirmationModal.header"
        :icon="confirmationModal.icon"
        :message="confirmationModal.message"
        :open="confirmationModal.open"
        @close="confirmationModal.open = false"
    />

    <Teleport to="#portal-breadcrumb">
        <Breadcrumbs
            :level1="{ name: 'Teams', route: 'teams' }"
            :level2="{ name: team.name, route: 'teams.show', route_id: team.id }"
            :level3="{ name: 'Edit All Details', route: 'teams.edit', route_id: team.id }"
            :levels="3"
        />
    </Teleport>
</template>
