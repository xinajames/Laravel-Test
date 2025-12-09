<?php

namespace App\Jobs\Royalty;

use App\Enums\MacroBatchStatusEnum;
use App\Enums\MacroFileRevisionEnum;
use App\Enums\MacroFileTypeEnum;
use App\Enums\MacroStepStatusEnum;
use App\Models\Royalty\MacroBatch;
use App\Models\Royalty\MacroStep;
use App\Services\Royalty\MNSRFranchiseeDataService;
use App\Traits\ErrorLogger;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

class AddFranchiseeDataToMnsrJob implements ShouldQueue
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
        try {
            // Update batch status to Ongoing at the start
            $batch = MacroBatch::find($this->batch_id);
            if ($batch && $batch->status == MacroBatchStatusEnum::Pending()->value) {
                $batch->status = MacroBatchStatusEnum::Ongoing()->value;
                $batch->save();
            }

            $mnsrFranchiseeDataService = new MNSRFranchiseeDataService;

            $macroSteps = MacroStep::where('batch_id', $this->batch_id)
                ->where('file_type_id', MacroFileTypeEnum::MNSR()->value)
                ->where('file_revision_id', MacroFileRevisionEnum::MNSRAddedFranchiseeData()->value)
                ->whereIn('status', [MacroStepStatusEnum::Pending()->value, MacroStepStatusEnum::Failed()->value])
                ->get();

            foreach ($macroSteps as $macroStep) {
                try {
                    // Mark step as ongoing before processing
                    MacroStep::where('id', $macroStep->id)->update(['status' => MacroStepStatusEnum::Ongoing()->value]);

                    $macroUpload = $macroStep->macroUpload;
                    $parts = explode('-', $macroUpload->file_name);
                    $region = $parts[2];
                    $monthAbbrev = $parts[3];
                    $month = Carbon::createFromFormat('M', $monthAbbrev)->month;

                    $yearWithExt = $parts[4];
                    $year = (int)pathinfo($yearWithExt, PATHINFO_FILENAME);

                    $mnsrFranchiseeDataService->addFranchiseeDataToMNSR($this->batch_id, $macroStep->id, $month, $year, $region);
                } catch (Exception $serviceException) {
                    // Handle step failure immediately - use fresh query to ensure persistence
                    MacroStep::where('id', $macroStep->id)->update(['status' => MacroStepStatusEnum::Failed()->value]);

                    // Log error to batch immediately
                    $this->logErrorToMacroBatch(
                        $this->batch_id,
                        $serviceException,
                        "AddFranchiseeDataToMnsrJob::handle - Franchisee data processing failed for step ID: {$macroStep->id}, file: {$macroUpload->file_name}",
                        'critical'
                    );

                    // Update batch status to failed immediately - use fresh query to ensure persistence
                    MacroBatch::where('id', $this->batch_id)->update(['status' => MacroBatchStatusEnum::Failed()->value]);

                    // Throw to mark job as failed
                    throw $serviceException;
                }
            }

            // Check if all steps are completed before dispatching next job
            $remainingSteps = MacroStep::where('batch_id', $this->batch_id)
                ->where('file_type_id', MacroFileTypeEnum::MNSR()->value)
                ->where('file_revision_id', MacroFileRevisionEnum::MNSRAddedFranchiseeData()->value)
                ->whereIn('status', [MacroStepStatusEnum::Pending()->value, MacroStepStatusEnum::Failed()->value])
                ->count();

            if ($remainingSteps == 0) {
                dispatch(new AddJbmisPosDataToMnsrJob($this->batch_id));
            }
        } catch (Exception $e) {
            // Any other errors that weren't caught in the foreach loop
            throw $e;
        }
    }

}
