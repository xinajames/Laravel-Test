<?php

namespace App\Jobs;

use App\Models\ReminderInstance;
use App\Services\ReminderInstanceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessReminderNotificationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private ReminderInstance $reminder
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        app(ReminderInstanceService::class)->notify($this->reminder);
    }
}
