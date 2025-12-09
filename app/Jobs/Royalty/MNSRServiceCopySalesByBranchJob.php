<?php

namespace App\Jobs\Royalty;

use App\Enums\MacroBatchStatusEnum;
use App\Models\Royalty\MacroBatch;
use App\Services\Royalty\MNSRServiceCopySalesByBranch;
use App\Traits\ErrorLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Exception;

class MNSRServiceCopySalesByBranchJob implements ShouldQueue
{
    use Dispatchable, ErrorLogger, InteractsWithQueue, Queueable, SerializesModels;

    protected int $batchId;

    public function __construct(int $batchId)
    {
        $this->batchId = $batchId;
        $this->queue = 'royalty';
    }

    public function handle(): void
    {
        try {
            $service = new MNSRServiceCopySalesByBranch;
            $result = $service->processData($this->batchId);
        } catch (Exception $e) {
            // Log error to batch immediately
            $this->logErrorToMacroBatch(
                $this->batchId,
                $e,
                "MNSRServiceCopySalesByBranchJob::handle - Sales branch processing failed for batch ID: {$this->batchId}",
                'critical'
            );

            // Update batch status to failed immediately - use fresh query to ensure persistence
            MacroBatch::where('id', $this->batchId)->update(['status' => MacroBatchStatusEnum::Failed()->value]);

            // Throw to mark job as failed
            throw $e;
        }
    }
}
