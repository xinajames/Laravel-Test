<?php

namespace App\Jobs\Royalty;

use App\Enums\MacroBatchStatusEnum;
use App\Models\Royalty\MacroBatch;
use App\Services\Royalty\MNSRServiceCopySalesByBranch;
use App\Services\Royalty\MNSRServiceCopySalesByStore;
use App\Traits\ErrorLogger;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateSalesHistoryJob implements ShouldQueue
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
            // Update batch status to Ongoing at the start if still Pending
            $batch = MacroBatch::find($this->batchId);
            if ($batch && $batch->status == MacroBatchStatusEnum::Pending()->value) {
                $batch->status = MacroBatchStatusEnum::Ongoing()->value;
                $batch->save();
            }

            // Verify the batch exists
            $batch = MacroBatch::findOrFail($this->batchId);

            // Step 1: Execute MNSRServiceCopySalesByBranch FIRST
            $branchService = app(MNSRServiceCopySalesByBranch::class);
            $branchResult = $branchService->processData($this->batchId);

            // Step 2: Execute MNSRServiceCopySalesByStore SECOND
            $storeService = app(MNSRServiceCopySalesByStore::class);
            $salesPerformanceId = $branchResult['sales_performance_id'] ?? null;
            $storeResult = $storeService->processData($this->batchId, $salesPerformanceId);

            // If we reach here, both services completed successfully
            // The MNSRServiceCopySalesByStore already updates the batch status to Successful
        } catch (Exception $e) {
            // Log error to batch immediately
            $this->logErrorToMacroBatch(
                $this->batchId,
                $e,
                "GenerateSalesHistoryJob::handle - Sales history generation failed for batch ID: {$this->batchId}",
                'critical'
            );

            // Update batch status to failed immediately - use fresh query to ensure persistence
            MacroBatch::where('id', $this->batchId)->update(['status' => MacroBatchStatusEnum::Failed()->value]);

            // Throw to mark job as failed
            throw $e;
        }
    }
}
