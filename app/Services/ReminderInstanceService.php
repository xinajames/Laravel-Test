<?php

namespace App\Services;

use App\Helpers\DateHelper;
use App\Models\Franchisee;
use App\Models\ReminderInstance;
use App\Models\Store;
use App\Notifications\ReminderDueNotification;
use App\Traits\HandleTransactions;
use App\Traits\ManageActivities;
use Carbon\Carbon;
use http\Exception\InvalidArgumentException;
use Illuminate\Support\Str;

class ReminderInstanceService
{
    use HandleTransactions;
    use ManageActivities;

    public function store(array $reminderData, $user = null)
    {
        $user = $user ?? auth()->user();

        return $this->transact(function () use ($reminderData, $user) {
            $remindableTypeMap = [
                'store' => Store::class,
                'franchisee' => Franchisee::class,
            ];

            if (isset($reminderData['model_type'], $reminderData['model_id'])
                && isset($remindableTypeMap[$reminderData['model_type']])) {
                $reminderData['remindable_type'] = $remindableTypeMap[$reminderData['model_type']];
                $reminderData['remindable_id'] = $reminderData['model_id'];
            }
            unset($reminderData['model_type'], $reminderData['model_id']);

            $reminder = ReminderInstance::create($reminderData);

            if ($reminder->remindable) {
                $this->log($reminder->remindable, 'reminders.store', $user);
            }

            return $reminder;
        });
    }

    public function delete(ReminderInstance $reminder, $user = null)
    {
        $user = $user ?? auth()->user();

        return $this->transact(function () use ($reminder, $user) {
            $reminder->delete();

            if ($reminder->remindable) {
                $this->log($reminder->remindable, 'reminders.delete', $user);
            }

            return $reminder;
        });
    }

    public function toggleStatus(int $reminderInstanceId, bool $enable = true)
    {
        return $this->transact(function () use ($reminderInstanceId, $enable) {
            $reminderInstance = ReminderInstance::findOrFail($reminderInstanceId);
            $reminderInstance->is_enabled = $enable;
            $reminderInstance->save();

            if ($reminderInstance->remindable) {
                $this->log($reminderInstance->remindable, 'reminders.toggleStatus');
            }

            return true;
        });
    }

    public function updateDaysBefore(int $reminderInstanceId, int $value, string $unit)
    {
        return $this->transact(function () use ($reminderInstanceId, $value, $unit) {
            $reminderInstance = ReminderInstance::findOrFail($reminderInstanceId);

            $days = match (strtolower($unit)) {
                'days' => $value,
                'weeks' => $value * 7,
                'months' => $value * 30,
                'years' => $value * 365,
                default => throw new InvalidArgumentException("Unsupported unit: $unit"),
            };

            $reminderInstance->update([
                'days_before' => $days,
                'notify_number' => $value,
                'notify_unit' => strtolower($unit),
                'is_custom' => true,
            ]);

            if ($reminderInstance->remindable) {
                $this->log($reminderInstance->remindable, 'reminders.update');
            }

            return $reminderInstance;
        });
    }

    public function update(ReminderInstance $reminder, array $reminderData, $user = null)
    {
        $user = $user ?? auth()->user();

        return $this->transact(function () use ($reminder, $reminderData, $user) {
            // Handle potential change in model reference
            $remindableTypeMap = [
                'store' => Store::class,
                'franchisee' => Franchisee::class,
            ];

            if (isset($reminderData['type'], $reminderData['model_id'])
                && isset($remindableTypeMap[$reminderData['type']])) {
                $reminderData['remindable_type'] = $remindableTypeMap[$reminderData['type']];
                $reminderData['remindable_id'] = $reminderData['model_id'];
            }
            unset($reminderData['type'], $reminderData['model_id']);

            $reminder->update($reminderData);

            if ($reminder->remindable) {
                $this->log($reminder->remindable, 'reminders.update', $user);
            }

            return $reminder;
        });
    }

    public function getStoreNotifications($id)
    {
        $notifications = ReminderInstance::whereNotNull('reminder_id')
            ->where('remindable_type', Store::class)
            ->where('remindable_id', $id)
            ->get();

        return $notifications->map(function ($notification) {
            $storeField = $notification->reference_date_field;
            $store = Store::find($notification->remindable_id);

            return [
                'id' => $notification->id,
                'title' => $notification->title,
                'description' => $notification->description,
                'days_before' => $notification->days_before,
                'notify' => [
                    'value' => $notification->notify_number,
                    'unit' => $notification->notify_unit ? Str::ucfirst($notification->notify_unit) : null,
                ],
                'code' => $notification->code,
                'is_enabled' => (bool) $notification->is_enabled,
                'is_custom' => $notification->is_custom,
                'date' => $store->{$storeField} ? DateHelper::changeDateFormat($store->{$storeField}) : null,
            ];
        });
    }

