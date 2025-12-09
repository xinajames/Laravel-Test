<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { router, usePage } from '@inertiajs/vue3';
import EditIcon from '@/Components/Icon/EditIcon.vue';
import TrashIcon from '@/Components/Icon/TrashIcon.vue';
import AddReminderModal from '@/Components/Modal/AddReminderModal.vue';
import EditReminderModal from '@/Components/Modal/EditReminderModal.vue';
import ConfirmationModal from '@/Components/Modal/ConfirmationModal.vue';

const props = defineProps({
    stores: {
        type: Array,
        default: () => [],
    },
    model_type: { type: String, default: null },
    model_id: { type: Number, required: false },
});

const modalOpen = ref(false);
const editModalOpen = ref(false);
const selectedReminder = ref(null);

const todayReminders = ref([]);
const upcomingReminders = ref([]);
const page = ref(1);
const hasMore = ref(true);
const isLoading = ref(false);

const canAddReminder = computed(() => {
    return (
        props.model_type === 'store' &&
        usePage().props.auth.permissions.includes('update-settings-notifications')
    );
});

const loadMoreTrigger = ref(null);
let observer = null;

const fetchTodayReminders = async () => {
    try {
        const url = props.model_id
            ? route('reminders.getTodayReminders', {
                  type: props.model_type,
                  id: props.model_id,
              })
            : route('reminders.getTodayReminders');

        const response = await axios.get(url);

        todayReminders.value = response.data.filter((r) => !r.dismissed && !r.deleted);
    } catch (error) {
        console.error('Failed to fetch today reminders:', error);
    }
};

const fetchUpcomingReminders = async (reset = false) => {
    if (reset) {
        upcomingReminders.value = [];
        page.value = 1;
        hasMore.value = true;
    }

    if (!hasMore.value || isLoading.value) return;
    isLoading.value = true;

    try {
        const url = props.model_id
            ? route('reminders.getUpcomingReminders', {
                  page: page.value,
                  type: props.model_type,
                  id: props.model_id,
              })
            : route('reminders.getUpcomingReminders', { page: page.value });

        const response = await axios.get(url);

        const data = response.data;

        if (data.current_page >= data.last_page) hasMore.value = false;
        upcomingReminders.value.push(...data.data.filter((r) => !r.deleted));
        page.value++;
    } catch (error) {
        console.error('Failed to fetch upcoming reminders:', error);
    } finally {
        isLoading.value = false;
    }
};

const initIntersectionObserver = async () => {
    await nextTick();
    if (!loadMoreTrigger.value) return;

    if (observer) observer.disconnect();

    observer = new IntersectionObserver(
        (entries) => {
            const [entry] = entries;
            if (entry.isIntersecting && hasMore.value) {
                fetchUpcomingReminders();
            }
        },
        {
            root: null,
            rootMargin: '0px',
            threshold: 1.0,
        }
    );

    observer.observe(loadMoreTrigger.value);
};

onMounted(async () => {
    await fetchTodayReminders();
    await fetchUpcomingReminders();
    await initIntersectionObserver();
});

onBeforeUnmount(() => {
    if (observer && loadMoreTrigger.value) {
        observer.unobserve(loadMoreTrigger.value);
    }
});

const reloadAll = async () => {
    await fetchTodayReminders();
    await fetchUpcomingReminders(true);
    await initIntersectionObserver();
};

const addReminder = (newReminder) => {
    router.post(
        route('reminders.store'),
        {
            title: newReminder.title,
            description: newReminder.description,
            scheduled_at: newReminder.date,
            model_type: newReminder.model_type,
            model_id: newReminder.model_id,
            is_custom: true,
        },
        {
            preserveScroll: true,
            onSuccess: () => reloadAll(),
            onError: (errors) => console.error('Error adding reminder:', errors),
        }
    );
};

const updateReminder = (updatedReminder) => {
    router.post(
        route('reminders.update', { reminderInstance: updatedReminder.id }),
        {
            title: updatedReminder.title,
            description: updatedReminder.description,
            scheduled_at: updatedReminder.date,
            type: updatedReminder.model_type,
            model_id: updatedReminder.model_id,
        },
        {
            preserveScroll: true,
            onSuccess: () => reloadAll(),
            onError: (errors) => console.error('Error updating reminder:', errors),
        }
    );
};

const editReminder = (reminder) => {
    selectedReminder.value = { ...reminder };
    editModalOpen.value = true;
};

const confirmationModal = reactive({
    open: false,
    header: null,
    message: null,
    icon: 'document',
    action_label: null,
    action: null,
});

function handleRemove(reminderId) {
    confirmationModal.header = 'Delete Reminder';
    confirmationModal.message =
        'Are you sure you want to delete this reminder? This action cannot be undone.';
    confirmationModal.icon = 'delete';
    confirmationModal.action_label = 'Delete';
    confirmationModal.action = route('reminders.delete', { reminderInstance: reminderId });
    confirmationModal.open = true;
}
</script>

