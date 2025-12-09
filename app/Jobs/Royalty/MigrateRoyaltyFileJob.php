<?php

namespace App\Jobs\Royalty;

use App\Enums\MacroBatchStatusEnum;
use App\Enums\MacroFileRevisionEnum;
use App\Enums\MacroFileTypeEnum;
use App\Models\Royalty\MacroBatch;
use App\Models\Royalty\MacroOutput;
use App\Traits\ErrorLogger;
use App\Traits\ManageFilesystems;
use App\Traits\ManageRoyaltyFiles;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MigrateRoyaltyFileJob implements ShouldQueue
{
    use Queueable, ErrorLogger, ManageFilesystems, ManageRoyaltyFiles;

    private string $mnsrFilePath;
    private string $royaltyFilePath;
    private int $month;
    private int $year;

    public function __construct(string $mnsrFilePath, string $royaltyFilePath, int $month, int $year)
    {
        $this->mnsrFilePath = $mnsrFilePath;
        $this->royaltyFilePath = $royaltyFilePath;
        $this->month = $month;
        $this->year = $year;
        $this->queue = 'royalty';
    }

    public function handle(): void
    {
        $shouldThrow = null;
        $batchId = null;

        try {
            DB::beginTransaction();

            // Create MacroBatch
            $monthAbbrev = Carbon::createFromDate($this->year, $this->month, 1)->format('M');
            $batch = new MacroBatch();
            $batch->code = Str::uuid();
            $batch->title = "Migration of {$monthAbbrev} {$this->year} Royalty Files";
            $batch->remarks = "Automated migration";
            $batch->status = MacroBatchStatusEnum::Ongoing()->value;
            $batch->month = $this->month;
            $batch->year = $this->year;
            $batch->user_id = 1;
            $batch->save();

            $batchId = $batch->id;

            // Process MNSR file
            $this->processMnsrFile($batch->id, $monthAbbrev);

            // Process Royalty workbook file
            $this->processRoyaltyFile($batch->id, $monthAbbrev);

            // Update batch status
            $batch->status = MacroBatchStatusEnum::Successful()->value;
            $batch->completed_at = now();
            $batch->save();

            DB::commit();

            Log::info("Successfully migrated royalty files for {$monthAbbrev} {$this->year}", [
                'batch_id' => $batch->id
            ]);

        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            DB::rollBack();
            $shouldThrow = $e;
        } finally {
            if ($shouldThrow !== null && $batchId !== null) {
                $this->markBatchAsFailed($batchId, $shouldThrow);
                throw $shouldThrow;
            }
        }
    }

    private function processMnsrFile(int $batchId, string $monthAbbrev): void
    {
        // Read the file from local storage
        $fileContent = file_get_contents($this->mnsrFilePath);

        // Create temporary file for processing
        $tempDir = sys_get_temp_dir() . '/royalty_migration_' . $batchId;
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $tempPath = $tempDir . '/mnsr_temp.xlsx';
        file_put_contents($tempPath, $fileContent);

        try {
            // Use PhpSpreadsheet to read and process the file (similar to CacheUploadedRoyaltyFilesJob)
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(false); // Allow formula calculation
            $reader->setReadEmptyCells(false);

            $spreadsheet = $reader->load($tempPath);

            // Force calculation of all formulas
            $spreadsheet->getCalculationEngine()->clearCalculationCache();

            // First, trim all worksheets to remove unnecessary rows
            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                $this->trimWorksheet($worksheet);
            }

            // Save the trimmed spreadsheet back to temp file
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($tempPath);

            // Read the trimmed sheets for JSON conversion
            $mnsrData = [];
            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                $sheetName = $worksheet->getTitle();
                // Use toArray with calculateFormulas = true to get calculated values
                $mnsrData[$sheetName] = $worksheet->toArray(null, true, true, true);
            }

            // Define paths for generated folder using traits
            $generatedBasePath = $this->generateUploadBasePath() . '/royalty/generated/' . $batchId;
            $generatedXlsxPath = $generatedBasePath . '/Monthly-Natl-Sales-Rept-' . $monthAbbrev . '-' . $this->year . '.xlsx';
            $generatedCachedPath = $generatedBasePath . '/Cached-Monthly-Natl-Sales-Rept-' . $monthAbbrev . '-' . $this->year . '.json';

            // Upload the XLSX file using trait function
            $this->upload($tempPath, $generatedXlsxPath);

            // Upload the cached JSON data using trait function
            $jsonData = json_encode($mnsrData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $this->uploadData($jsonData, $generatedCachedPath);

            // Create MacroOutput entry
            $macroOutput = new MacroOutput();
            $macroOutput->batch_id = $batchId;
            $macroOutput->status = MacroBatchStatusEnum::Successful()->value;
            $macroOutput->file_name = 'Monthly-Natl-Sales-Rept-' . $monthAbbrev . '-' . $this->year . '.xlsx';
            $macroOutput->file_type_id = MacroFileTypeEnum::MNSR()->value;
            $macroOutput->file_revision_id = MacroFileRevisionEnum::MNSRAddedJBMISData()->value;
            $macroOutput->file_path = $generatedXlsxPath;
            $macroOutput->cached_path = $generatedCachedPath;
            $macroOutput->month = $this->month;
            $macroOutput->year = $this->year;
            $macroOutput->completed_at = now();
            $macroOutput->save();

        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            Log::error($exception->getTraceAsString());
        } finally {
            // Clean up temporary files
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
            if (is_dir($tempDir) && count(scandir($tempDir)) == 2) {
                rmdir($tempDir);
            }
        }
    }

    private function processRoyaltyFile(int $batchId, string $monthAbbrev): void
    {
        // Read the file from local storage
        $fileContent = file_get_contents($this->royaltyFilePath);

        // Create temporary file for processing
        $tempDir = sys_get_temp_dir() . '/royalty_migration_' . $batchId;
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $tempPath = $tempDir . '/royalty_temp.xlsx';
        file_put_contents($tempPath, $fileContent);

        try {
            // Read the Excel file for JSON conversion
            $royaltyFile = Excel::toCollection([], $tempPath, null, \Maatwebsite\Excel\Excel::XLSX);

            // Convert to array format similar to MNSR processing
            $royaltyData = [];
            foreach ($royaltyFile as $sheetIndex => $sheet) {
                $royaltyData["Sheet{$sheetIndex}"] = $sheet->toArray();
            }

            // Define paths for generated folder using traits
            $generatedBasePath = $this->generateUploadBasePath() . '/royalty/generated/' . $batchId;
            $generatedXlsxPath = $generatedBasePath . '/Royalty-Workbook-' . $this->year . '-' . $monthAbbrev . '.xlsx';
            $generatedCachedPath = $generatedBasePath . '/Cached-Royalty-Workbook-' . $this->year . '-' . $monthAbbrev . '.json';

            // Upload the XLSX file using trait function
            $this->upload($tempPath, $generatedXlsxPath);

            // Upload the cached JSON data using trait function
            $jsonData = json_encode($royaltyData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $this->uploadData($jsonData, $generatedCachedPath);

            // Create MacroOutput entry
            $macroOutput = new MacroOutput();
            $macroOutput->batch_id = $batchId;
            $macroOutput->status = MacroBatchStatusEnum::Successful()->value;
            $macroOutput->file_name = 'Royalty-Workbook-' . $this->year . '-' . $monthAbbrev . '.xlsx';
            $macroOutput->file_type_id = MacroFileTypeEnum::Royalty()->value;
            $macroOutput->file_revision_id = MacroFileRevisionEnum::RoyaltyDefault()->value;
            $macroOutput->file_path = $generatedXlsxPath;
            $macroOutput->cached_path = $generatedCachedPath;
            $macroOutput->month = $this->month;
            $macroOutput->year = $this->year;
            $macroOutput->completed_at = now();
            $macroOutput->save();

        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            Log::error($exception->getTraceAsString());
        } finally {
            // Clean up temporary files
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
            if (is_dir($tempDir) && count(scandir($tempDir)) == 2) {
                rmdir($tempDir);
            }
        }
    }

    /**
     * Trim worksheet to remove unnecessary rows from the actual XLSX file
     * (copied from CacheUploadedRoyaltyFilesJob)
     */
    private function trimWorksheet($worksheet): void
    {
        $highestRow = $worksheet->getHighestRow();

        // Find the last row with data in columns A or B, starting from row 10
        $lastDataRow = 10; // Start from row 10

        for ($i = max(10, $highestRow); $i >= 10; $i--) {
            $columnA = $worksheet->getCell('A' . $i)->getCalculatedValue();
            $columnB = $worksheet->getCell('B' . $i)->getCalculatedValue();

            // If either column A or B has data, this is our last data row
            if (!empty($columnA) || !empty($columnB)) {
                $lastDataRow = $i;
                break;
            }
        }

        // Keep the last data row + 2 empty rows
        $keepUntilRow = $lastDataRow + 2;

        // Remove excess rows if there are any
        if ($keepUntilRow < $highestRow) {
            $worksheet->removeRow($keepUntilRow + 1, $highestRow - $keepUntilRow);
        }
    }

    private function markBatchAsFailed(int $batchId, Exception $e): void
    {
        try {
            // Use a separate transaction to ensure batch status update persists
            DB::transaction(function () use ($batchId, $e) {
                $this->markBatchAsFailedWithError($batchId, $e, 'MigrateRoyaltyFileJob failed');
            });
        } catch (Exception $logException) {
            Log::error('Failed to mark batch as failed', [
                'batch_id' => $batchId,
                'original_error' => $e->getMessage(),
                'log_error' => $logException->getMessage()
            ]);
        }
    }
}