    public function getUpcomingReminders($type = null, $id = null)
    {
        $today = Carbon::today()->toDateString();

        $query = ReminderInstance::whereNull('reminder_id')
            ->where('is_custom', true)
            ->whereDate('scheduled_at', '<>', $today)
            ->orderBy('scheduled_at', 'asc');

        // Optional: filter by type and model_id
        $remindableTypeMap = [
            'store' => Store::class,
            'franchisee' => Franchisee::class,
        ];

        if ($type && $id && isset($remindableTypeMap[$type])) {
            $query->where('remindable_type', $remindableTypeMap[$type])
                ->where('remindable_id', $id);
        }

        $reminders = $query->paginate(5);

        $reminders->getCollection()->transform(function ($reminder) {
            return $this->formatReminder($reminder);
        });

        return $reminders;
    }

    private function formatReminder($reminder)
    {
        $reminder = $reminder->toArray();

        if ($reminder['remindable_type'] === Store::class) {
            $store = Store::find($reminder['remindable_id']);
            $reminder['model_name'] = $store ? $store->jbs_name : 'Unknown Store';
        } else {
            $reminder['model_name'] = 'Reminder';
        }

        if (isset($reminder['scheduled_at'])) {
            $scheduledAt = Carbon::parse($reminder['scheduled_at']);

            $reminder['formatted_date'] = $scheduledAt->format('F d, Y');
            $reminder['formatted_month'] = strtoupper($scheduledAt->format('M'));
            $reminder['formatted_day'] = $scheduledAt->format('d');
        }

        return $reminder;
    }

    public function getTodayReminders($type = null, $id = null)
    {
        $today = Carbon::today()->toDateString();

        $query = ReminderInstance::whereNull('reminder_id')
            ->where('is_custom', true)
            ->whereDate('scheduled_at', $today)
            ->orderBy('scheduled_at', 'asc');

        // Optional: filter by type and model_id
        $remindableTypeMap = [
            'store' => Store::class,
            'franchisee' => Franchisee::class,
        ];

        if ($type && $id && isset($remindableTypeMap[$type])) {
            $query->where('remindable_type', $remindableTypeMap[$type])
                ->where('remindable_id', $id);
        }

        $reminders = $query->get();

        return $reminders->map(function ($reminder) {
            return $this->formatReminder($reminder);
        });
    }

    public function getTodayRemindersCount($type = null, $id = null)
    {
        $today = Carbon::today()->toDateString();

        $query = ReminderInstance::whereNull('reminder_id')
            ->where('is_custom', true)
            ->whereDate('scheduled_at', $today)
            ->whereNull('deleted_at');

        // Optional: filter by type and model_id
        $remindableTypeMap = [
            'store' => Store::class,
            'franchisee' => Franchisee::class,
        ];

        if ($type && $id && isset($remindableTypeMap[$type])) {
            $query->where('remindable_type', $remindableTypeMap[$type])
                ->where('remindable_id', $id);
        }

        return $query->count();
    }

    public function getNotificationReminders($type = null, $id = null)
    {
        $today = Carbon::today();
        $remindableTypeMap = [
            'store' => Store::class,
            'franchisee' => Franchisee::class,
        ];

        $query = ReminderInstance::whereNotNull('reminder_id')
            ->whereNotNull('days_before')
            ->whereNotNull('reference_date_field')
            ->where('is_enabled', true)
            ->orderBy('scheduled_at', 'asc');

        if ($type && $id && isset($remindableTypeMap[$type])) {
            $query->where('remindable_type', $remindableTypeMap[$type])
                ->where('remindable_id', $id);
        }

        $reminders = $query->get();

        return $reminders->filter(function ($reminder) use ($today) {
            $model = $reminder->remindable;
            $field = $reminder->reference_date_field;

            // Ensure model exists, field is valid, and the value is not null/empty
            if (
                ! $model ||
                ! $field ||
                ! array_key_exists($field, $model->getAttributes()) ||
                empty($model->{$field})
            ) {
                return false;
            }

            $targetDate = Carbon::parse($model->{$field});
            $notifyDate = $targetDate->copy()->subDays($reminder->days_before);

            return $today->greaterThanOrEqualTo($notifyDate);
        })->map(function ($reminder) {
            return $this->formatReminder($reminder);
        });
    }

    public function notify(ReminderInstance $reminderInstance): void
    {
        $this->transact(function () use ($reminderInstance) {
            $notification = new ReminderDueNotification($reminderInstance);

            // TODO :: Finalized users to be notified
            $users = NotificationService::getUserAdmins('super-admin');

            foreach ($users as $user) {
                NotificationService::handleNotification($user, $notification);
            }

            $reminderInstance->update([
                'notified_at' => $reminderInstance->notified_at ?? now(),
                'last_notified_at' => now(),
            ]);
        });
    }
}
