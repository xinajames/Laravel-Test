<?php

namespace App\Jobs;

use App\Enums\UserReportRequestStatusEnum;
use App\Models\UserReportRequest;
use App\Notifications\GenerateReportNotification;
use App\Services\NotificationService;
use App\Services\UserReportRequestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private int $user_report_request_id;

    /**
     * Create a new job instance.
     */
    public function __construct($user_report_request_id)
    {
        $this->user_report_request_id = $user_report_request_id;
        $this->queue = 'generate-report';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $request = UserReportRequest::find($this->user_report_request_id);

        if (! $request) {
            return; // Exit if the report request no longer exists
        }

        // Proceed only if status is Ongoing
        if ((int) $request->status !== (int) UserReportRequestStatusEnum::Ongoing()->value) {
            Log::info("Skipping report ID {$request->id}: not marked as Ongoing.");

            return;
        }

        // Generate the report
        app(UserReportRequestService::class)->generateReport($request);
    }

    public function failed(Throwable $exception): void
    {
        $report = UserReportRequest::find($this->user_report_request_id);

        if (! $report) {
            return;
        }

        $report->status = UserReportRequestStatusEnum::Failed()->value;
        $report->save();

        $notification = new GenerateReportNotification($report);
        NotificationService::handleNotification($report->user, $notification);
    }
}
