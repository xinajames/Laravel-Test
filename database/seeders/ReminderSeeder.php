<?php

namespace Database\Seeders;

use App\Enums\ReminderTypeEnum;
use App\Models\Reminder;
use App\Models\ReminderInstance;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ReminderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Global Notification Settings
        $reminders = [
            [
                'reference_date_field' => 'target_opening_date',
                'title' => 'Reminder: Target Opening Date',
                'description' => 'Notify X days before the store’s target opening date.',
                'days_before' => 14,
                'notify_number' => 2,
                'notify_unit' => 'weeks',
                'type' => ReminderTypeEnum::Store()->value,
                'is_enabled' => true,
            ],
        ];

        foreach ($reminders as $data) {
            $data['code'] = Str::slug($data['title']);
            Reminder::firstOrCreate(['code' => $data['code']], $data);
        }

        // Store-specific Reminders
        $stores = Store::all();

        $storeReminders = Reminder::where('type', ReminderTypeEnum::Store()->value)->where('is_enabled', true)->get();

        foreach ($stores as $store) {
            // From Global Reminders
            foreach ($storeReminders as $reminder) {
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

            // Specific Reminders
            $count = rand(0, 4);
            for ($i = 0; $i < $count; $i++) {
                ReminderInstance::create([
                    'reminder_id' => null,
                    'remindable_id' => $store->id,
                    'remindable_type' => Store::class,
                    'title' => 'Custom Reminder '.Str::random(5),
                    'description' => 'Auto-seeded custom reminder for store '.$store->jbs_name,
                    'days_before' => null,
                    'is_enabled' => true,
                    'is_custom' => true,
                    'scheduled_at' => Carbon::now()->addDays(rand(5, 60)),
                ]);
            }
        }
    }
}
