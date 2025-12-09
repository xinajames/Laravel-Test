<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import Breadcrumbs from '@/Components/Shared/Breadcrumbs.vue';
import Layout from '@/Layouts/Admin/Layout.vue';
import TeamHeader from '@/Components/Settings/Show/TeamHeader.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import StatusBadge from '@/Components/Common/Badge/StatusBadge.vue';
import ChangeRoleModal from '@/Components/Modal/ChangeRoleModal.vue';
import ConfirmationModal from '@/Components/Modal/ConfirmationModal.vue';

const props = defineProps({
    team: Object,
});

const changeRoleModal = ref(false);

const confirmationModals = reactive({
    deactivate: {
        open: false,
        header: 'Deactivate Team Member',
        message:
            'Are you sure you want to deactivate this team member? This will temporarily revoke their access to the application. You can reactivate them at any time.',
        icon: 'deactivate',
        action_label: 'Deactivate',
        action: '',
    },
    reactivate: {
        open: false,
        header: 'Reactivate Team Member',
        message:
            'This will restore the team member’s access to the application. Proceed with reactivation?',
        icon: 'reactivate',
        action_label: 'Reactivate',
        action: '',
    },
});

function openModal(type) {
    switch (type) {
        case 'deactivate':
            confirmationModals.deactivate.action = route('teams.deactivate', props.team.id);
            break;
        case 'reactivate':
            confirmationModals.reactivate.action = route('teams.activate', props.team.id);
            break;
    }
    confirmationModals[type].open = true;
}

function handleSuccess(type) {
    confirmationModals[type].open = false;
}

function edit() {
    router.visit(route('teams.edit', props.team.id));
}

const canUpdateTeam = computed(() => {
    return usePage().props.auth.permissions.includes('update-team');
});
</script>

<template>
    <Head title="Teams" />

    <Layout :content-no-padding="true">
        <template #header>
            <TeamHeader :team="team" />
        </template>

        <div class="p-8 space-y-8">
            <div class="bg-white rounded-2xl p-6">
                <div class="space-y-3 divide-y divide-gray-200">
                    <div class="grid grid-cols-3 gap-4 items-center pt-3">
                        <h5 class="font-medium text-gray-500">Full Name</h5>
                        <div class="col-span-2 flex justify-between items-center">
                            <h5 class="text-gray-900">{{ team.name }}</h5>
                            <SecondaryButton
                                v-if="canUpdateTeam"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="edit"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4 items-center pt-3">
                        <h5 class="font-medium text-gray-500">Email Address</h5>
                        <div class="col-span-2 flex justify-between items-center">
                            <h5 class="text-gray-900">{{ team.email }}</h5>
                            <SecondaryButton
                                v-if="canUpdateTeam"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="edit"
                            >
                                Edit
                            </SecondaryButton>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4 items-center pt-3">
                        <h5 class="font-medium text-gray-500">Role</h5>
                        <div class="col-span-2 flex justify-between items-center">
                            <h5 class="text-gray-900">{{ team.admin_type }}</h5>
                            <SecondaryButton
                                v-if="canUpdateTeam"
                                class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                @click="changeRoleModal = true"
                            >
                                Change Role
                            </SecondaryButton>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4 items-center pt-3">
                        <h5 class="font-medium text-gray-500">Status</h5>
                        <div class="col-span-2 flex justify-between items-center">
                            <StatusBadge
                                :type="team.status_label"
                                category="userStatus"
                                class="!rounded-full [&_svg]:hidden"
                            >
                                {{ team.status_label }}
                            </StatusBadge>
                            <div v-if="canUpdateTeam">
                                <div v-if="team.status === 1">
                                    <SecondaryButton
                                        class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                        @click="openModal('deactivate')"
                                    >
                                        Deactivate
                                    </SecondaryButton>
                                </div>
                                <div v-else>
                                    <SecondaryButton
                                        class="!ring-transparent !shadow-none !px-0 !py-0 !font-medium !text-[#A32130]"
                                        @click="openModal('reactivate')"
                                    >
                                        Reactivate
                                    </SecondaryButton>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ChangeRoleModal
            :open="changeRoleModal"
            :role-id="team.user_role_id"
            :user-id="team.id"
            @close="changeRoleModal = false"
        />

        <ConfirmationModal
            v-for="(modal, key) in confirmationModals"
            :key="key"
            :action_label="modal.action_label"
            :header="modal.header"
            :icon="modal.icon"
            :message="modal.message"
            :open="modal.open"
            :action="modal.action"
            @close="modal.open = false"
            @success="handleSuccess(key)"
        />
    </Layout>

    <Teleport to="#portal-breadcrumb">
        <Breadcrumbs
            :level1="{ name: 'Teams', route: 'teams' }"
            :level2="{ name: team.name, route: 'teams.show', route_id: team.id }"
            :levels="2"
        />
    </Teleport>
</template>
