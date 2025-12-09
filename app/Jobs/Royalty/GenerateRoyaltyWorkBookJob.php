<?php

namespace App\Jobs\Royalty;

use App\Enums\MacroBatchStatusEnum;
use App\Enums\MacroFileRevisionEnum;
use App\Enums\MacroFileTypeEnum;
use App\Models\Royalty\MacroBatch;
use App\Models\Royalty\MacroOutput;
use App\Services\Royalty\RoyaltyCreationService;
use App\Traits\ErrorLogger;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

class GenerateRoyaltyWorkBookJob implements ShouldQueue
{
    use ErrorLogger, Queueable;

    private $batch_id;

    public function __construct($batch_id)
    {
        $this->batch_id = $batch_id;
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

            $royaltyCreationService = new RoyaltyCreationService;

            $macroOutputs = MacroOutput::where('batch_id', $this->batch_id)
                ->where('file_type_id', MacroFileTypeEnum::MNSR()->value)
                ->where('file_revision_id', MacroFileRevisionEnum::MNSRAddedJBMISData()->value)
                ->orderBy('created_at', 'desc')
                ->limit(1)
                ->get();

            foreach ($macroOutputs as $macroUpload) {
                try {
                    $parts = explode('-', $macroUpload->file_name);
                    $monthAbbrev = $parts[4];
                    $month = Carbon::createFromFormat('M', $monthAbbrev)->month;

                    $yearWithExt = $parts[5];
                    $year = (int)pathinfo($yearWithExt, PATHINFO_FILENAME); // strips file extension

                    $royaltyCreationService->generateRoyalty($this->batch_id, $month, $year);
                } catch (Exception $serviceException) {
                    // Log the service-level error to MacroBatch
                    $this->logErrorToMacroBatch(
                        $this->batch_id,
                        $serviceException,
                        "GenerateRoyaltyWorkBookJob::handle - Royalty workbook generation failed for file: {$macroUpload->file_name}",
                        'critical'
                    );
                    // Store the exception to throw later after marking batch as failed
                    $shouldThrow = $serviceException;
                    break; // Stop processing other outputs
                }
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
        $this->markBatchAsFailedWithError($this->batch_id, $e, 'GenerateRoyaltyWorkBookJob failed');
    }
}
