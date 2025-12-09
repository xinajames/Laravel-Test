<?php

namespace App\Services;

use App\Enums\ReminderTypeEnum;
use App\Models\Reminder;
use App\Models\ReminderInstance;
use App\Models\Store;
use App\Traits\HandleTransactions;
use InvalidArgumentException;

class ReminderService
{
    use HandleTransactions;

    public function fetchReminders()
    {
        return Reminder::all()->groupBy('type')->map(function ($reminders) {
            return $reminders->map(function ($reminder) {
                return [
                    'id' => $reminder->id,
                    'title' => $reminder->title,
                    'description' => $reminder->description,
                    'days_before' => $reminder->days_before,
                    'notify_number' => $reminder->notify_number,
                    'notify_unit' => $reminder->notify_unit,
                    'code' => $reminder->code,
                    'is_enabled' => $reminder->is_enabled,
                ];
            });
        });
    }

    public function toggleReminderStatus(int $reminderId, bool $enable = true)
    {
        return $this->transact(function () use ($reminderId, $enable) {
            $reminder = Reminder::findOrFail($reminderId);
            $reminder->is_enabled = $enable;
            $reminder->save();

            return true;
        });
    }

    public function updateDaysBefore(int $reminderId, int $value, string $unit)
    {
        return $this->transact(function () use ($reminderId, $value, $unit) {
            $reminder = Reminder::findOrFail($reminderId);

            $days = match (strtolower($unit)) {
                'days' => $value,
                'weeks' => $value * 7,
                'months' => $value * 30,
                'years' => $value * 365,
                default => throw new InvalidArgumentException("Unsupported unit: $unit"),
            };

            $reminder->update([
                'days_before' => $days,
                'notify_number' => $value,
                'notify_unit' => strtolower($unit),
            ]);

            return $reminder;
        });
    }

    public function generateStoreReminderInstance(Store $store): void
    {
        $this->transact(function () use ($store) {
            $reminders = Reminder::where('type', ReminderTypeEnum::Store()->value)
                ->where('is_enabled', true)
                ->get();

            foreach ($reminders as $reminder) {
                ReminderInstance::create([
                    'reminder_id' => $reminder->id,
                    'remindable_id' => $store->id,
                    'remindable_type' => Store::class,
                    'reference_date_field' => $reminder->reference_date_field,
                    'title' => $reminder->title,
                    'description' => $reminder->description,
                    'days_before' => $reminder->days_before,
                    'notify_number' => $reminder->notify_number,
                    'notify_unit' => $reminder->notify_unit,
                    'is_enabled' => true,
                ]);
            }
        });
    }
}
