<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { Cog8ToothIcon, GlobeAltIcon } from '@heroicons/vue/24/outline';
import axios from 'axios';

import ConfirmationModal from '@/Components/Modal/ConfirmationModal.vue';
import DropdownSelect from '@/Components/Common/Select/DropdownSelect.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import ToggleSwitch from '@/Components/Common/Toggle/ToggleSwitch.vue';

const props = defineProps({
    store: Object,
});

const notifications = ref([]);

const confirmationModal = reactive({
    action: null,
    open: false,
    data: null,
    header: 'Change Notification Settings',
    message:
        "This notification currently follows the default universal settings. Editing it will apply only to this store and won’t affect other stores' settings.",
    icon: 'notification',
    action_label: 'Proceed',
    selectedItem: null,
});

const canUpdate = computed(() => {
    return usePage().props.auth.permissions.includes('update-stores-notifications-reminders');
});

const durations = [1, 2, 3, 4, 5, 6, 7, 8].map((val) => ({ value: val }));
const durationTypes = ['Days', 'Weeks', 'Months', 'Years'].map((val) => ({ value: val }));

const handleToggleChange = async (value, item) => {
    try {
        await router.post(
            route('reminders.toggleStatus', item.id),
            {
                enabled: value,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    fetchNotifications();
                },
            }
        );
    } catch (error) {
        console.error('Error :', error);
    }
};

const confirmToggle = () => {
    confirmationModal.open = false;
};

const fetchNotifications = async () => {
    try {
        const url = route('stores.getStoreNotification', props.store.id);

        const response = await axios.get(url);

        notifications.value = response.data;
    } catch (error) {
        console.error('Failed to fetch store notifications :', error);
    }
};

const submit = async (item) => {
    confirmationModal.open = true;
    confirmationModal.action = route('reminders.updateNotificationDuration', item.id);
    confirmationModal.data = {
        value: item.notify.value,
        unit: item.notify.unit.toLowerCase(),
    };
};

onMounted(() => {
    fetchNotifications();
});
</script>

<template>
    <div class="p-4 sm:p-6 md:p-8 space-y-6">
        <p class="text-xl font-semibold">Notifications</p>

        <div
            v-if="notifications && notifications.length > 0"
            class="bg-white rounded-2xl p-4 sm:p-6 border border-gray-200 space-y-6"
        >
            <div
                v-for="(item, index) in notifications"
                :key="index"
                :class="[
                    'pb-6',
                    index !== notifications.length - 1 ? 'border-b border-gray-200' : '',
                ]"
            >
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                    <!-- Toggle Switch - Enables/Disables Notification -->
                    <ToggleSwitch
                        :disabled="!canUpdate"
                        :model-value="item.is_enabled"
                        enabled-color="bg-primary"
                        @update:modelValue="(value) => handleToggleChange(value, item)"
                    />

                    <div class="flex flex-col w-full space-y-2 sm:space-y-0">
                        <div
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between w-full gap-2 pb-2"
                        >
                            <div class="flex flex-wrap items-center gap-2">
                                <h5 class="text-base font-semibold">{{ item.title }}</h5>
                                <span class="text-gray-700">-</span>
                                <p v-if="item.date" class="text-sm text-gray-700">
                                    {{ item.date }}
                                </p>
                                <p v-else class="text-sm text-gray-500">Date not found</p>
                            </div>
                            <div class="flex items-center gap-2 sm:gap-4">
                                <p
                                    v-if="item.is_custom"
                                    class="px-2.5 py-0.5 bg-pink-50 rounded-full text-pink-800 font-medium text-sm flex items-center gap-1"
                                >
                                    Custom
                                    <Cog8ToothIcon class="w-4 h-4 text-pink-800 inline-block" />
                                </p>
                                <p
                                    v-else
                                    class="px-2.5 py-0.5 bg-purple-50 rounded-full text-purple-800 font-medium text-sm flex items-center gap-1"
                                >
                                    Universal
                                    <GlobeAltIcon class="w-4 h-4 text-purple-800 inline-block" />
                                </p>
                            </div>
                        </div>

                        <!-- Show Notify Settings Only If Enabled -->
                        <div
                            v-if="item.is_enabled"
                            class="px-4 py-3 bg-gray-50 mt-3 rounded-xl flex flex-col sm:flex-row sm:items-center sm:gap-4 space-y-2 sm:space-y-0"
                        >
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-semibold text-gray-600">Notify</p>
                                <DropdownSelect
                                    v-model="item.notify.value"
                                    :required="true"
                                    :value="item.notify.value"
                                    custom-class="border-gray-300 w-[70px] sm:w-[60px]"
                                >
                                    <option
                                        v-for="(duration, dIndex) in durations"
                                        :key="dIndex"
                                        :value="duration.value"
                                    >
                                        {{ duration.value }}
                                    </option>
                                </DropdownSelect>
                            </div>

                            <div class="flex items-center gap-2">
                                <DropdownSelect
                                    v-model="item.notify.unit"
                                    :required="true"
                                    :value="item.notify.unit"
                                    custom-class="border-gray-300 w-[140px] sm:w-[115px]"
                                >
                                    <option
                                        v-for="(type, tIndex) in durationTypes"
                                        :key="tIndex"
                                        :value="type.value"
                                    >
                                        {{ type.value }}
                                    </option>
                                </DropdownSelect>
                                <p class="text-sm font-semibold text-gray-600">before</p>
                            </div>
                            <div v-if="canUpdate" class="ml-auto">
                                <PrimaryButton class="px-6" @click="submit(item)">
                                    Save
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div
            v-else
            class="flex flex-col items-center justify-center py-10 text-center bg-white rounded-2xl border border-gray-200"
        >
            <p class="text-gray-500">No notifications found yet — check back soon!</p>
        </div>

        <ConfirmationModal
            :action="confirmationModal.action"
            :action_label="confirmationModal.action_label"
            :data="confirmationModal.data"
            :header="confirmationModal.header"
            :icon="confirmationModal.icon"
            :message="confirmationModal.message"
            :open="confirmationModal.open"
            @close="confirmationModal.open = false"
            @success="confirmToggle"
        />
    </div>
</template>
