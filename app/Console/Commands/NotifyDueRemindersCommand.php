<?php

namespace App\Console\Commands;

use App\Jobs\ProcessReminderNotificationJob;
use App\Models\ReminderInstance;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NotifyDueRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:notify-due';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all due ReminderInstances and trigger notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Type 1: Store Notifications
        $dueStoreNotifications = ReminderInstance::whereNotNull('reminder_id')
            ->whereNotNull('days_before')
            ->whereNotNull('reference_date_field')
            ->where('is_enabled', true)
            ->whereNull('notified_at')
            ->get()
            ->filter(function ($reminder) {
                $model = $reminder->remindable;
                $field = $reminder->reference_date_field;

                if (
                    ! $model ||
                    ! $field ||
                    ! array_key_exists($field, $model->getAttributes()) ||
                    empty($model->{$field})) {
                    return false;
                }

                $targetDate = Carbon::parse($model->{$field});
                $notifyDate = $targetDate->copy()->subDays($reminder->days_before);

                return now()->greaterThanOrEqualTo($notifyDate); // Due or overdue date check
            });

        // Type 2: Store Reminders
        $dueStoreReminders = ReminderInstance::whereNull('reminder_id')
            ->where('is_enabled', true)
            ->whereDate('scheduled_at', now()->toDateString())
            ->get();

        $reminders = $dueStoreNotifications->merge($dueStoreReminders);
        foreach ($reminders as $reminder) {
            ProcessReminderNotificationJob::dispatch($reminder);
        }
    }
}
