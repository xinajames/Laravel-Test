<?php

namespace App\Jobs\Royalty;

use App\Enums\MacroBatchStatusEnum;
use App\Enums\MacroFileTypeEnum;
use App\Models\Royalty\MacroBatch;
use App\Models\Royalty\MacroUpload;
use App\Services\Royalty\MNSRJbmisPosDataService;
use App\Traits\ErrorLogger;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

class AddJbmisPosDataToMnsrJob implements ShouldQueue
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

            $mnsrJbmisService = new MNSRJbmisPosDataService;

            $macroUploads = MacroUpload::where('batch_id', $this->batch_id)
                ->where('file_type_id', MacroFileTypeEnum::JBMISData()->value)
                ->limit(1)
                ->get();

            foreach ($macroUploads as $macroUpload) {
                try {
                    $parts = explode('-', $macroUpload->file_name);
                    $region = $parts[2];
                    $monthAbbrev = $parts[3];
                    $month = Carbon::createFromFormat('M', $monthAbbrev)->month;

                    $yearWithExt = $parts[4];
                    $year = pathinfo($yearWithExt, PATHINFO_FILENAME); // strips file extension

                    $mnsrJbmisService->addJbmisPosDataToMNSR($this->batch_id, $month, $year);
                } catch (Exception $serviceException) {
                    // Log the service-level error to MacroBatch
                    $this->logErrorToMacroBatch(
                        $this->batch_id,
                        $serviceException,
                        "AddJbmisPosDataToMnsrJob::handle - JBMIS/POS data processing failed for file: {$macroUpload->file_name}",
                        'critical'
                    );
                    // Store the exception to throw later after marking batch as failed
                    $shouldThrow = $serviceException;
                    break; // Stop processing other uploads
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
        $this->markBatchAsFailedWithError($this->batch_id, $e, 'AddJbmisPosDataToMnsrJob failed');
    }
}
