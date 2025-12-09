<?php

namespace App\Console\Commands;

use App\Enums\UserReportRequestStatusEnum;
use App\Jobs\GenerateReportJob;
use App\Models\UserReportRequest;
use Illuminate\Console\Command;

class GeneratePendingReportCommand extends Command
{
    protected $signature = 'generate:reports';

    protected $description = 'Process one pending report request at a time';

    public function handle(): int
    {
        // Check if there's already an ongoing report
        $hasOngoing = UserReportRequest::where('status', UserReportRequestStatusEnum::Ongoing()->value)->exists();

        if ($hasOngoing) {
            $this->info('Ongoing report found. Skipping.');

            return Command::SUCCESS;
        }

        // Get the next pending report
        $pending = UserReportRequest::where('status', UserReportRequestStatusEnum::Pending()->value)
            ->orderBy('created_at')
            ->first();

        if (! $pending) {
            $this->info('No pending reports.');

            return Command::SUCCESS;
        }

        // Mark as ongoing to lock it
        $pending->update([
            'status' => UserReportRequestStatusEnum::Ongoing()->value,
            'attempts' => 1,
        ]);

        // Dispatch job
        dispatch(new GenerateReportJob($pending->id));

        $this->info("Dispatched report job for request ID {$pending->id}");

        return Command::SUCCESS;
    }
}