<template>
    <div>
        <div class="flex justify-between items-center mb-4">
            <h5 class="text-xl font-bold"></h5>
            <button
                v-if="!model_type || canAddReminder"
                class="!bg-red-50 !text-[#A32130] py-2 px-4 rounded-lg text-sm font-medium shadow-sm"
                @click="modalOpen = true"
            >
                Add Reminder
            </button>
        </div>

        <!-- Today -->
        <div>
            <div v-if="todayReminders.length > 0">
                <div
                    v-for="reminder in todayReminders"
                    :key="reminder.id"
                    class="bg-white rounded-2xl flex gap-4 p-4 border border-gray-200 mb-4 hover:bg-gray-50 transition"
                >
                    <div class="flex items-stretch">
                        <div
                            class="p-2.5 rounded-2xl bg-rose-100 flex flex-col items-center justify-center w-[68px] h-full"
                        >
                            <p class="text-sm font-bold text-center">
                                {{ reminder.formatted_month }}
                            </p>
                            <p class="text-3xl font-bold text-center">
                                {{ reminder.formatted_day }}
                            </p>
                        </div>
                    </div>
                    <div class="flex-1 flex flex-col">
                        <h3 class="text-sm font-bold text-gray-900">{{ reminder.title }}</h3>
                        <p class="text-sm text-gray-500">{{ reminder.model_name }}</p>
                        <p class="mt-4 text-sm text-gray-900">{{ reminder.description }}</p>
                    </div>
                </div>
            </div>
            <div
                v-else
                class="flex flex-col items-center justify-center py-10 text-center bg-white rounded-2xl border border-gray-200"
            >
                <p class="text-gray-500">
                    No reminders scheduled for today. Add one to stay organized!
                </p>
            </div>
        </div>

        <!-- Upcoming -->
        <div class="mt-8">
            <div class="flex justify-between items-center mb-4">
                <p class="text-xl font-semibold">Upcoming</p>
            </div>

            <div v-if="upcomingReminders.length > 0">
                <div
                    v-for="reminder in upcomingReminders"
                    :key="reminder.id"
                    class="bg-white rounded-2xl flex gap-4 p-4 border border-gray-200 mb-4 hover:bg-gray-50 transition"
                >
                    <div class="flex items-stretch">
                        <div
                            class="p-2.5 rounded-2xl bg-gray-100 flex flex-col items-center justify-center w-[68px] h-full"
                        >
                            <p class="text-sm font-bold text-center">
                                {{ reminder.formatted_month }}
                            </p>
                            <p class="text-3xl font-bold text-center">
                                {{ reminder.formatted_day }}
                            </p>
                        </div>
                    </div>
                    <div class="flex-1 flex flex-col">
                        <p class="text-md font-bold text-gray-900">{{ reminder.title }}</p>
                        <p class="text-sm text-gray-500">{{ reminder.model_name }}</p>
                        <p class="text-sm text-gray-900 my-4">{{ reminder.description }}</p>
                        <p class="text-sm text-gray-500">
                            Scheduled on {{ reminder.formatted_date }}
                        </p>
                    </div>
                    <div class="flex items-center gap-6">
                        <div
                            class="flex items-center gap-1.5 text-primary font-medium text-sm cursor-pointer"
                            @click="editReminder(reminder)"
                        >
                            <EditIcon />
                            Edit
                        </div>
                        <div
                            class="flex items-center gap-1.5 text-primary font-medium text-sm cursor-pointer"
                            @click="handleRemove(reminder.id)"
                        >
                            <TrashIcon />
                            Remove
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="flex flex-col items-center justify-center py-10 text-center bg-white rounded-2xl border border-gray-200"
            >
                <p class="text-gray-500">No upcoming reminders. Stay ahead by adding one now!</p>
            </div>

            <div v-if="isLoading" class="flex justify-center py-6">
                <svg
                    class="animate-spin h-5 w-5 text-primary"
                    fill="none"
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>
                    <path class="opacity-75" d="M4 12a8 8 0 018-8v8H4z" fill="currentColor"></path>
                </svg>
            </div>

            <div ref="loadMoreTrigger" class="h-6 w-full"></div>
        </div>

        <!-- Modals -->
        <AddReminderModal
            :model_id="props.model_id"
            :model_type="props.model_type"
            :open="modalOpen"
            :stores="stores"
            @close="modalOpen = false"
            @add-reminder="addReminder"
        />
        <EditReminderModal
            :open="editModalOpen"
            :reminder="selectedReminder"
            @close="editModalOpen = false"
            @edit-reminder="updateReminder"
        />

        <ConfirmationModal
            :action="confirmationModal.action"
            :action_label="confirmationModal.action_label"
            :header="confirmationModal.header"
            :icon="confirmationModal.icon"
            :message="confirmationModal.message"
            :open="confirmationModal.open"
            @close="confirmationModal.open = false"
            @success="reloadAll"
        />
    </div>
</template>
