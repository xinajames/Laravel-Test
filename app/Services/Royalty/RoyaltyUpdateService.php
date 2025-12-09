<?php

namespace App\Services\Royalty;

use App\Enums\MacroBatchStatusEnum;
use App\Enums\MacroFileRevisionEnum;
use App\Enums\MacroFileTypeEnum;
use App\Models\Royalty\MacroBatch;
use App\Models\Royalty\MacroOutput;
use App\Models\SalesPerformance;
use App\Traits\ErrorLogger;
use App\Traits\HandlesRoyaltyData;
use App\Traits\ManageFilesystems;
use App\Traits\VbaRounding;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

class RoyaltyUpdateService
{
    use ErrorLogger, HandlesRoyaltyData, ManageFilesystems, VbaRounding;

    // Constants
    const LMSF_PERCENT = 0.005;

    const NMSF_PERCENT = 0.01;

    const VAT_RATE = 1.12;

    const EWT_RATE = 0.02; // Updated from 0.15 to 0.02 per comment on 20230721

    // Cached JSON data
    private $mnsrJsonData; // Store the MNSR JSON data
    private $batch_id; // Store the batch ID for error logging

    private $latestAdjustNumber = 0;

    // Paths
    private $salesFolder;

    private $monthWorkFolder;

    // Files
    private $invWorkFilename;

    private $salesFilename;

    // Objects
    private $invWorkBook;

    private $storSheet;

    private $consSheet;

    private $summSheet;

    // Date variables
    private $startOfMonth;

    private $endOfMonth;

    private $daysInMonth;

    private $todayDate;

    // Tracking totals
    private $totVATInAmt = 0;

    private $totVATExAmt = 0;

    private $totEWTAmt = 0;

    private $totLMSFAmt = 0;

    /**
     * Generate adjustments workbook for royalty accounting entries
     *
     * @param string $batch_id The batch ID
     * @param int $salesMonth Month (1-12)
     * @param int $salesYear Year (4 digits)
     * @return array Results of the operation
     */
    public function updateRoyalty(string $batch_id, int $royalty_macro_output_id, int $salesMonth, int $salesYear): array
    {
        $this->batch_id = $batch_id;
        DB::beginTransaction();

        try {

            // Convert numeric month to text month abbreviation (Jan, Feb, etc.)
            $salesMonthText = date('M', mktime(0, 0, 0, $salesMonth, 1, $salesYear));

            // Setup paths and filenames
            $this->setupPaths($batch_id, $royalty_macro_output_id, $salesYear, $salesMonthText);

            // Setup date variables
            $this->setupDateVariables($salesMonth, $salesYear);

            // Load MNSR data from cached JSON and find latest adjustment
            $this->loadMnsrJsonData($batch_id);

            // Load workbooks
            $this->loadWorkbooks();

            // Find the latest adjustment sheet from JSON data
            $adjustNbr = $this->findLatestAdjustmentFromJson();

            if ($adjustNbr == 0) {
                throw new Exception('The Original Monthly Report has not yet been processed. Cannot use this program to process it.');
            }

            // Create a new adjustment sheet and mark the original as closed
            $newAdjustNbr = $adjustNbr + 1;
            $this->createNewAdjustmentSheet($adjustNbr, $newAdjustNbr);

            // Copy sales data from MNSR JSON to the storSheet
            $this->copySalesDataFromJson();

            // Calculate invoice details for each store
            $this->calculateInvoiceDetails($salesYear, $salesMonthText);

            // Calculate changes to previous values
            $this->determineChanges();

            // Sort the store sheet by company, cluster, and store
            $this->sortStorSheet();

            // Copy and consolidate by cluster
            $this->consolidateByCluster();

            // Update summary sheet with totals
            $this->updateSummarySheet();

            // Save workbooks and update cached JSON
            $this->saveWorkbooks($batch_id, $salesYear, $salesMonthText);

            // Save workbooks and update cached JSON
            $this->saveWorkbooks($batch_id, $salesYear, $salesMonthText);

            // Update macro batch with completion timestamp
            $macroBatch = MacroBatch::find($batch_id);
            $macroBatch->status = MacroBatchStatusEnum::Successful()->value;
            $macroBatch->completed_at = now();
            $macroBatch->save();

            DB::commit();

            // Return success with totals
            return [
                'success' => true,
                'message' => 'The Royalty Adjustment Workbook has been updated successfully.',
                'data' => [
                    'total_vat_inc' => $this->totVATInAmt,
                    'total_vat_exc' => $this->totVATExAmt,
                    'total_ewt' => $this->totEWTAmt,
                    'total_lmsf' => $this->totLMSFAmt,
                ],
            ];
        } catch (Exception $e) {
            DB::rollBack();
            
            // Log the error to the batch
            $this->logErrorToMacroBatch(
                $batch_id,
                $e,
                'RoyaltyUpdateService::updateRoyalty failed',
                'critical'
            );
            
            // Update batch status to failed
            $batch = MacroBatch::find($batch_id);
            if ($batch) {
                $batch->status = MacroBatchStatusEnum::Failed()->value;
                $batch->save();
            }
            
            throw $e;
        }
    }

    /**
     * Load MNSR data from cached JSON
     */
    private function loadMnsrJsonData(string $batch_id): void
    {
        $macroOutput = MacroOutput::where('batch_id', $batch_id)
            ->where('file_type_id', MacroFileTypeEnum::MNSR()->value)
            ->whereIn('file_revision_id', [
                MacroFileRevisionEnum::MNSRAddedJBMISData()->value,
                MacroFileRevisionEnum::MNSRCreatedRoyaltyData()->value
            ])
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$macroOutput || !$macroOutput->cached_path) {
            throw new Exception("No cached MNSR data found for batch {$batch_id}");
        }

        // Load MNSR data from cached JSON
        $this->mnsrJsonData = json_decode($this->readFile($macroOutput->cached_path), true);
        $this->ensureDateConsistency($this->mnsrJsonData);

        // Convert any formulas in the JSON data to actual calculated values
        foreach ($this->mnsrJsonData as $sheetName => &$sheetData) {
            $sheetData = $this->convertBulk($sheetData);
        }

