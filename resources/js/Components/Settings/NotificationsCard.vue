<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

import ToggleSwitch from '@/Components/Common/Toggle/ToggleSwitch.vue';
import DropdownSelect from '@/Components/Common/Select/DropdownSelect.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';

const props = defineProps({
    notificationSettings: Object,
});

const durations = [1, 2, 3, 4, 5, 6, 7, 8].map((val) => ({ value: val }));
const durationTypes = ['Days', 'Weeks', 'Months', 'Years'].map((val) => ({ value: val }));

const canUpdate = computed(() => {
    return usePage().props.auth.permissions.includes('update-settings-notifications');
});

// Transform the raw object into a grouped array format
const notifications = computed(() => {
    return Object.entries(props.notificationSettings).map(([section, items]) => ({
        section: section.charAt(0).toUpperCase() + section.slice(1),
        items: items.map((item) => ({
            ...item,
            enabled: !!item.is_enabled,
            notify: {
                value: Number(item.notify_number || 1),
                unit: ['days', 'weeks', 'months', 'years'].includes(item.notify_unit?.toLowerCase())
                    ? item.notify_unit.charAt(0).toUpperCase() +
                      item.notify_unit.slice(1).toLowerCase()
                    : 'Days',
            },
        })),
    }));
});

const handleReminderNotifyUpdate = async (item) => {
    try {
        await router.post(
            route('settings.updateNotificationDuration', { reminderId: item.id }),
            {
                value: item.notify.value,
                unit: item.notify.unit.toLowerCase(),
            },
            {
                preserveScroll: true,
            }
        );
    } catch (error) {
        console.error('Error updating notification duration:', error);
    }
};

const handleToggleChange = async (value, item) => {
    try {
        await router.post(
            route('settings.toggleNotification', { reminderId: item.id }),
            {
                enabled: value,
            },
            {
                preserveScroll: true,
            }
        );
    } catch (error) {
        console.error('Error :', error);
    }
};
</script>

<template>
    <div class="p-4 sm:p-6 md:p-8 space-y-6">
        <div
            v-for="(group, gIndex) in notifications"
            :key="gIndex"
            class="bg-white rounded-2xl p-4 sm:p-6 border border-gray-200 space-y-6"
        >
            <h5 class="text-xl font-semibold">{{ group.section }}</h5>

            <div
                v-for="(item, index) in group.items"
                :key="item.code"
                :class="index !== group.items.length - 1 ? 'border-b border-gray-200' : ''"
                class="pb-6"
            >
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                    <!-- Toggle Switch - Enables/Disables Notification -->
                    <ToggleSwitch
                        :disabled="!canUpdate"
                        :model-value="item.enabled"
                        enabled-color="bg-primary"
                        @update:modelValue="(value) => handleToggleChange(value, item)"
                    />

                    <div class="flex flex-col w-full space-y-2">
                        <!-- Title -->
                        <div class="flex flex-wrap justify-between w-full gap-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <h5 class="text-base font-semibold">{{ item.title }}</h5>
                            </div>
                        </div>

                        <!-- Notify Settings -->
                        <div
                            v-if="item.enabled"
                            class="px-4 py-3 bg-gray-50 mt-2 rounded-xl flex flex-wrap sm:flex-nowrap gap-2 items-center"
                        >
                            <div class="flex items-center gap-2 min-w-[160px]">
                                <span class="text-sm font-medium text-gray-600">Notify</span>
                                <DropdownSelect
                                    v-model="item.notify.value"
                                    custom-class="w-[80px] sm:w-[100px]"
                                    :value="item.notify.value"
                                >
                                    <option v-for="d in durations" :key="d.value" :value="d.value">
                                        {{ d.value }}
                                    </option>
                                </DropdownSelect>
                            </div>

                            <div class="flex items-center gap-2 min-w-[180px]">
                                <DropdownSelect
                                    v-model="item.notify.unit"
                                    custom-class="w-[120px] sm:w-[140px]"
                                    :value="item.notify.unit"
                                >
                                    <option
                                        v-for="t in durationTypes"
                                        :key="t.value"
                                        :value="t.value"
                                    >
                                        {{ t.value }}
                                    </option>
                                </DropdownSelect>
                                <span class="text-sm font-medium text-gray-600">before</span>
                            </div>

                            <div v-if="canUpdate" class="ml-auto">
                                <PrimaryButton
                                    class="px-6"
                                    @click="handleReminderNotifyUpdate(item)"
                                >
                                    Save
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
