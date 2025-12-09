<?php

namespace App\Console\Commands;

use App\Enums\ReminderTypeEnum;
use App\Models\Reminder;
use App\Models\ReminderInstance;
use App\Models\Store;
use App\Traits\HandleTransactions;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AddStoreGlobalRemindersCommand extends Command
{
    use HandleTransactions;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stores:add-global-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add pre-defined global reminders to all stores';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->transact(function () {
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
                [
                    'reference_date_field' => 'renewal_date',
                    'title' => 'Reminder: Renewal Date',
                    'description' => 'Notify X days before the store’s renewal date.',
                    'days_before' => 14,
                    'notify_number' => 2,
                    'notify_unit' => 'weeks',
                    'type' => ReminderTypeEnum::Store()->value,
                    'is_enabled' => true,
                ],
                [
                    'reference_date_field' => 'contract_of_lease_end_date',
                    'title' => 'Reminder: Contract of Lease (Expiry Date)',
                    'description' => 'Notify X days before the store’s contract of lease end date.',
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

            // Re-populate missing ReminderInstance entries
            $stores = Store::all();

            $storeReminders = Reminder::where('type', ReminderTypeEnum::Store()->value)
                ->where('is_enabled', true)
                ->get();

            foreach ($stores as $store) {
                foreach ($storeReminders as $reminder) {
                    $alreadyExists = ReminderInstance::where('reminder_id', $reminder->id)
                        ->where('remindable_id', $store->id)
                        ->where('remindable_type', Store::class)
                        ->exists();

                    if (! $alreadyExists) {
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
                }
            }

            $this->info('Global reminders added successfully.');
        });
    }
}