        foreach ($this->mnsrJsonData as $sheetName => &$sheetData) {
            $weekIntervals = $this->calculateWeekIntervals($this->startOfMonth->month, $this->startOfMonth->year);
            $this->addEstimatedSalesColumns($sheetData, $this->startOfMonth->month, $this->startOfMonth->year);
            $this->distributeEstimatedSalesAcrossWeeks($sheetData, $weekIntervals);
        }
    }

    /**
     * Find the latest adjustment sheet from JSON data
     */
    private function findLatestAdjustmentFromJson(): int
    {
        $adjustNbr = 0;

        while (true) {
            $tabName = "Adjust-{$adjustNbr}";

            // Check if sheet exists in JSON data
            if (!isset($this->mnsrJsonData[$tabName])) {
                break;
            }

            $sheetData = $this->mnsrJsonData[$tabName];

            // Check if sheet is closed (check cell A4 = row 4, col A = index [3][0])
            if (isset($sheetData[3][0]) && $sheetData[3][0] === ' T H I S   S H E E T   I S   C L O S E D') {
                $adjustNbr++;

                continue;
            }

            // Found an open sheet
            break;
        }

        $this->latestAdjustNumber = $adjustNbr;

        return $adjustNbr;
    }

    /**
     * Setup paths and filenames
     */
    private function setupPaths(string $batch_id, int $royalty_macro_output_id, int $salesYear, string $salesMonthText): void
    {
        $royaltyMacroOutput = MacroOutput::find($royalty_macro_output_id);

        // point both input (MNSR) and output (Royalty) at your royalty/generated folder
        $base = storage_path("app/royalty/generated/{$batch_id}/");
        $this->salesFolder = $base;
        $this->monthWorkFolder = $base;

        // keep your existing naming convention for the workbooks
        $this->invWorkFilename = "Created-Royalty-Workbook-{$salesYear}-{$salesMonthText}.xlsx";
        $this->salesFilename = "4-Monthly-Natl-Sales-Rept-{$salesMonthText}-{$salesYear}.xlsx";

        // Ensure the royalty file exists locally
        $localRoyaltyPath = $this->monthWorkFolder . $this->invWorkFilename;
        if (!file_exists($localRoyaltyPath)) {
            $dir = dirname($localRoyaltyPath);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($localRoyaltyPath, $this->readFile($royaltyMacroOutput->file_path));
        }
    }

    /**
     * Setup date variables
     */
    private function setupDateVariables($salesMonth, $salesYear): void
    {
        $this->startOfMonth = Carbon::createFromDate($salesYear, $salesMonth, 1);
        $this->endOfMonth = $this->startOfMonth->copy()->endOfMonth();
        $this->daysInMonth = $this->startOfMonth->daysInMonth;
        $this->todayDate = Carbon::now();
    }

    /**
     * Load workbooks
     */
    private function loadWorkbooks(): void
    {
        // Load Royalty Invoices Workbook
        $invWorkPath = $this->monthWorkFolder . $this->invWorkFilename;
        if (!file_exists($invWorkPath)) {
            throw new Exception("Royalty workbook not found at: {$invWorkPath}");
        }
        $this->invWorkBook = IOFactory::load($invWorkPath);
    }

    /**
     * Create a new adjustment sheet and mark the original as closed
     */
    private function createNewAdjustmentSheet($adjustNbr, $newAdjustNbr): void
    {
        $tabName = "Adjust-{$adjustNbr}";
        $newTabName = "Adjust-{$newAdjustNbr}";

        // Get current adjustment data from JSON
        $currentAdjustData = $this->mnsrJsonData[$tabName];

        // Create a copy for the new adjustment and clear header cells A to K for rows 2-5
        $newAdjustData = $currentAdjustData;
        for ($row = 1; $row <= 4; $row++) { // rows 2-5 (1-based) = indices 1-4 (0-based)
            for ($col = 0; $col <= 10; $col++) { // columns A-K (1-based) = indices 0-10 (0-based)
                $newAdjustData[$row][$col] = '';
            }
        }
        $this->mnsrJsonData[$newTabName] = $newAdjustData;

        // Mark the current sheet as closed in JSON data
        $today = Carbon::now()->format('n/j/Y g:i:s A');
        $this->mnsrJsonData[$tabName][1][0] = ' T H E S E   A M O U N T S   W E R E'; // A2
        $this->mnsrJsonData[$tabName][2][0] = " P R O C E S S E D   O N: {$today}"; // A3
        $this->mnsrJsonData[$tabName][3][0] = ' T H I S   S H E E T   I S   C L O S E D'; // A4
    }

    /**
     * Copy sales data from MNSR JSON to the storSheet
     */
    private function copySalesDataFromJson(): void
    {
        // Find the latest royalty invoice worksheet
        $bookMonth = $this->startOfMonth->month;
        $bookYear = $this->startOfMonth->year;
        $currentMonth = $this->todayDate->month;
        $currentYear = $this->todayDate->year;

        if ($currentYear > $bookYear) {
            $currentMonth += 12;
        }

        // MNSR adjustment numbers are always one ahead of Royalty sheet numbers
        // If MNSR has Adjust-1, Royalty should copy from StorSheet-0 and create StorSheet-1
        $sheetSuffix = $this->latestAdjustNumber - 1;     // Sheet to copy FROM
        $newSheetSuffix = $this->latestAdjustNumber;      // Sheet to CREATE

        $tempStorName = "StorSheet-{$sheetSuffix}";
        $tempSummName = "Summary-{$sheetSuffix}";
        $storName = "StorSheet-{$newSheetSuffix}";
        $summName = "Summary-{$newSheetSuffix}";

        // Check if source sheets exist for copying
        if (!$this->invWorkBook->sheetNameExists($tempStorName)) {
            throw new Exception("Source worksheet '{$tempStorName}' not found in workbook. Expected to copy from latest adjustment {$sheetSuffix}. Available sheets: " . implode(', ', $this->invWorkBook->getSheetNames()));
        }
        if (!$this->invWorkBook->sheetNameExists($tempSummName)) {
            throw new Exception("Source worksheet '{$tempSummName}' not found in workbook. Expected to copy from latest adjustment {$sheetSuffix}. Available sheets: " . implode(', ', $this->invWorkBook->getSheetNames()));
        }

        // Remove target sheets if they already exist (overwrite behavior like VBA)
        if ($this->invWorkBook->sheetNameExists($storName)) {
            $this->invWorkBook->removeSheetByIndex($this->invWorkBook->getIndex($this->invWorkBook->getSheetByName($storName)));
        }
        if ($this->invWorkBook->sheetNameExists($summName)) {
            $this->invWorkBook->removeSheetByIndex($this->invWorkBook->getIndex($this->invWorkBook->getSheetByName($summName)));
        }

        // Copy sheets to create new versions

        // Clone existing sheets to create new ones
        $tempStorSheet = $this->invWorkBook->getSheetByName($tempStorName);
        $clonedStorSheet = clone $tempStorSheet;
        $clonedStorSheet->setTitle($storName);
        $this->invWorkBook->addSheet($clonedStorSheet);
        $this->storSheet = $clonedStorSheet;

        $tempSummSheet = $this->invWorkBook->getSheetByName($tempSummName);
        $clonedSummSheet = clone $tempSummSheet;
        $clonedSummSheet->setTitle($summName);
        $this->invWorkBook->addSheet($clonedSummSheet);
        $this->summSheet = $clonedSummSheet;

        // Zero-out the Summary Sheet amounts
        $this->zeroOutSummarySheet();

        // Zero-out "This Month" amounts and copy "This Month" to "Last Month"
        $this->zeroOutThisMonthAmounts();

        // Get the latest adjustment data from JSON
        $latestAdjustData = $this->mnsrJsonData["Adjust-{$this->latestAdjustNumber}"];

        // Copy total sales amounts into the StorSheet from JSON data
        $salesRow = 8; // Starting row in Excel (1-based)
        $storRow = 6;

        while (true) {
            // Convert Excel row to 0-based array index
            $jsonRowIndex = $salesRow - 1;

            // Check if we've reached the end of data
            if ($jsonRowIndex >= count($latestAdjustData)) {
                break;
            }

            // Get the row from JSON data
            $mnsrRow = $latestAdjustData[$jsonRowIndex];

            // Check if cluster (column A = index 0) is empty - break if so
            if (empty(trim((string)($mnsrRow[0] ?? '')))) {
                break;
            }

            // Skip stores with no sales (columns AL and AP = indices 37 and 41)
            if (($mnsrRow[37] ?? 0) == 0 && ($mnsrRow[41] ?? 0) == 0) {
                $salesRow++;

                continue;
            }

            // Check if we've reached the end of the store sheet
            if (empty($this->storSheet->getCell("A{$storRow}")->getValue())) {
                $salesCluster = $mnsrRow[1] ?? ''; // Store (B = index 1)
                throw new Exception("NMSR Cluster: {$salesCluster} is not in Royalty Invoices Workbook.");
            }

            // Match cluster and store
            $salesCluster = $mnsrRow[0] ?? ''; // Cluster (A = index 0)
            $salesStore = $mnsrRow[1] ?? ''; // Store (B = index 1)
            $storCluster = $this->storSheet->getCell("A{$storRow}")->getValue();
            $storStore = $this->storSheet->getCell("B{$storRow}")->getValue();

            if ($salesCluster != $storCluster || $salesStore != $storStore) {
                $storRow++;

                continue;
            }

            // Cluster and store match - copy the sales amounts
            $this->storSheet->setCellValue("AI{$storRow}", $this->vbaRound((float)($mnsrRow[37] ?? 0), 2)); // actual bread sales (AL = index 37)
            $this->storSheet->setCellValue("AJ{$storRow}", $this->vbaRound((float)($mnsrRow[38] ?? 0), 2)); // actual non-bread sales (AM = index 38)
            $this->storSheet->setCellValue("AN{$storRow}", $this->vbaRound((float)($mnsrRow[41] ?? 0), 2)); // estimated bread sales (AP = index 41)
            $this->storSheet->setCellValue("AO{$storRow}", $this->vbaRound((float)($mnsrRow[42] ?? 0), 2)); // estimated non-bread sales (AQ = index 42)

            $this->storSheet->setCellValueByColumnAndRow(65, $storRow, $this->vbaRound((float)($mnsrRow[64] ?? 0), 2)); // BM - Estimated Bread Sales
            $this->storSheet->setCellValueByColumnAndRow(66, $storRow, $this->vbaRound((float)($mnsrRow[65] ?? 0), 2)); // BN - Estimated Non-Bread Sales
            $this->storSheet->setCellValueByColumnAndRow(67, $storRow, $this->vbaRound((float)($mnsrRow[66] ?? 0), 2)); // BO - Estimated Combined Sales

            $this->storSheet->getStyleByColumnAndRow(65, $storRow)->getNumberFormat()->setFormatCode('#,##0.00');
            $this->storSheet->getStyleByColumnAndRow(66, $storRow)->getNumberFormat()->setFormatCode('#,##0.00');
            $this->storSheet->getStyleByColumnAndRow(67, $storRow)->getNumberFormat()->setFormatCode('#,##0.00');

            // Apply the same formatting fixes as in the creation service
            // FIX 1: G column should be 0 if no value
            $this->storSheet->setCellValueByColumnAndRow(7, $storRow, (float)($mnsrRow[56] ?? 0)); // Old Royalty % (BE = index 56)
            $this->storSheet->setCellValueByColumnAndRow(8, $storRow, (float)($mnsrRow[57] ?? 0)); // New Royalty % (BF = index 57)

            // FIX 2: Column I (Effect Date) - convert to proper Excel date format
            $effectDateValue = $mnsrRow[58] ?? '';
            if (!empty($effectDateValue)) {
                if (is_numeric($effectDateValue)) {
                    // Already an Excel serial number
                    $this->storSheet->setCellValueByColumnAndRow(9, $storRow, $effectDateValue);
                } else {
                    // Convert string date to Excel serial number
                    try {
                        $dateObj = Carbon::parse($effectDateValue);
                        $excelDate = ExcelDate::PHPToExcel($dateObj);
                        $this->storSheet->setCellValueByColumnAndRow(9, $storRow, $excelDate);
                    } catch (Exception $e) {
                        $this->storSheet->setCellValueByColumnAndRow(9, $storRow, $effectDateValue);
                    }
                }
                // Apply date formatting to column I
                $this->storSheet->getStyleByColumnAndRow(9, $storRow)
                    ->getNumberFormat()
                    ->setFormatCode('m/d/yyyy');
            }

            $salesRow++;
            $storRow = 6; // Reset for next search
        }
    }

    /**
     * Zero out summary sheet amounts
     */
    private function zeroOutSummarySheet(): void
    {
        // Zero-out Workbook Summary Amounts
        $this->summSheet->setCellValue('D5', 0);
        $this->summSheet->setCellValue('E5', 0);
        $this->summSheet->setCellValue('D6', 0);
        $this->summSheet->setCellValue('E6', 0);
        $this->summSheet->setCellValue('D8', 0);
        $this->summSheet->setCellValue('E8', 0);

        // Zero-out JFC AR Invoice Summary Amounts
        $this->summSheet->setCellValue('D12', 0);
        $this->summSheet->setCellValue('E12', 0);
        $this->summSheet->setCellValue('F12', '');
        $this->summSheet->setCellValue('G12', '');
        $this->summSheet->setCellValue('D13', 0);
        $this->summSheet->setCellValue('E13', 0);
        $this->summSheet->setCellValue('D15', 0);
        $this->summSheet->setCellValue('E15', 0);
        $this->summSheet->setCellValue('F15', '');
        $this->summSheet->setCellValue('G15', '');

        // Zero-out JFC AR Credit Memos Summary Amounts
        $this->summSheet->setCellValue('D19', 0);
        $this->summSheet->setCellValue('E19', 0);
        $this->summSheet->setCellValue('F19', '');
        $this->summSheet->setCellValue('G19', '');
        $this->summSheet->setCellValue('D20', 0);
        $this->summSheet->setCellValue('E20', 0);
        $this->summSheet->setCellValue('D22', 0);
        $this->summSheet->setCellValue('E22', 0);
        $this->summSheet->setCellValue('F22', '');
        $this->summSheet->setCellValue('G22', '');

        // Zero-out JFC Incoming Payments Summary Amounts
        $this->summSheet->setCellValue('D28', 0);
        $this->summSheet->setCellValue('E28', 0);
        $this->summSheet->setCellValue('F28', 0);
        $this->summSheet->setCellValue('G28', 0);
        $this->summSheet->setCellValue('H28', '');
        $this->summSheet->setCellValue('I28', '');

        // Zero-out JFC Outgoing Payments Summary Amounts
        $this->summSheet->setCellValue('D31', 0);
        $this->summSheet->setCellValue('E31', 0);
        $this->summSheet->setCellValue('F31', 0);
        $this->summSheet->setCellValue('G31', 0);
        $this->summSheet->setCellValue('H31', '');
        $this->summSheet->setCellValue('I31', '');

        // Zero-out BGC Incoming Payments from CMs Summary Amounts
        for ($sumRow1 = 34; $sumRow1 <= 38; $sumRow1++) {
            for ($sumCol1 = 4; $sumCol1 <= 7; $sumCol1++) {
                $cell = Coordinate::stringFromColumnIndex($sumCol1) . $sumRow1;
                $this->summSheet->setCellValue($cell, 0);
            }
            for ($sumCol2 = 8; $sumCol2 <= 9; $sumCol2++) {
                $cell = Coordinate::stringFromColumnIndex($sumCol2) . $sumRow1;
                $this->summSheet->setCellValue($cell, '');
            }
        }

        // Zero-out BGC AP Invoices Summary Amounts
        for ($sumRow2 = 42; $sumRow2 <= 46; $sumRow2++) {
            for ($sumCol3 = 4; $sumCol3 <= 7; $sumCol3++) {
                $cell = Coordinate::stringFromColumnIndex($sumCol3) . $sumRow2;
                $this->summSheet->setCellValue($cell, 0);
            }
            for ($sumCol4 = 8; $sumCol4 <= 9; $sumCol4++) {
                $cell = Coordinate::stringFromColumnIndex($sumCol4) . $sumRow2;
                $this->summSheet->setCellValue($cell, '');
            }
        }

        // Zero-out BGC Outgoing Payments Summary Amounts
        for ($sumRow3 = 50; $sumRow3 <= 54; $sumRow3++) {
            for ($sumCol5 = 4; $sumCol5 <= 7; $sumCol5++) {
                $cell = Coordinate::stringFromColumnIndex($sumCol5) . $sumRow3;
                $this->summSheet->setCellValue($cell, 0);
            }
            for ($sumCol6 = 8; $sumCol6 <= 9; $sumCol6++) {
                $cell = Coordinate::stringFromColumnIndex($sumCol6) . $sumRow3;
                $this->summSheet->setCellValue($cell, '');
            }
        }
    }

    /**
     * Zero out "This Month" amounts and copy to "Last Month" - FIXED VERSION
     */
    private function zeroOutThisMonthAmounts(): void
    {
        for ($storRow = 6; $storRow <= 1500; $storRow++) {
            if (empty($this->storSheet->getCell("A{$storRow}")->getValue())) {
                break;
            }

            for ($thisCol = 35; $thisCol <= 48; $thisCol++) {
                $thisColLetter = Coordinate::stringFromColumnIndex($thisCol);
                $lastCol = $thisCol + 15;
                $lastColLetter = Coordinate::stringFromColumnIndex($lastCol);

                // Get value with explicit conversion to float and proper rounding
                $currentValue = (float)($this->storSheet->getCell("{$thisColLetter}{$storRow}")->getValue() ?: 0);

                // Round to avoid tiny decimal issues
                $currentValue = $this->vbaRound($currentValue, 2);

                // Fix for tiny floating point errors
                if (abs($currentValue) < 0.001) {
                    $currentValue = 0;
                }

                // Copy "This Month" to "Last Month"
                $this->storSheet->setCellValue("{$lastColLetter}{$storRow}", $currentValue);

                // Zero out "This Month"
                $this->storSheet->setCellValue("{$thisColLetter}{$storRow}", 0);
            }
        }
    }

    /**
     * Calculate invoice details for each store, with two debug dd() blocks:
     *  - one for the raw-gross/raw-net values
     *  - one for the "This vs Last" net‐change
     */
    private function calculateInvoiceDetails(int $salesYear, string $salesMonthText): void
    {
        $storRow = 6;

        // normalize our month bounds to midnight for exact day diffs
        $startDate = $this->startOfMonth->copy()->startOfDay();
        $endDate = $this->endOfMonth->copy()->startOfDay();

        while (true) {
            if (empty($this->storSheet->getCell("A{$storRow}")->getValue())) {
                break; // end of file
            }

            $vatInInvAmt = 0;
            $vatExInvAmt = 0;

            // read key fields
            $oldRoyaltyPercent = (float)$this->storSheet->getCell("G{$storRow}")->getValue();
            $newRoyaltyPercent = (float)$this->storSheet->getCell("H{$storRow}")->getValue();

            //            $newRoyaltyEffectDt = new Carbon(
            //                $this->convertSerialToDate(
            //                    $this->storSheet->getCell("I{$storRow}")->getValue()
            //                )
            //            );

            $effDateValue = $this->storSheet->getCell("I{$storRow}")->getValue();
            if ($effDateValue === null || $effDateValue === '' || $effDateValue == 0) {
                // Handle null/empty/zero effect date - use end of month as default
                $newRoyaltyEffectDt = $this->endOfMonth;
            } else {
                $newRoyaltyEffectDt = new Carbon(
                    $this->convertSerialToDate($effDateValue)
                );
            }

            // strip time so diffInDays matches VBA's date subtraction
            $newDate = $newRoyaltyEffectDt->copy()->startOfDay();

            // actual and accrued "bread" / "non‐bread" sales
            $actBread = (float)$this->storSheet->getCell("AI{$storRow}")->getValue();
            $actNonBread = (float)$this->storSheet->getCell("AJ{$storRow}")->getValue();
            $acrBread = (float)$this->storSheet->getCell("AN{$storRow}")->getValue();
            $acrNonBread = (float)$this->storSheet->getCell("AO{$storRow}")->getValue();

            // figure out how many days to apply old vs new rate
            if ($newDate < $startDate) {
                $oldPeriod = 0;
                $newPeriod = $this->daysInMonth;
            } elseif ($newDate > $endDate) {
                $oldPeriod = $this->daysInMonth;
                $newPeriod = 0;
            } else {
                // signed difference, just like VBA's NewRoyaltyEffectDt - StartOfMonth
                $oldPeriod = $startDate->diffInDays($newDate, false);
                $newPeriod = $this->daysInMonth - $oldPeriod;
            }

            //
            // 1) Actual bread royalty
            //
            if ($newDate < $startDate) {
                // Entire period at new royalty percentage
                $grossActBreadRoyalty = $this->vbaRound($actBread * $newRoyaltyPercent, 2);
            } elseif ($newDate > $endDate) {
                // Entire period at old royalty percentage
                $grossActBreadRoyalty = $this->vbaRound($actBread * $oldRoyaltyPercent, 2);
            } else {
                // Royalty percent changed during this month
                // VBA: OldPeriod = NewRoyaltyEffectDt - StartofMonth (INTEGER calculation like VBA)
                $oldPeriod = (int)($newDate->day - $startDate->day);
                $newPeriod = $this->daysInMonth - $oldPeriod;

                // Calculate gross royalties for old percentage period
                $grossActBreadOld = $this->vbaRound(($actBread * $oldRoyaltyPercent) * ($oldPeriod / $this->daysInMonth), 2);

                // Calculate gross royalties for new percentage period
                $grossActBreadNew = $this->vbaRound(($actBread * $newRoyaltyPercent) * ($newPeriod / $this->daysInMonth), 2);

                // Add them together
                $grossActBreadRoyalty = $grossActBreadOld + $grossActBreadNew;
            }
            $netActBreadRoyalty = $this->vbaRound($grossActBreadRoyalty / self::VAT_RATE, 2);
            $this->storSheet->setCellValue("AK{$storRow}", $this->vbaRound($netActBreadRoyalty, 2));
            $vatExInvAmt += $netActBreadRoyalty;

            //
            // 2) Actual non‐bread royalty (same logic)
            //
            if ($newDate < $startDate) {
                // Entire period at new royalty percentage
                $grossActNonBdRoyalty = $this->vbaRound($actNonBread * $newRoyaltyPercent, 2);
            } elseif ($newDate > $endDate) {
                // Entire period at old royalty percentage
                $grossActNonBdRoyalty = $this->vbaRound($actNonBread * $oldRoyaltyPercent, 2);
            } else {
                // Royalty percent changed during this month
                // VBA: OldPeriod = NewRoyaltyEffectDt - StartofMonth (INTEGER calculation like VBA)
                $oldPeriod = (int)($newDate->day - $startDate->day);
                $newPeriod = $this->daysInMonth - $oldPeriod;

                // Calculate gross royalties for old percentage period
                $grossActNonBdOld = $this->vbaRound(($actNonBread * $oldRoyaltyPercent) * ($oldPeriod / $this->daysInMonth), 2);

                // Calculate gross royalties for new percentage period
                $grossActNonBdNew = $this->vbaRound(($actNonBread * $newRoyaltyPercent) * ($newPeriod / $this->daysInMonth), 2);

                // Add them together
                $grossActNonBdRoyalty = $grossActNonBdOld + $grossActNonBdNew;
            }
            $netActNonBdRoyalty = $this->vbaRound($grossActNonBdRoyalty / self::VAT_RATE, 2);
            $this->storSheet->setCellValue("AL{$storRow}", $this->vbaRound($netActNonBdRoyalty, 2));
            $vatExInvAmt += $netActNonBdRoyalty;

            //
            // 3) Actual NMSF
            //
            $grossActNMSF = $this->vbaRound(($actBread + $actNonBread) * self::NMSF_PERCENT, 2);
            $netActNMSF = $this->vbaRound($grossActNMSF / self::VAT_RATE, 2);
            $this->storSheet->setCellValue("AM{$storRow}", $this->vbaRound($netActNMSF, 2));
            $vatExInvAmt += $netActNMSF;

            //
            // 4) Accrued bread & non‐bread royalty
            //
            if ($newDate < $startDate) {
                // Entire period at new royalty percentage
                $grossAcrBreadRoyalty = $this->vbaRound($acrBread * $newRoyaltyPercent, 2);
                $grossAcrNonBdRoyalty = $this->vbaRound($acrNonBread * $newRoyaltyPercent, 2);
            } elseif ($newDate > $endDate) {
                // Entire period at old royalty percentage
                $grossAcrBreadRoyalty = $this->vbaRound($acrBread * $oldRoyaltyPercent, 2);
                $grossAcrNonBdRoyalty = $this->vbaRound($acrNonBread * $oldRoyaltyPercent, 2);
            } else {
                // Royalty percent changed during this month
                // VBA: OldPeriod = NewRoyaltyEffectDt - StartofMonth (INTEGER calculation like VBA)
                $oldPeriod = (int)($newDate->day - $startDate->day);
                $newPeriod = $this->daysInMonth - $oldPeriod;

                // Calculate gross royalties for old percentage period
                $grossAcrBreadOld = $this->vbaRound(($acrBread * $oldRoyaltyPercent) * ($oldPeriod / $this->daysInMonth), 2);
                $grossAcrNonBdOld = $this->vbaRound(($acrNonBread * $oldRoyaltyPercent) * ($oldPeriod / $this->daysInMonth), 2);

                // Calculate gross royalties for new percentage period
                $grossAcrBreadNew = $this->vbaRound(($acrBread * $newRoyaltyPercent) * ($newPeriod / $this->daysInMonth), 2);
                $grossAcrNonBdNew = $this->vbaRound(($acrNonBread * $newRoyaltyPercent) * ($newPeriod / $this->daysInMonth), 2);

                // Add them together
                $grossAcrBreadRoyalty = $grossAcrBreadOld + $grossAcrBreadNew;
                $grossAcrNonBdRoyalty = $grossAcrNonBdOld + $grossAcrNonBdNew;
            }
            $netAcrBreadRoyalty = $this->vbaRound($grossAcrBreadRoyalty / self::VAT_RATE, 2);
            $netAcrNonBdRoyalty = $this->vbaRound($grossAcrNonBdRoyalty / self::VAT_RATE, 2);

            $grossAcrNMSF = $this->vbaRound(($acrBread + $acrNonBread) * self::NMSF_PERCENT, 2);
            $netAcrNMSF = $this->vbaRound($grossAcrNMSF / self::VAT_RATE, 2);

            $this->storSheet->setCellValue("AP{$storRow}", $this->vbaRound($netAcrBreadRoyalty, 2));
            $this->storSheet->setCellValue("AQ{$storRow}", $this->vbaRound($netAcrNonBdRoyalty, 2));
            $this->storSheet->setCellValue("AR{$storRow}", $this->vbaRound($netAcrNMSF, 2));
            $vatExInvAmt += $netAcrBreadRoyalty + $netAcrNonBdRoyalty + $netAcrNMSF;

            //
            // 5) LMSF
            //
            $totalSales = $actBread + $actNonBread + $acrBread + $acrNonBread;
            $lmsfInvAmt = $this->vbaRound($totalSales * self::LMSF_PERCENT, 2);
            if (
                ($salesYear === 2019 && in_array($salesMonthText, ['May', 'Jun'], true))
                || $this->storSheet->getCell("D{$storRow}")->getValue() === 'ZZZ'
            ) {
                $this->storSheet->setCellValue("AV{$storRow}", $this->vbaRound($lmsfInvAmt, 2));
            }

            //
            // 6) VAT‐inclusive & VAT‐exclusive
            //
            // Round VATExInvAmt to fix floating point precision issues
            $vatExInvAmt = $this->vbaRound($vatExInvAmt, 2);
            $vatInInvAmt = $this->vbaRound($vatExInvAmt * self::VAT_RATE, 2);
            $this->storSheet->setCellValue("AS{$storRow}", $this->vbaRound($vatInInvAmt, 2));
            $this->storSheet->setCellValue("AT{$storRow}", $this->vbaRound($vatExInvAmt, 2));

            //
            // 7) EWT
            //
            if ($this->storSheet->getCell("D{$storRow}")->getValue() === 'ZZZ') {
                $this->storSheet->setCellValue("AU{$storRow}", 0.00);
            } else {
                $ewtAmount = $this->vbaRound($vatExInvAmt * self::EWT_RATE, 2);
                $this->storSheet->setCellValue("AU{$storRow}", $this->vbaRound($ewtAmount, 2));
            }

            $storRow++;
        }
    }

    /**
     * Determine Net Sales and Invoice Amount Changes,
     * with deep raw‐value debug for cluster B10138 / store B21447
     */
    private function determineChanges(): void
    {
        $storRow = 6;
        while (true) {
            if (empty($this->storSheet->getCell("A{$storRow}")->getValue())) {
                break;
            }

            $clusterCode = $this->storSheet->getCell("A{$storRow}")->getValue();
            $storeCode = $this->storSheet->getCell("B{$storRow}")->getValue();

            for ($netCol = 10; $netCol <= 23; $netCol++) {
                $netColLetter = Coordinate::stringFromColumnIndex($netCol);
                $thisColLetter = Coordinate::stringFromColumnIndex($netCol + 25);
                $lastColLetter = Coordinate::stringFromColumnIndex($netCol + 40);

                // read the two-decimal values you wrote earlier
                $thisValue = (float)($this->storSheet->getCell("{$thisColLetter}{$storRow}")->getValue() ?: 0);
                $lastValue = (float)($this->storSheet->getCell("{$lastColLetter}{$storRow}")->getValue() ?: 0);

                // preliminary net
                $netValue = $this->vbaRound($thisValue - $lastValue, 2);
                if (abs($netValue) < 0.001) {
                    $netValue = 0;
                }

                // ▶ DEEP DEBUG for column L on B10138/B21447
                if ($netCol === 12
                    && $clusterCode === 'B10138'
                    && $storeCode === 'B21447'
                ) {
                    // re-compute raw four-decimal nets exactly as in VBA
                    $actBreadThis = (float)$this->storSheet->getCell("AI{$storRow}")->getValue();
                    $actBreadLast = (float)$this->storSheet->getCell("AX{$storRow}")->getValue();
                    $oldPct = $this->storSheet->getCell("G{$storRow}")->getValue();
                    $newPct = $this->storSheet->getCell("H{$storRow}")->getValue();
                    $effDt = new Carbon(
                        $this->convertSerialToDate(
                            $this->storSheet->getCell("I{$storRow}")->getValue()
                        )
                    );

                    $computeRaw = function ($salesAmt) use ($effDt, $oldPct, $newPct) {
                        if ($effDt < $this->startOfMonth) {
                            $gross = $salesAmt * $newPct;
                        } elseif ($effDt > $this->endOfMonth) {
                            $gross = $salesAmt * $oldPct;
                        } else {
                            $oldPeriod = $effDt->diffInDays($this->startOfMonth);
                            $newPeriod = $this->daysInMonth - $oldPeriod;
                            $gross = $salesAmt * $oldPct * ($oldPeriod / $this->daysInMonth)
                                + $salesAmt * $newPct * ($newPeriod / $this->daysInMonth);
                        }
                        $rawNet = $gross / self::VAT_RATE;

                        return [
                            'rawGross' => number_format($gross, 4, '.', ''),
                            'rawNet' => number_format($rawNet, 4, '.', ''),
                        ];
                    };

                    $thisRaw = $computeRaw($actBreadThis);
                    $lastRaw = $computeRaw($actBreadLast);

                    // overwrite your two-decimal floats with rawNet, then round for display
                    $thisValue = (float)$thisRaw['rawNet'];
                    $lastValue = (float)$lastRaw['rawNet'];
                    $netValue = $this->vbaRound($thisValue - $lastValue, 2);
                    if (abs($netValue) < 0.001) {
                        $netValue = 0;
                    }

                    // now round for the dump to two decimals
                    $thisValue = $this->vbaRound($thisValue, 2);
                    $lastValue = $this->vbaRound($lastValue, 2);
                }

                // write the net-change back into J–W
                $this->storSheet->setCellValue("{$netColLetter}{$storRow}", $netValue);
            }

            $storRow++;
        }
    }

    /**
     * Sort the StorSheet by store within company
     */
    private function sortStorSheet(): void
    {
        // Find the last record
        $lastRec = 6;
        while (!empty($this->storSheet->getCell("A{$lastRec}")->getValue())) {
            $lastRec++;
        }
        $lastRec--;

        // Sort range by company, cluster, and store
        $sortRange = "A6:BO{$lastRec}";

        // PhpSpreadsheet doesn't have a direct sort method like Excel VBA
        // We'll need to extract data, sort it, and then reinsert it
        $data = [];
        for ($row = 6; $row <= $lastRec; $row++) {
            $rowData = [];
            for ($col = 1; $col <= 67; $col++) { // Column BK is 63
                $colLetter = Coordinate::stringFromColumnIndex($col);
                $rowData[$col] = $this->storSheet->getCell("{$colLetter}{$row}")->getValue();
            }
            $data[] = $rowData;
        }

        // Sort the data by company (column D), cluster (column A), and store (column B)
        usort($data, function ($a, $b) {
            // Column D (Company) - Primary sort
            if ($a[4] != $b[4]) {
                return $a[4] <=> $b[4];
            }
            // Column A (Cluster) - Secondary sort
            if ($a[1] != $b[1]) {
                return $a[1] <=> $b[1];
            }

            // Column B (Store) - Tertiary sort
            return $a[2] <=> $b[2];
        });

        // Write the sorted data back to the sheet
        $row = 6;
        foreach ($data as $rowData) {
            foreach ($rowData as $col => $value) {
                $colLetter = Coordinate::stringFromColumnIndex($col);
                $this->storSheet->setCellValue("{$colLetter}{$row}", $value);
            }

            $this->storSheet->getStyleByColumnAndRow(65, $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $this->storSheet->getStyleByColumnAndRow(66, $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $this->storSheet->getStyleByColumnAndRow(67, $row)->getNumberFormat()->setFormatCode('#,##0.00');

            $row++;
        }
    }

    /**
     * Copy and consolidate by cluster
     */
    private function consolidateByCluster(): void
    {
        // Copy the StorSheet to create the ConsSheet
        $storName = $this->storSheet->getTitle();
        $newSheetSuffix = substr($storName, strrpos($storName, '-') + 1);
        $consName = "ConsSheet-{$newSheetSuffix}";

        // Remove ConsSheet if it already exists (overwrite behavior like VBA)
        if ($this->invWorkBook->sheetNameExists($consName)) {
            $this->invWorkBook->removeSheetByIndex($this->invWorkBook->getIndex($this->invWorkBook->getSheetByName($consName)));
        }

        // Clone the StorSheet to create ConsSheet
        $this->consSheet = clone $this->storSheet;
        $this->consSheet->setTitle($consName);
        $this->invWorkBook->addSheet($this->consSheet);

        // Consolidate the store amounts into cluster amounts
        $prevRow = 6;
        $thisRow = 7;
        $prevCluster = $this->consSheet->getCell("A{$prevRow}")->getValue();

        while (true) {
            if (empty($this->consSheet->getCell("A{$thisRow}")->getValue())) {
                // We are done with the consolidation
                break;
            }

            $thisCluster = $this->consSheet->getCell("A{$thisRow}")->getValue();

            if ($thisCluster == $prevCluster) {
                // Found another entry for the same cluster - add the amounts
                for ($consCol1 = 10; $consCol1 <= 23; $consCol1++) {
                    $consCol1Letter = Coordinate::stringFromColumnIndex($consCol1);
                    $val1 = $this->consSheet->getCell("{$consCol1Letter}{$prevRow}")->getValue() ?: 0;
                    $val2 = $this->consSheet->getCell("{$consCol1Letter}{$thisRow}")->getValue() ?: 0;
                    $this->consSheet->setCellValue("{$consCol1Letter}{$prevRow}", $val1 + $val2);
                }

                for ($consCol2 = 35; $consCol2 <= 48; $consCol2++) {
                    $consCol2Letter = Coordinate::stringFromColumnIndex($consCol2);
                    $val1 = $this->consSheet->getCell("{$consCol2Letter}{$prevRow}")->getValue() ?: 0;
                    $val2 = $this->consSheet->getCell("{$consCol2Letter}{$thisRow}")->getValue() ?: 0;
                    $this->consSheet->setCellValue("{$consCol2Letter}{$prevRow}", $val1 + $val2);
                }

                for ($consCol3 = 50; $consCol3 <= 63; $consCol3++) {
                    $consCol3Letter = Coordinate::stringFromColumnIndex($consCol3);
                    $val1 = $this->consSheet->getCell("{$consCol3Letter}{$prevRow}")->getValue() ?: 0;
                    $val2 = $this->consSheet->getCell("{$consCol3Letter}{$thisRow}")->getValue() ?: 0;
                    $this->consSheet->setCellValue("{$consCol3Letter}{$prevRow}", $val1 + $val2);
                }

                // "Delete" the consolidated row by shifting rows up
                $this->consSheet->removeRow($thisRow, 1);

                // Don't increment $thisRow since we've removed a row
                continue;
            }

            // Delete any previous invoice and docnums for the previous cluster
            for ($consCol4 = 24; $consCol4 <= 33; $consCol4++) {
                $consCol4Letter = Coordinate::stringFromColumnIndex($consCol4);
                $this->consSheet->setCellValue("{$consCol4Letter}{$prevRow}", '');
            }

            // Calculate Total VATInc Invoice Amount for the Cluster
            $vatExcAmount = $this->consSheet->getCell("U{$prevRow}")->getValue() ?: 0;
            $this->consSheet->setCellValue("T{$prevRow}", $this->vbaRound($vatExcAmount * self::VAT_RATE, 2));

            $vatExcAmount = $this->consSheet->getCell("AT{$prevRow}")->getValue() ?: 0;
            $this->consSheet->setCellValue("AS{$prevRow}", $this->vbaRound($vatExcAmount * self::VAT_RATE, 2));

            $vatExcAmount = $this->consSheet->getCell("BI{$prevRow}")->getValue() ?: 0;
            $this->consSheet->setCellValue("BH{$prevRow}", $this->vbaRound($vatExcAmount * self::VAT_RATE, 2));

            // Sum invoice amounts for display at the end
            $this->totVATInAmt += $this->consSheet->getCell("T{$prevRow}")->getValue() ?: 0;
            $this->totVATExAmt += $this->consSheet->getCell("U{$prevRow}")->getValue() ?: 0;
            $this->totEWTAmt += $this->consSheet->getCell("V{$prevRow}")->getValue() ?: 0;
            $this->totLMSFAmt += $this->consSheet->getCell("W{$prevRow}")->getValue() ?: 0;

            $prevRow = $prevRow + 1;
            $prevCluster = $this->consSheet->getCell("A{$prevRow}")->getValue();
            $thisRow = $thisRow + 1;
        }

        // Process the last cluster row (which won't be caught in the loop)
        if (!empty($this->consSheet->getCell("A{$prevRow}")->getValue())) {
            // Delete any previous invoice and docnums
            for ($consCol4 = 24; $consCol4 <= 33; $consCol4++) {
                $consCol4Letter = Coordinate::stringFromColumnIndex($consCol4);
                $this->consSheet->setCellValue("{$consCol4Letter}{$prevRow}", '');
            }

            // Calculate Total VATInc Invoice Amount for the Cluster
            $vatExcAmount = $this->consSheet->getCell("U{$prevRow}")->getValue() ?: 0;
            $this->consSheet->setCellValue("T{$prevRow}", $this->vbaRound($vatExcAmount * self::VAT_RATE, 2));

            $vatExcAmount = $this->consSheet->getCell("AT{$prevRow}")->getValue() ?: 0;
            $this->consSheet->setCellValue("AS{$prevRow}", $this->vbaRound($vatExcAmount * self::VAT_RATE, 2));

            $vatExcAmount = $this->consSheet->getCell("BI{$prevRow}")->getValue() ?: 0;
            $this->consSheet->setCellValue("BH{$prevRow}", $this->vbaRound($vatExcAmount * self::VAT_RATE, 2));

            // Sum invoice amounts for display at the end
            $this->totVATInAmt += $this->consSheet->getCell("T{$prevRow}")->getValue() ?: 0;
            $this->totVATExAmt += $this->consSheet->getCell("U{$prevRow}")->getValue() ?: 0;
            $this->totEWTAmt += $this->consSheet->getCell("V{$prevRow}")->getValue() ?: 0;
            $this->totLMSFAmt += $this->consSheet->getCell("W{$prevRow}")->getValue() ?: 0;
        }
    }

    /**
     * Update summary sheet with totals
     */
    private function updateSummarySheet(): void
    {
        // Put the Total Amounts in the Summary Sheet
        $this->summSheet->setCellValue('D5', $this->vbaRound($this->totVATInAmt, 2));
        $this->summSheet->setCellValue('E5', $this->vbaRound($this->totVATExAmt, 2));
        $this->summSheet->setCellValue('D6', $this->vbaRound($this->totLMSFAmt, 2));
        $this->summSheet->setCellValue('E6', $this->vbaRound($this->totLMSFAmt, 2));
        $this->summSheet->setCellValue('D8', $this->vbaRound(-$this->totEWTAmt, 2));
        $this->summSheet->setCellValue('E8', $this->vbaRound(-$this->totEWTAmt, 2));
    }

    private function addEstimatedSalesColumns(&$mnsrSheets, $salesMonth, $salesYear)
    {
        $salesPerformance = SalesPerformance::whereNotNull('cached_path')
            ->whereNotNull('by_store_cached_path')
            ->latest('created_at')
            ->first();

        if (!$salesPerformance) {
            foreach ($mnsrSheets as $ri => &$mnsrRow) {
                if ($ri < 7) {
                    continue;
                }
                if (empty($mnsrRow[0])) {
                    continue;
                }
                $mnsrRow[64] = 0;
                $mnsrRow[65] = 0;
                $mnsrRow[66] = 0;
            }

            return;
        }

        $salesFileContent = $this->readFile($salesPerformance->by_store_cached_path);
        if ($salesFileContent === null) {
            throw new RuntimeException("Sales Performance by-store cached file does not exist: {$salesPerformance->by_store_cached_path}");
        }
        $salesData = json_decode($salesFileContent, true);

        $targetMonth = (int)$salesMonth;
        $targetYear = (int)$salesYear;
        $monthsToCheck = [];

        // For 6-month average, we want the 6 months BEFORE the target month
        // Use DateTime to properly handle year boundaries
        $targetDate = DateTime::createFromFormat('Y-m-d', "{$targetYear}-{$targetMonth}-01");

        for ($i = 1; $i <= 6; $i++) {
            $checkDate = clone $targetDate;
            $checkDate->modify("-{$i} months");

            $monthsToCheck[] = [
                'month' => (int)$checkDate->format('n'), // n = 1-12 without leading zeros
                'year' => (int)$checkDate->format('Y')
            ];
        }

        foreach ($mnsrSheets as $ri => &$mnsrRow) {
            if ($ri < 7) {
                continue;
            }
            if (empty($mnsrRow[0])) {
                continue;
            }

            $clusterCode = $mnsrRow[0] ?? '';
            $storeCode = $mnsrRow[1] ?? '';
            if (empty($clusterCode) || empty($storeCode)) {
                continue;
            }

            // Get projected sales values (BJ=column 61, BK=column 62)
            $projectedBread = floatval($mnsrRow[61] ?? 0);
            $projectedNonBread = floatval($mnsrRow[62] ?? 0);

            // Initialize arrays to store exactly 6 months of data
            $monthlyBreadSales = [];
            $monthlyNonBreadSales = [];

            // Step 1: Collect actual sales data for each of the 6 months
            foreach ($monthsToCheck as $monthData) {
                $monthIndex = $monthData['month'];
                $yearToCheck = $monthData['year'];

                $actualBreadSales = 0;
                $actualNonBreadSales = 0;

                // Find the correct year sheet by name
                $yearSheetData = $salesData[(string)$yearToCheck] ?? null;
                if ($yearSheetData) {
                    // Find store row in the year sheet
                    $storeRow = $this->findStoreInSalesData($yearSheetData, $clusterCode, $storeCode);

                    if ($storeRow) {
                        // Bread columns: January starts at P (15), consistent across all years
                        // Non-bread columns: January starts at AD (29), consistent across all years
                        $breadColumnIndex = 14 + $monthIndex;
                        $nonBreadColumnIndex = 28 + $monthIndex;

                        $actualBreadSales = floatval($storeRow[$breadColumnIndex] ?? 0);
                        $actualNonBreadSales = floatval($storeRow[$nonBreadColumnIndex] ?? 0);
                    }
                }

                $monthlyBreadSales[] = $actualBreadSales;
                $monthlyNonBreadSales[] = $actualNonBreadSales;
            }

            // Step 2: Fill missing or zero months with projected sales (BJ/BK)
            for ($i = 0; $i < 6; $i++) {
                // If actual sales is missing or zero, and we have projected sales, use projected
                if ($monthlyBreadSales[$i] <= 0 && $projectedBread > 0) {
                    $monthlyBreadSales[$i] = $projectedBread;
                }
                if ($monthlyNonBreadSales[$i] <= 0 && $projectedNonBread > 0) {
                    $monthlyNonBreadSales[$i] = $projectedNonBread;
                }
            }

            // Step 3: Calculate 6-month averages
            $totalBreadSales = array_sum($monthlyBreadSales);
            $totalNonBreadSales = array_sum($monthlyNonBreadSales);

            $estimatedBreadSales = round($totalBreadSales / 6, 2);
            $estimatedNonBreadSales = round($totalNonBreadSales / 6, 2);
            $estimatedCombinedSales = $estimatedBreadSales + $estimatedNonBreadSales;

            // Update MNSR row with estimated values
            $mnsrRow[64] = $estimatedBreadSales;
            $mnsrRow[65] = $estimatedNonBreadSales;
            $mnsrRow[66] = $estimatedCombinedSales;
        }
        unset($mnsrRow);
    }

    /**
     * Helper method to find a store in sales data
     */
    private function findStoreInSalesData($yearSheetData, $clusterCode, $storeCode)
    {
        $emptyRowCount = 0;

        foreach ($yearSheetData as $rowIndex => $row) {
            if ($rowIndex < 4) {
                continue;
            }

            $salesClusterCode = $row[1] ?? '';
            $salesStoreCode = $row[2] ?? '';

            if (empty($salesClusterCode) && empty($salesStoreCode)) {
                $emptyRowCount++;
                if ($emptyRowCount >= 3) {
                    break;
                }
                continue;
            } else {
                $emptyRowCount = 0;
            }

            if ($salesClusterCode === $clusterCode && $salesStoreCode === $storeCode) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Save workbooks and update cached JSON data
     */
    private function saveWorkbooks($batch_id, $salesYear, $salesMonthText): void
    {
        // Create new filenames with proper prefixes
        $updatedRoyaltyFilename = "Updated-Royalty-Workbook-{$salesYear}-{$salesMonthText}.xlsx";
        $updatedMnsrFilename = "5-Monthly-Natl-Sales-Rept-{$salesMonthText}-{$salesYear}.xlsx";

        // Save the Updated Royalty Workbook
        $writer = new Xlsx($this->invWorkBook);
        $writer->save($this->monthWorkFolder . $updatedRoyaltyFilename);

        // Save updated JSON cache BEFORE creating Excel file
        $updatedMnsrCachedPath = $this->generateUploadBasePath() . '/royalty/generated/' . $batch_id . "/5-Cached-Monthly-Natl-Sales-Rept-{$salesMonthText}-{$salesYear}.json";
        $jsonContent = json_encode($this->mnsrJsonData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->uploadData($jsonContent, $updatedMnsrCachedPath);

        // Create Excel file from updated JSON data (similar to creation service)
        $updatedMnsrLocalPath = $this->salesFolder . $updatedMnsrFilename;
        $this->createExcelFromUpdatedJsonData($updatedMnsrLocalPath);

        // Upload Updated MNSR to S3
        $updatedMnsrS3Path = $this->generateUploadBasePath() . '/royalty/generated/' . $batch_id . "/{$updatedMnsrFilename}";
        $this->upload($updatedMnsrLocalPath, $updatedMnsrS3Path);

        // Clean up local MNSR file after upload
        @unlink($updatedMnsrLocalPath);

        // Create MacroOutput record for Updated MNSR
        $updatedMnsrOutput = new MacroOutput;
        $updatedMnsrOutput->batch_id = $batch_id;
        $updatedMnsrOutput->status = MacroBatchStatusEnum::Successful()->value;
        $updatedMnsrOutput->file_name = "Monthly-Natl-Sales-Rept-{$salesMonthText}-{$salesYear}.xlsx";
        $updatedMnsrOutput->file_type_id = MacroFileTypeEnum::MNSR()->value;
        $updatedMnsrOutput->file_revision_id = MacroFileRevisionEnum::MNSRUpdatedRoyaltyData()->value;
        $updatedMnsrOutput->file_path = $updatedMnsrS3Path;
        $updatedMnsrOutput->cached_path = $updatedMnsrCachedPath;
        $updatedMnsrOutput->month = $this->startOfMonth->month;
        $updatedMnsrOutput->year = $this->startOfMonth->year;
        $updatedMnsrOutput->completed_at = now();
        $updatedMnsrOutput->save();

        // Upload Updated Royalty to S3
        $updatedRoyaltyS3Path = $this->generateUploadBasePath() . '/royalty/generated/' . $batch_id . "/{$updatedRoyaltyFilename}";
        $updatedRoyaltyLocalPath = $this->monthWorkFolder . $updatedRoyaltyFilename;
        $this->upload($updatedRoyaltyLocalPath, $updatedRoyaltyS3Path);

        // Clean up local Royalty file after upload
        @unlink($updatedRoyaltyLocalPath);

        // Create cached JSON for Updated Royalty Workbook
        $updatedRoyaltyCachedPath = $this->generateUploadBasePath() . '/royalty/generated/' . $batch_id . "/Updated-Cached-Royalty-Workbook-{$salesYear}-{$salesMonthText}.json";
        $royaltyCachedData = [
            'batch_id' => $batch_id,
            'sales_month' => $this->startOfMonth->month,
            'sales_year' => $this->startOfMonth->year,
            'generated_at' => now()->toISOString(),
            'mnsr_data' => $this->mnsrJsonData,
            'totals' => [
                'total_vat_inc' => $this->totVATInAmt,
                'total_vat_exc' => $this->totVATExAmt,
                'total_ewt' => $this->totEWTAmt,
                'total_lmsf' => $this->totLMSFAmt,
            ]
        ];
        $this->uploadData(json_encode($royaltyCachedData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $updatedRoyaltyCachedPath);

        // Create MacroOutput record for Updated Royalty
        $updatedRoyaltyOutput = new MacroOutput;
        $updatedRoyaltyOutput->batch_id = $batch_id;
        $updatedRoyaltyOutput->status = MacroBatchStatusEnum::Successful()->value;
        $updatedRoyaltyOutput->file_name = "Royalty-Workbook-{$salesYear}-{$salesMonthText}.xlsx";
        $updatedRoyaltyOutput->file_type_id = MacroFileTypeEnum::Royalty()->value;
        $updatedRoyaltyOutput->file_revision_id = MacroFileRevisionEnum::RoyaltyUpdated()->value;
        $updatedRoyaltyOutput->file_path = $updatedRoyaltyS3Path;
        $updatedRoyaltyOutput->cached_path = $updatedRoyaltyCachedPath;
        $updatedRoyaltyOutput->month = $this->startOfMonth->month;
        $updatedRoyaltyOutput->year = $this->startOfMonth->year;
        $updatedRoyaltyOutput->completed_at = now();
        $updatedRoyaltyOutput->save();
    }

    /**
     * Create Excel file from updated JSON data (similar to creation service)
     */
    private function createExcelFromUpdatedJsonData($outputPath): void
    {
        // Load template directly from local storage - NEVER copy template files
        $templatePath = storage_path('app/private/royalty/cache/Z-ProForma-Natl-Monthly-Sales-by-Store.xlsx');
        if (!file_exists($templatePath)) {
            throw new RuntimeException('MNSR template not found: ' . $templatePath);
        }

        $spreadsheet = IOFactory::load($templatePath);

        // Remove existing sheets except the first one
        while ($spreadsheet->getSheetCount() > 1) {
            $spreadsheet->removeSheetByIndex(1);
        }

        // Store reference to original template sheet before modifying it
        $templateSheet = clone $spreadsheet->getActiveSheet();

        $firstSheet = true;
        // Create all adjustment sheets from JSON data
        foreach ($this->mnsrJsonData as $sheetName => $sheetData) {
            if ($firstSheet) {
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle($sheetName);
                $firstSheet = false;
            } else {
                $sheet = clone $templateSheet; // Clone original template, not the modified active sheet
                $sheet->setTitle($sheetName);
                $spreadsheet->addSheet($sheet);
            }

            $sheetData = $this->reApplyMnsrFormulas($sheetData);

            // Add totals row to the sheet data before writing to Excel
            $sheetDataWithTotals = $this->addMnsrTotals($sheetData);

            // Populate sheet with data
            $sheet->fromArray($sheetDataWithTotals, null, 'A1');

            // Check if this is a closed sheet and apply protection
            $isClosed = isset($sheetData[3][0]) && $sheetData[3][0] === ' T H I S   S H E E T   I S   C L O S E D';
            if ($isClosed) {
                // For closed sheets: apply protection and keep closure remarks
                $sheet->getProtection()->setSheet(true);
            } else {
                // For open sheets (like the latest adjustment): clear header cells A to K for rows 2-5 to match VBA behavior
                foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'] as $col) {
                    for ($row = 2; $row <= 5; $row++) {
                        $sheet->setCellValue($col . $row, '');
                    }
                }
            }

            // Apply date formatting with consistent handling
            // Header date formatting - columns Q, U, Y, AC, AH for rows 3 and 4
            foreach (['H', 'Q', 'U', 'Y', 'AC', 'AH'] as $col) {
                for ($r = 3; $r <= 4; $r++) {
                    $cell = $sheet->getCell($col . $r);
                    $val = $cell->getValue();

                    // Only process if there's actually a value to convert
                    if ($val !== null && $val !== '' && !is_numeric($val)) {
                        try {
                            $dtObj = DateTime::createFromFormat('Y-m-d', $val);
                            if ($dtObj !== false) {
                                $cell->setValue(ExcelDate::PHPToExcel($dtObj));
                            }
                        } catch (Exception $e) {
                            // Keep original value if date parsing fails
                        }
                    }

                    // Apply date formatting if there's a value
                    if ($cell->getValue() !== null && $cell->getValue() !== '') {
                        // Special formatting for H4
                        if ($col === 'H') {
                            $sheet->getStyle($col . $r)
                                ->getNumberFormat()
                                ->setFormatCode('m/d/yyyy h:mm');
                        } else {
                            $sheet->getStyle($col . $r)
                                ->getNumberFormat()
                                ->setFormatCode('d-MMM-yy');
                        }
                    }
                }
            }

            // Apply number formatting to columns BE and BF to show 0.000 format AND force values
            $maxRow = $sheet->getHighestRow();
            foreach (['BE', 'BF'] as $col) {
                for ($row = 8; $row <= $maxRow; $row++) {
                    // Check if this row has actual data by looking at column A (cluster code) or column B (store code)
                    $clusterCode = $sheet->getCell('A' . $row)->getValue();
                    $storeCode = $sheet->getCell('B' . $row)->getValue();

                    // Stop the loop if both cluster and store codes are empty (end of data)
                    if (empty($clusterCode) && empty($storeCode)) {
                        break;
                    }

                    $cell = $sheet->getCell($col . $row);
                    $currentValue = $cell->getValue();

                    // Force 0.000 for empty/null values
                    if ($currentValue === null || $currentValue === '' || $currentValue === 0) {
                        $cell->setValue(0.000);
                    }

                    // Apply number formatting to show 0.000 format
                    $sheet->getStyle($col . $row)
                        ->getNumberFormat()
                        ->setFormatCode('0.000');
                }
            }

            // Data row date formatting - columns D, E, BG, BH, BI for rows 8 and beyond
            foreach (['D', 'E', 'BG', 'BH', 'BI'] as $col) {
                for ($r = 8; $r <= $maxRow; $r++) {
                    // Check if this row has actual data
                    $clusterCode = $sheet->getCell('A' . $r)->getValue();
                    $storeCode = $sheet->getCell('B' . $r)->getValue();

                    // Skip rows that don't have branch data (except the TOTALS row)
                    if (empty($clusterCode) && empty($storeCode)) {
                        $totalLabel = $sheet->getCell('C' . $r)->getValue();
                        if ($totalLabel !== 'T O T A L S') {
                            continue; // Skip empty rows
                        }
                    }

                    $cell = $sheet->getCell($col . $r);
                    $val = $cell->getValue();

                    if ($col === 'D') {
                        // Special handling for column D
                        if (!empty($clusterCode) || !empty($storeCode)) {
                            $this->writeExcelDateColumnD($sheet, $col . $r, $val);
                        }
                    } else {
                        // Regular handling for other columns
                        if ($val !== null && $val !== '' && !is_numeric($val)) {
                            try {
                                $dtObj = DateTime::createFromFormat('Y-m-d', $val);
                                if ($dtObj !== false) {
                                    $cell->setValue(ExcelDate::PHPToExcel($dtObj));
                                }
                            } catch (Exception $e) {
                                // Keep original value if date parsing fails
                            }
                        }

                        // Apply date formatting if there's a value
                        if ($cell->getValue() !== null && $cell->getValue() !== '') {
                            $sheet->getStyle($col . $r)
                                ->getNumberFormat()
                                ->setFormatCode('d-MMM-yy');
                        }
                    }
                }
            }
        }

        // Set the first sheet as active
        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        $writer->save($outputPath);
    }

    private function writeExcelDateColumnD($sheet, $cell, $dateValue)
    {
        if ($dateValue === null || $dateValue === '') {
            // Write Excel's zero date (0) which displays as 0-Jan-00
            $sheet->getCell($cell)->setValue(0);
            $sheet->getStyle($cell)
                ->getNumberFormat()
                ->setFormatCode('d-MMM-yy');
        } elseif (is_numeric($dateValue)) {
            // If already numeric (could be 0 or an Excel date serial), keep it
            $sheet->getCell($cell)->setValue($dateValue);
            $sheet->getStyle($cell)
                ->getNumberFormat()
                ->setFormatCode('d-MMM-yy');
        } elseif ($dateValue instanceof Carbon || $dateValue instanceof DateTime) {
            // Convert to Excel date format
            $excelDate = ExcelDate::PHPToExcel($dateValue);
            $sheet->getCell($cell)->setValue($excelDate);
            $sheet->getStyle($cell)
                ->getNumberFormat()
                ->setFormatCode('d-MMM-yy');
        } elseif (is_string($dateValue)) {
            // Try to parse the string date
            $dateObj = DateTime::createFromFormat('Y-m-d', $dateValue);
            if ($dateObj !== false) {
                // Check if this is the future date (2049-12-31)
                if ($dateObj->format('Y-m-d') === '2049-12-31') {
                    // Convert to Excel date and it will display as 31-Dec-49
                    $excelDate = ExcelDate::PHPToExcel($dateObj);
                    $sheet->getCell($cell)->setValue($excelDate);
                } else {
                    // Regular date conversion
                    $excelDate = ExcelDate::PHPToExcel($dateObj);
                    $sheet->getCell($cell)->setValue($excelDate);
                }
            } else {
                // If parsing fails, write 0 (displays as 0-Jan-00)
                $sheet->getCell($cell)->setValue(0);
            }
            $sheet->getStyle($cell)
                ->getNumberFormat()
                ->setFormatCode('d-MMM-yy');
        } else {
            // For any other case, write 0
            $sheet->getCell($cell)->setValue(0);
            $sheet->getStyle($cell)
                ->getNumberFormat()
                ->setFormatCode('d-MMM-yy');
        }
    }

    private function ensureDateConsistency(&$mnsrJsonData)
    {
        // When loading cached data, ensure dates are in consistent format
        foreach ($mnsrJsonData as $sheetName => &$sheetData) {
            foreach ($sheetData as &$row) {
                if (!is_array($row)) continue;

                // For date columns that might contain various date formats
                $dateColumns = [3, 4, 58, 59, 60]; // Columns D, E, BG, BH, BI (0-indexed)

                foreach ($dateColumns as $col) {
                    if (isset($row[$col])) {
                        if ($row[$col] instanceof Carbon) {
                            $row[$col] = $row[$col]->format('Y-m-d');
                        } elseif ($row[$col] instanceof DateTime) {
                            $row[$col] = $row[$col]->format('Y-m-d');
                        }
                    }
                }
            }
        }
    }

    /**
     * Calculate week intervals for the given month and year
     */
    private function calculateWeekIntervals(string $salesMonth, int $salesYear)
    {
        $startOfMonth = DateTime::createFromFormat('j-n-Y', "1-{$salesMonth}-{$salesYear}")->setTime(0, 0);
        $startNextMonth = (clone $startOfMonth)->modify('+1 month');
        $daysInMonth = $startOfMonth->diff($startNextMonth)->days;

        // Week 1 - matches VBA Weekday(StartWk1, vbMonday) calculation
        $startWk1 = clone $startOfMonth;
        $dayOfWeek = (int)$startWk1->format('N'); // 1 (Mon) to 7 (Sun)
        $daysWk1 = 8 - $dayOfWeek;
        if ($daysWk1 < 2) {
            $daysWk1 += 7;
        }
        $endWk1 = (clone $startWk1)->modify('+' . ($daysWk1 - 1) . ' days');

        // Week 2 - always 7 days in VBA
        $daysWk2 = 7;
        $startWk2 = (clone $endWk1)->modify('+1 day');
        $endWk2 = (clone $startWk2)->modify('+' . ($daysWk2 - 1) . ' days');

        // Week 3 - always 7 days in VBA
        $daysWk3 = 7;
        $startWk3 = (clone $endWk2)->modify('+1 day');
        $endWk3 = (clone $startWk3)->modify('+' . ($daysWk3 - 1) . ' days');

        // Week 4 - VBA logic: If DaysLeft = 8 Then DaysWk4 = 8 Else DaysWk4 = 7
        $daysLeft = $daysInMonth - ($daysWk1 + $daysWk2 + $daysWk3);
        $daysWk4 = ($daysLeft == 8) ? 8 : 7;
        $startWk4 = (clone $endWk3)->modify('+1 day');
        $endWk4 = (clone $startWk4)->modify('+' . ($daysWk4 - 1) . ' days');

        // Week 5 - remaining days if any
        $daysLeft = $daysInMonth - ($daysWk1 + $daysWk2 + $daysWk3 + $daysWk4);
        if ($daysLeft > 0) {
            $daysWk5 = $daysLeft;
            $startWk5 = (clone $endWk4)->modify('+1 day');
            $endWk5 = (clone $startWk5)->modify('+' . ($daysWk5 - 1) . ' days');
        } else {
            $daysWk5 = 0;
            $startWk5 = null;
            $endWk5 = null;
        }

        return [
            'startOfMonth' => $startOfMonth,
            'startNextMonth' => $startNextMonth,
            'daysInMonth' => $daysInMonth,
            'startWk1' => $startWk1,
            'endWk1' => $endWk1,
            'daysWk1' => $daysWk1,
            'startWk2' => $startWk2,
            'endWk2' => $endWk2,
            'daysWk2' => $daysWk2,
            'startWk3' => $startWk3,
            'endWk3' => $endWk3,
            'daysWk3' => $daysWk3,
            'startWk4' => $startWk4,
            'endWk4' => $endWk4,
            'daysWk4' => $daysWk4,
            'startWk5' => $startWk5,
            'endWk5' => $endWk5,
            'daysWk5' => $daysWk5,
        ];
    }

    /**
     * Distribute estimated sales across weeks based on daily rates for weeks marked as "E"
     */
    private function distributeEstimatedSalesAcrossWeeks(&$mnsrSheets, $weekIntervals)
    {
        // Extract week days and calculate total month days
        $weekDays = [
            $weekIntervals['daysWk1'],
            $weekIntervals['daysWk2'], 
            $weekIntervals['daysWk3'],
            $weekIntervals['daysWk4'],
            $weekIntervals['daysWk5']
        ];
        $totalDays = array_sum($weekDays);

        // Column mappings for A/E indicators and sales amounts
        $weekIndicators = [14, 18, 22, 26, 31]; // O, S, W, AA, AF
        $weekSalesColumns = [
            [15, 16], // Week 1: P(bread), Q(non-bread)
            [19, 20], // Week 2: T(bread), U(non-bread)  
            [23, 24], // Week 3: X(bread), Y(non-bread)
            [27, 28], // Week 4: AB(bread), AC(non-bread)
            [32, 33]  // Week 5: AG(bread), AH(non-bread)
        ];

        foreach ($mnsrSheets as $ri => &$mnsrRow) {
            if ($ri < 7) {
                continue; // Skip header rows
            }
            if (empty($mnsrRow[0])) {
                continue; // Skip empty rows
            }

            // Get estimated sales from BM(64) and BN(65) columns
            $estimatedBread = floatval($mnsrRow[64] ?? 0);
            $estimatedNonBread = floatval($mnsrRow[65] ?? 0);

            // Skip if no estimated sales
            if ($estimatedBread <= 0 && $estimatedNonBread <= 0) {
                continue;
            }

            // Calculate daily rates
            $dailyBreadRate = $totalDays > 0 ? $estimatedBread / $totalDays : 0;
            $dailyNonBreadRate = $totalDays > 0 ? $estimatedNonBread / $totalDays : 0;

            // Get store info for validation
            $dateOpened = $mnsrRow[3] ?? null; // Column D (0-based index 3)
            $storeCode = $mnsrRow[1] ?? '';
            $clusterCode = $mnsrRow[0] ?? '';
            $dateClosed = $mnsrRow[4] ?? null; // Column E (0-based index 4)
            $tempClosure = $mnsrRow[59] ?? null; // Column BH (0-based index 59)
            $reOpening = $mnsrRow[60] ?? null; // Column BI (0-based index 60)
            
            // Get week end dates and start dates for validation
            $weekEndDates = [
                $weekIntervals['endWk1'],
                $weekIntervals['endWk2'], 
                $weekIntervals['endWk3'],
                $weekIntervals['endWk4'],
                $weekIntervals['endWk5']
            ];
            $weekStartDates = [
                $weekIntervals['startWk1'],
                $weekIntervals['startWk2'], 
                $weekIntervals['startWk3'],
                $weekIntervals['startWk4'],
                $weekIntervals['startWk5']
            ];

            // Calculate weekly estimated amounts for reference columns (AZ to BD)
            $weeklyEstimatedAmounts = [];
            $estimatedColumns = [51, 52, 53, 54, 55]; // AZ, BA, BB, BC, BD
            
            // Check each week individually to determine if store was open
            for ($week = 0; $week < 5; $week++) {
                $weeklyEstimatedAmounts[$week] = $weekDays[$week] > 0 
                    ? round($dailyBreadRate * $weekDays[$week], 2) 
                    : 0;
                
                // Check if store was open during this specific week
                $wasOpenDuringWeek = $this->wasStoreOpenDuringWeek(
                    $dateOpened, 
                    $weekEndDates[$week], 
                    $weekStartDates[$week],
                    $tempClosure,
                    $reOpening,
                    $dateClosed
                );
                
                // Only populate AZ-BD columns for weeks when store was open
                if ($wasOpenDuringWeek && $weeklyEstimatedAmounts[$week] > 0) {
                    $mnsrRow[$estimatedColumns[$week]] = $weeklyEstimatedAmounts[$week];
                }
            }

            // Check each week and update if marked "E" - only for weeks when store was open
            for ($week = 0; $week < 5; $week++) {
                // Check if store was open during this specific week
                $wasOpenDuringWeek = $this->wasStoreOpenDuringWeek(
                    $dateOpened, 
                    $weekEndDates[$week], 
                    $weekStartDates[$week],
                    $tempClosure,
                    $reOpening,
                    $dateClosed
                );
                
                // Only process weeks when store was actually open AND marked as "E"
                if ($wasOpenDuringWeek && 
                    ($mnsrRow[$weekIndicators[$week]] ?? '') === 'E' && 
                    $weekDays[$week] > 0) {
                    
                    // Calculate amounts for this week
                    $breadAmount = round($dailyBreadRate * $weekDays[$week], 2);
                    $nonBreadAmount = round($dailyNonBreadRate * $weekDays[$week], 2);

                    // Update the sales columns for this week
                    $mnsrRow[$weekSalesColumns[$week][0]] = $breadAmount; // Bread
                    $mnsrRow[$weekSalesColumns[$week][1]] = $nonBreadAmount; // Non-bread
                }
            }
        }
        unset($mnsrRow);
    }

    /**
     * Parse date value using convertSerialToDate and Carbon only
     * 
     * @param mixed $dateValue The date value to parse
     * @return Carbon|null Parsed date as Carbon instance, null if invalid
     */
    private function parseDateValue($dateValue): ?Carbon
    {
        if ($dateValue === null || $dateValue === '' || $dateValue === 0) {
            return null;
        }

        try {
            // Use existing convertSerialToDate for numeric values
            if (is_numeric($dateValue)) {
                // Check if it's an Excel serial number (> 25569 = 1970-01-01)
                if ($dateValue > 25569) {
                    $dateString = $this->convertSerialToDate($dateValue);
                    if ($dateString) {
                        return Carbon::createFromFormat('Y-m-d', $dateString);
                    }
                }
                return null;
            }

            // Handle string dates with Carbon
            if (is_string($dateValue)) {
                // Try Y-m-d format first (most common in DB)
                try {
                    return Carbon::createFromFormat('Y-m-d', $dateValue);
                } catch (Exception $e) {
                    // Try d-M-y format (Excel display format)
                    try {
                        return Carbon::createFromFormat('d-M-y', $dateValue);
                    } catch (Exception $e2) {
                        // Try parsing with Carbon's intelligent parser
                        try {
                            return Carbon::parse($dateValue);
                        } catch (Exception $e3) {
                            return null;
                        }
                    }
                }
            }

            // Handle Carbon objects
            if ($dateValue instanceof Carbon) {
                return clone $dateValue;
            }

            // Handle DateTime objects by converting to Carbon
            if ($dateValue instanceof DateTime) {
                return Carbon::instance($dateValue);
            }
        } catch (Exception $e) {
            return null;
        }

        return null;
    }

    /**
     * Validate store operational status with comprehensive temp closure logic
     * 
     * @param mixed $dateOpened Date the store opened
     * @param Carbon $weekEndDate End date of the week being checked
     * @param Carbon|null $weekStartDate Start date of the week being checked
     * @param mixed $tempClosure Temp closure date
     * @param mixed $reOpening Re-opening date
     * @return bool True if store was operational during the week
     */
    private function validateStoreOperationalStatus($dateOpened, $weekEndDate, $weekStartDate, $tempClosure, $reOpening, $dateClosed = null): bool
    {
        // Level 0: Permanent closure check (from MNSRService shouldEstimateForWeek logic)
        // MNSRService logic: (empty($row[4]) || $row[4] > $weekStart) - include only if closure date > week start
        if ($dateClosed !== null && $weekStartDate !== null) {
            $dateClosedParsed = $this->parseDateValue($dateClosed);
            $weekStartParsed = is_string($weekStartDate) ? Carbon::parse($weekStartDate) : Carbon::instance($weekStartDate);
            
            if ($dateClosedParsed && $weekStartParsed) {
                // Compare only the date portion (ignoring time) to match MNSRService logic
                $closureDate = $dateClosedParsed->startOfDay();
                $weekStartDate = $weekStartParsed->startOfDay();
                
                // If closure date is NOT greater than week start, store is closed during this week
                if (!($closureDate > $weekStartDate)) {
                    return false; // Store is permanently closed
                }
            }
        }
        
        // Level 1: Handle null/empty/zero date opened
        if ($dateOpened === null || $dateOpened === '' || $dateOpened === 0) {
            return false;
        }

        // Parse date opened using Carbon
        $dateOpenedObj = $this->parseDateValue($dateOpened);
        if (!$dateOpenedObj) {
            return false;
        }

        // Level 2: Future date check (2049-12-31)
        if ($dateOpenedObj->format('Y-m-d') === '2049-12-31') {
            return false;
        }

        // Level 3: Basic date opened vs week validation
        if ($dateOpenedObj > $weekEndDate) {
            return false;
        }

        // If weekStartDate is null, skip temp closure validation (backward compatibility)
        if ($weekStartDate === null) {
            return true;
        }

        // Level 4: Temp Closure/Re-opening validation (4 scenarios from VBA)
        $tc = $this->parseDateValue($tempClosure);
        $ro = $this->parseDateValue($reOpening);

        // Scenario 1: Both TC and RO are blank - bypass sales date check ONLY if sales date >= week start
        // This matches the MNSRService shouldEstimateForWeek logic to prevent estimating weeks far in the future from sales date
        if (!$tc && !$ro) {
            // Only bypass if sales date is empty OR sales date >= week start (not way before)
            if ($dateClosed === null || $dateClosed === '' || 
                ($weekStartDate !== null && $dateClosed >= $weekStartDate->format('Y-m-d'))) {
                return true;
            }
        }

        // Scenario 2: TC is blank, RO has date
        if (!$tc && $ro) {
            return $ro <= $weekEndDate;
        }

        // Scenario 3: TC has date, RO is blank  
        if ($tc && !$ro) {
            return $tc > $weekStartDate;
        }

        // Scenario 4: Both TC and RO have dates
        if ($tc && $ro) {
            // Sub-case 4A: Normal sequence (close then reopen)
            if ($tc < $ro) {
                return ($tc > $weekStartDate) || ($ro <= $weekEndDate);
            }
            // Sub-case 4B: Unusual sequence (TC >= RO)  
            else {
                if ($tc > $weekStartDate) {
                    return true;
                }
                return ($ro <= $weekEndDate && $tc > $weekStartDate);
            }
        }

        return false;
    }


    /**
     * Check if store was open during a specific week
     * 
     * @param mixed $dateOpened The date opened value from the MNSR row
     * @param DateTime $weekEndDate The end date of the week being checked
     * @return bool True if store was open during this week, false otherwise
     */
    private function wasStoreOpenDuringWeek($dateOpened, $weekEndDate, $weekStartDate = null, $tempClosure = null, $reOpening = null, $dateClosed = null): bool
    {
        // Convert weekEndDate to Carbon if it's a DateTime
        if ($weekEndDate instanceof DateTime) {
            $weekEndDate = Carbon::instance($weekEndDate);
        }

        // Use enhanced validation with closure validation parameters
        return $this->validateStoreOperationalStatus($dateOpened, $weekEndDate, $weekStartDate, $tempClosure, $reOpening, $dateClosed);
    }
}
