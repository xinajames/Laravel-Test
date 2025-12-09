<?php

namespace App\Jobs\Royalty;

use App\Enums\MacroBatchStatusEnum;
use App\Models\Royalty\MacroBatch;
use App\Models\Royalty\MacroOutput;
use App\Services\Royalty\RoyaltyUpdateService;
use App\Traits\ErrorLogger;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use RuntimeException;

class UpdateRoyaltyWorkBookJob implements ShouldQueue
{
    use ErrorLogger, Queueable;

    private $batch_id;
    private $royalty_macro_output_id;

    public function __construct($batch_id, $royalty_macro_output_id)
    {
        $this->batch_id = $batch_id;
        $this->royalty_macro_output_id = $royalty_macro_output_id;
        $this->queue = 'royalty';
    }

    public function handle(): void
    {
        $shouldThrow = null;

        try {
            // Update batch status to Ongoing at the start if still Pending
            $batch = MacroBatch::find($this->batch_id);
            if ($batch && $batch->status == MacroBatchStatusEnum::Pending()->value) {
                $batch->status = MacroBatchStatusEnum::Ongoing()->value;
                $batch->save();
            }

            $royaltyUpdateService = new RoyaltyUpdateService;

            $macroOutput = MacroOutput::find($this->royalty_macro_output_id);

            if (!$macroOutput) {
                $this->logErrorToMacroBatch(
                    $this->batch_id,
                    "MacroOutput with ID {$this->royalty_macro_output_id} not found",
                    'UpdateRoyaltyWorkBookJob::handle - Missing MacroOutput record',
                    'critical'
                );
                throw new RuntimeException("MacroOutput with ID {$this->royalty_macro_output_id} not found");
            }

            try {
                $parts = explode('-', $macroOutput->file_name);
                $year = (int)$parts[2];

                $monthAbbrevWithExt = $parts[3];
                $monthAbbrev = pathinfo($monthAbbrevWithExt, PATHINFO_FILENAME); // strips file extension
                $month = Carbon::createFromFormat('M', $monthAbbrev)->month;

                $royaltyUpdateService->updateRoyalty($this->batch_id, $this->royalty_macro_output_id, $month, $year);
            } catch (Exception $serviceException) {
                // Log the service-level error to MacroBatch
                $this->logErrorToMacroBatch(
                    $this->batch_id,
                    $serviceException,
                    "UpdateRoyaltyWorkBookJob::handle - Royalty workbook update failed for file: {$macroOutput->file_name}",
                    'critical'
                );
                // Store the exception to throw later after marking batch as failed
                $shouldThrow = $serviceException;
            }
        } catch (Exception $e) {
            // This catches any other unexpected errors
            $shouldThrow = $e;
        } finally {
            // Always mark batch as failed if there was an error
            if ($shouldThrow !== null) {
                $this->markBatchAsFailed($shouldThrow);
                throw $shouldThrow;
            }
        }
    }

    private function markBatchAsFailed(Exception $e): void
    {
        $this->markBatchAsFailedWithError($this->batch_id, $e, 'UpdateRoyaltyWorkBookJob failed');
    }
}
