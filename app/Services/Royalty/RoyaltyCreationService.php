<?php

namespace App\Services\Royalty;

use App\Enums\MacroBatchStatusEnum;
use App\Enums\MacroFileRevisionEnum;
use App\Enums\MacroFileTypeEnum;
use App\Models\Royalty\MacroBatch;
use App\Models\Royalty\MacroOutput;
use App\Models\SalesPerformance;
use App\Models\User;
use App\Notifications\Royalty\GenerateRoyaltyNotification;
use App\Traits\ErrorLogger;
use App\Traits\HandlesRoyaltyData;
use App\Traits\ManageFilesystems;
use App\Traits\VbaRounding;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

class RoyaltyCreationService
{
    use ErrorLogger, HandlesRoyaltyData, ManageFilesystems, VbaRounding;

    private $mnsrJsonData; // Store the MNSR JSON data

    public function generateRoyalty(int $batch_id, int $salesMonth, int $salesYear): void
    {
        DB::beginTransaction();

        try {

            //
            // 1. Set up dates
            //
            $startOfMonth = Carbon::createFromDate($salesYear, $salesMonth, 1);
            $daysInMonth = $startOfMonth->daysInMonth;
            $endOfMonth = $startOfMonth->copy()->endOfMonth();
            $monthAbbrev = $startOfMonth->format('M');

            //
            // 2. Load MNSR data from cached JSON instead of Excel file
            //
            // Create temporary directory for processing
            $tempDir = sys_get_temp_dir() . '/royalty_' . $batch_id . '_' . uniqid();
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            $outputFolder = $tempDir . '/';

            $macroOutput = MacroOutput::where('batch_id', $batch_id)
                ->where('file_type_id', MacroFileTypeEnum::MNSR()->value)
                ->where('file_revision_id', MacroFileRevisionEnum::MNSRAddedJBMISData()->value)
                ->orderBy('created_at', 'desc')
                ->first();

            // Load MNSR data from cached JSON
            $this->mnsrJsonData = json_decode($this->readFile($macroOutput->cached_path), true);

            // Convert any formulas in the JSON data to actual calculated values
            foreach ($this->mnsrJsonData as $sheetName => &$sheetData) {
                $sheetData = $this->convertBulk($sheetData);
            }
            unset($sheetData); // CRITICAL: Break the reference to prevent corruption

            foreach ($this->mnsrJsonData as $sheetName => &$sheetData) {
                $weekIntervals = $this->calculateWeekIntervals($salesMonth, $salesYear);
                $this->addEstimatedSalesColumns($sheetData, $salesMonth, $salesYear);
                $this->distributeEstimatedSalesAcrossWeeks($sheetData, $weekIntervals);
            }
            unset($sheetData); // CRITICAL: Break the reference to prevent corruption

            // Find the latest (highest numbered) open sheet
            $latestSheetData = null;
            $latestSheetName = null;
            $highestAdjustNumber = -1;
            foreach ($this->mnsrJsonData as $sheetName => $sheetData) {
                // Check if sheet is closed (check cell A4 = row 4, col A = index [3][0])
                if (isset($sheetData[3][0]) && $sheetData[3][0] === ' T H I S   S H E E T   I S   C L O S E D') {
                    continue; // Skip closed sheets
                }

                // Extract adjust number from sheet name (Adjust-X)
                if (preg_match('/^Adjust-(\d+)$/i', $sheetName, $matches)) {
                    $adjustNumber = (int)$matches[1];
                    if ($adjustNumber > $highestAdjustNumber) {
                        $highestAdjustNumber = $adjustNumber;
                        $latestSheetData = $sheetData;
                        $latestSheetName = $sheetName;
                    }
                } else {
                    // Handle non-Adjust sheets (fallback for original sheet names)
                    if ($latestSheetData === null) {
                        $latestSheetData = $sheetData;
                        $latestSheetName = $sheetName;
                    }
                }
            }

            if ($latestSheetData === null) {
                throw new RuntimeException('This monthly report has already been processed. Use update program.');
            }

            // Load template directly from local storage - NEVER copy template files
            $royaltyTemplatePath = storage_path('app/private/royalty/cache/Z-ProForma-Royalty-Workbook.xlsx');
            if (!file_exists($royaltyTemplatePath)) {
                throw new RuntimeException('Royalty workbook template not found: ' . $royaltyTemplatePath);
            }

            $localRoyaltyFileOutputPath = "{$outputFolder}Created-Royalty-Workbook-{$salesYear}-{$monthAbbrev}.xlsx";

            //
            // 3. Load the Pro-Forma template directly
            //
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx;
            $proFormaBook = $reader->load($royaltyTemplatePath);
            $storSheet = $proFormaBook->getSheetByName('StorSheet-0');
            $consSheet = null; // to be created later
            $summSheet = $proFormaBook->getSheetByName('Summary-0');

            // unprotect stor & summary
            $storSheet->getProtection()->setSheet(false);
            $summSheet->getProtection()->setSheet(false);

            //
            // 4. Copy store data from MNSR JSON data → StorSheet
            //
            $salesRow = 8; // Starting row in Excel (1-based)
            $storRow = 6;
            while (true) {
                // Convert Excel row to 0-based array index
                $jsonRowIndex = $salesRow - 1;
                // Check if we've reached the end of data
                if ($jsonRowIndex >= count($latestSheetData)) {
                    break;
                }
                // Get the row from JSON data
                $mnsrRow = $latestSheetData[$jsonRowIndex];
                // Check if cluster (column A = index 0) is empty - break if so
                if (empty(trim((string)($mnsrRow[0] ?? '')))) {
                    break;
                }

                // Copy data from JSON array (0-based indexing) to StorSheet
                $storSheet->setCellValueByColumnAndRow(1, $storRow, $mnsrRow[0] ?? ''); // Cluster (A = index 0)
                $storSheet->setCellValueByColumnAndRow(2, $storRow, $mnsrRow[1] ?? ''); // Store (B = index 1)
                $storSheet->setCellValueByColumnAndRow(3, $storRow, $mnsrRow[2] ?? ''); // Store Name (C = index 2)
                $storSheet->setCellValueByColumnAndRow(4, $storRow, $mnsrRow[8] ?? ''); // Company (I = index 8)
                $storSheet->setCellValueByColumnAndRow(5, $storRow, $mnsrRow[7] ?? ''); // Region (H = index 7)
                $storSheet->setCellValueByColumnAndRow(6, $storRow, $mnsrRow[9] ?? ''); // District (J = index 9)

                // FIX 1: G column should be 0 if no value
                $storSheet->setCellValueByColumnAndRow(7, $storRow, (float)($mnsrRow[56] ?? 0)); // Old Royalty % (BE = index 56)
                $storSheet->setCellValueByColumnAndRow(8, $storRow, (float)($mnsrRow[57] ?? 0)); // New Royalty % (BF = index 57)

                // FIX 2: Column I (Effect Date) - convert to proper Excel date format
                $effectDateValue = $mnsrRow[58] ?? '';
                if (!empty($effectDateValue)) {
                    if (is_numeric($effectDateValue)) {
                        // Already an Excel serial number
                        $storSheet->setCellValueByColumnAndRow(9, $storRow, $effectDateValue);
                    } else {
                        // Convert string date to Excel serial number
                        try {
                            $dateObj = Carbon::parse($effectDateValue);
                            $excelDate = ExcelDate::PHPToExcel($dateObj);
                            $storSheet->setCellValueByColumnAndRow(9, $storRow, $excelDate);
                        } catch (Exception $e) {
                            $storSheet->setCellValueByColumnAndRow(9, $storRow, $effectDateValue);
                        }
                    }
                    // Apply date formatting to column I
                    $storSheet->getStyleByColumnAndRow(9, $storRow)
                        ->getNumberFormat()
                        ->setFormatCode('m/d/yyyy');
                }

                $storSheet->setCellValueByColumnAndRow(10, $storRow, $this->vbaRound((float)($mnsrRow[37] ?? 0), 2)); // Actual Bread (AL = index 37)
                $storSheet->setCellValueByColumnAndRow(11, $storRow, $this->vbaRound((float)($mnsrRow[38] ?? 0), 2)); // Actual Non-Bread (AM = index 38)
                $storSheet->setCellValueByColumnAndRow(15, $storRow, $this->vbaRound((float)($mnsrRow[41] ?? 0), 2)); // Est. Bread (AP = index 41)
                $storSheet->setCellValueByColumnAndRow(16, $storRow, $this->vbaRound((float)($mnsrRow[42] ?? 0), 2)); // Est. Non-Bread (AQ = index 42)

                $storSheet->setCellValueByColumnAndRow(65, $storRow, $this->vbaRound((float)($mnsrRow[64] ?? 0), 2)); // BM - Estimated Bread Sales
                $storSheet->setCellValueByColumnAndRow(66, $storRow, $this->vbaRound((float)($mnsrRow[65] ?? 0), 2)); // BN - Estimated Non-Bread Sales
                $storSheet->setCellValueByColumnAndRow(67, $storRow, $this->vbaRound((float)($mnsrRow[66] ?? 0), 2)); // BO - Estimated Combined Sales

                $storSheet->getStyleByColumnAndRow(65, $storRow)->getNumberFormat()->setFormatCode('#,##0.00');
                $storSheet->getStyleByColumnAndRow(66, $storRow)->getNumberFormat()->setFormatCode('#,##0.00');
                $storSheet->getStyleByColumnAndRow(67, $storRow)->getNumberFormat()->setFormatCode('#,##0.00');

                $storSheet->setCellValueByColumnAndRow(64, $storRow, $mnsrRow[63] ?? ''); // Warehouse Code (BL = index 63)

                $salesRow++;
                $storRow++;
            }

            //
            // 5. Create processed MNSR data and save to Excel format
            //
            $today = Carbon::now()->format('n/j/Y g:i:s A');

            // Find the next Adjust number by checking existing sheets
            $maxAdjustNumber = -1;
            foreach (array_keys($this->mnsrJsonData) as $sheetName) {
                if (preg_match('/^Adjust-(\d+)$/', $sheetName, $matches)) {
                    $adjustNumber = (int)$matches[1];
                    $maxAdjustNumber = max($maxAdjustNumber, $adjustNumber);
                }
            }

            $newAdjustNumber = $maxAdjustNumber + 1;
            $newSheetName = "Adjust-{$newAdjustNumber}";

            // Create a copy of the latest sheet data and mark it as closed
            $closedMnsrData = $latestSheetData;
            $closedMnsrData[1][0] = ' T H E S E   A M O U N T S   W E R E'; // A2
            $closedMnsrData[2][0] = " P R O C E S S E D   O N: {$today}"; // A3
            $closedMnsrData[3][0] = ' T H I S   S H E E T   I S   C L O S E D'; // A4

            // Update the existing latest sheet in the data structure to be closed
            $this->mnsrJsonData[$latestSheetName] = $closedMnsrData;

            // Add the new adjustment sheet (copy of original latest sheet without closure marks)
            // Clear header cells A to K (columns 0-10) for rows 2-5 (indices 1-4) to match VBA behavior
            $newSheetData = $latestSheetData;
            for ($row = 1; $row <= 4; $row++) { // rows 2-5 (1-based) = indices 1-4 (0-based)
                for ($col = 0; $col <= 10; $col++) { // columns A-K (1-based) = indices 0-10 (0-based)
                    $newSheetData[$row][$col] = '';
                }
            }
            $this->mnsrJsonData[$newSheetName] = $newSheetData;

            // Create Excel file with all sheets
            $modifiedMnsrFileName = "4-Monthly-Natl-Sales-Rept-{$monthAbbrev}-{$salesYear}.xlsx";
            $modifiedMnsrLocalPath = "{$outputFolder}{$modifiedMnsrFileName}";
            $modifiedMnsrS3Path = $this->generateUploadBasePath() . '/royalty/generated/' . $batch_id . "/{$modifiedMnsrFileName}";
            $modifiedMnsrCachedPath = $this->generateUploadBasePath() . '/royalty/generated/' . $batch_id . "/4-Cached-Monthly-Natl-Sales-Rept-{$monthAbbrev}-{$salesYear}.json";

            // Upload cached JSON data with all sheets BEFORE creating Excel file
            $this->uploadData(json_encode($this->mnsrJsonData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $modifiedMnsrCachedPath);

            // Create Excel file from JSON data with all sheets
            $this->createExcelFromAllSheets($this->mnsrJsonData, $modifiedMnsrLocalPath);

            // Upload to default disk
            $this->upload($modifiedMnsrLocalPath, $modifiedMnsrS3Path);

            // Create MacroOutput record for the modified MNSR
            $modifiedMnsrOutput = new MacroOutput;
            $modifiedMnsrOutput->batch_id = $batch_id;
            $modifiedMnsrOutput->status = MacroBatchStatusEnum::Successful()->value;
            $modifiedMnsrOutput->file_name = "Monthly-Natl-Sales-Rept-{$monthAbbrev}-{$salesYear}.xlsx";
            $modifiedMnsrOutput->file_type_id = MacroFileTypeEnum::MNSR()->value;
            $modifiedMnsrOutput->file_revision_id = MacroFileRevisionEnum::MNSRCreatedRoyaltyData()->value;
            $modifiedMnsrOutput->file_path = $modifiedMnsrS3Path;
            $modifiedMnsrOutput->cached_path = $modifiedMnsrCachedPath;
            $modifiedMnsrOutput->month = $salesMonth;
            $modifiedMnsrOutput->year = $salesYear;
            $modifiedMnsrOutput->completed_at = now();
            $modifiedMnsrOutput->save();

            //
            // 6. Calculate per‐store royalties, NMSF, LMSF, VAT, EWT (Cols 12–23) - KEEP EXACTLY AS ORIGINAL
            //
            $lmsfPct = 0.005;
            $nmsfPct = 0.01;
            $storRow = 6;
            while (trim((string)$storSheet->getCellByColumnAndRow(1, $storRow)->getValue()) !== '') {
                $VATExInvAmt = 0.0;


                // read inputs
                $oldPct = (float)$storSheet->getCellByColumnAndRow(7, $storRow)->getValue();
                $newPct = (float)$storSheet->getCellByColumnAndRow(8, $storRow)->getValue();

                // Handle effect date - check if it's a date string or Excel serial number
                $effDateValue = $storSheet->getCellByColumnAndRow(9, $storRow)->getValue();

                if ($effDateValue === null || $effDateValue === '') {
                    // Handle null/empty effect date - you may want to skip or use a default
                    $effDate = $endOfMonth; // or another appropriate default
                } elseif (is_numeric($effDateValue)) {
                    // If it's already an Excel serial number (including 0 for 0-Jan-00)
                    if ($effDateValue == 0) {
                        // This represents 0-Jan-00, which is December 30, 1899
                        // You may want to handle this specially
                        $effDate = Carbon::createFromDate(1899, 12, 30);
                    } else {
                        $effDate = Carbon::instance(ExcelDate::excelToDateTimeObject($effDateValue));
                    }
                } else {
                    // If it's a string date, parse it directly
                    try {
                        $effDate = Carbon::parse($effDateValue);
                    } catch (Exception $e) {
                        // If parsing fails, use a default
                        $effDate = $endOfMonth;
                    }
                }


                //            if (is_numeric($effDateValue)) {
                //                $effDate = Carbon::instance(ExcelDate::excelToDateTimeObject($effDateValue));
                //            } else {
                //                // If it's a string date, parse it directly
                //                $effDate = Carbon::parse($effDateValue);
                //            }

                $actBread = (float)$storSheet->getCellByColumnAndRow(10, $storRow)->getValue();
                $actNon = (float)$storSheet->getCellByColumnAndRow(11, $storRow)->getValue();
                $estBread = (float)$storSheet->getCellByColumnAndRow(15, $storRow)->getValue();
                $estNon = (float)$storSheet->getCellByColumnAndRow(16, $storRow)->getValue();

                // — Actual Royalties → J & K → L & M —
                if ($effDate < $startOfMonth) {
                    // Entire period at new royalty percentage
                    $grossActBread = $this->vbaRound($actBread * $newPct, 2);
                    $grossActNon = $this->vbaRound($actNon * $newPct, 2);
                } elseif ($effDate > $endOfMonth) {
                    // Entire period at old royalty percentage
                    $grossActBread = $this->vbaRound($actBread * $oldPct, 2);
                    $grossActNon = $this->vbaRound($actNon * $oldPct, 2);
                } else {
                    // Royalty percent changed during this month
                    // VBA: OldPeriod = NewRoyaltyEffectDt - StartofMonth (INTEGER calculation like VBA)
                    $oldPeriod = (int)($effDate->day - $startOfMonth->day);
                    $newPeriod = $daysInMonth - $oldPeriod;


                    // Calculate gross royalties for old percentage period
                    $grossActBreadOld = $this->vbaRound(($actBread * $oldPct) * ($oldPeriod / $daysInMonth), 2);
                    $grossActNonOld = $this->vbaRound(($actNon * $oldPct) * ($oldPeriod / $daysInMonth), 2);

                    // Calculate gross royalties for new percentage period
                    $grossActBreadNew = $this->vbaRound(($actBread * $newPct) * ($newPeriod / $daysInMonth), 2);
                    $grossActNonNew = $this->vbaRound(($actNon * $newPct) * ($newPeriod / $daysInMonth), 2);


                    // Add them together
                    $grossActBread = $grossActBreadOld + $grossActBreadNew;
                    $grossActNon = $grossActNonOld + $grossActNonNew;
                }

                $netActBread = $this->vbaRound($grossActBread / 1.12, 2);
                $netActNon = $this->vbaRound($grossActNon / 1.12, 2);



                $storSheet->setCellValueByColumnAndRow(12, $storRow, $this->vbaRound($netActBread, 2));
                $storSheet->setCellValueByColumnAndRow(13, $storRow, $this->vbaRound($netActNon, 2));
                $VATExInvAmt += $netActBread + $netActNon;

                // — Actual NMSF → Col 14 —
                if ($oldPct == 0 && $newPct == 0) {
                    $netActNmsf = 0;
                } else {
                    $grossActNmsf = $this->vbaRound(($actBread + $actNon) * $nmsfPct, 2);
                    $netActNmsf = $this->vbaRound($grossActNmsf / 1.12, 2);
                }


                $storSheet->setCellValueByColumnAndRow(14, $storRow, $this->vbaRound($netActNmsf, 2));
                $VATExInvAmt += $netActNmsf;

                // — Accrued Royalties → Cols 17–18 (Q,R) —
                if ($effDate < $startOfMonth) {
                    // Entire period at new royalty percentage
                    $grossAcrBread = $this->vbaRound($estBread * $newPct, 2);
                    $grossAcrNon = $this->vbaRound($estNon * $newPct, 2);
                } elseif ($effDate > $endOfMonth) {
                    // Entire period at old royalty percentage
                    $grossAcrBread = $this->vbaRound($estBread * $oldPct, 2);
                    $grossAcrNon = $this->vbaRound($estNon * $oldPct, 2);
                } else {
                    // Royalty percent changed during this month
                    // VBA: OldPeriod = NewRoyaltyEffectDt - StartofMonth (INTEGER calculation like VBA)
                    $oldPeriod = (int)($effDate->day - $startOfMonth->day);
                    $newPeriod = $daysInMonth - $oldPeriod;

                    // Calculate gross royalties for old percentage period
                    $grossAcrBreadOld = $this->vbaRound(($estBread * $oldPct) * ($oldPeriod / $daysInMonth), 2);
                    $grossAcrNonOld = $this->vbaRound(($estNon * $oldPct) * ($oldPeriod / $daysInMonth), 2);

                    // Calculate gross royalties for new percentage period
                    $grossAcrBreadNew = $this->vbaRound(($estBread * $newPct) * ($newPeriod / $daysInMonth), 2);
                    $grossAcrNonNew = $this->vbaRound(($estNon * $newPct) * ($newPeriod / $daysInMonth), 2);

                    // Add them together
                    $grossAcrBread = $grossAcrBreadOld + $grossAcrBreadNew;
                    $grossAcrNon = $grossAcrNonOld + $grossAcrNonNew;
                }

                $netAcrBread = $this->vbaRound($grossAcrBread / 1.12, 2);
                $netAcrNon = $this->vbaRound($grossAcrNon / 1.12, 2);


                $storSheet->setCellValueByColumnAndRow(17, $storRow, $this->vbaRound($netAcrBread, 2));
                $storSheet->setCellValueByColumnAndRow(18, $storRow, $this->vbaRound($netAcrNon, 2));
                $VATExInvAmt += $netAcrBread + $netAcrNon;

                // — Accrued NMSF → Col 19 (S) —
                if ($oldPct != 0 && $newPct != 0) {
                    $grossAcrNmsf = $this->vbaRound(($estBread + $estNon) * $nmsfPct, 2);
                    $netAcrNmsf = $grossAcrNmsf / 1.12; // not rounded off
                } else {
                    $netAcrNmsf = 0;
                }


                $storSheet->setCellValueByColumnAndRow(19, $storRow, $this->vbaRound($netAcrNmsf, 2));
                $VATExInvAmt += $netAcrNmsf;

                // — LMSF → Col 23 (W) —
                $totalSales = $actBread + $actNon + $estBread + $estNon;
                $lmsfInvAmt = $this->vbaRound($totalSales * $lmsfPct, 2);
                $company = (string)$storSheet->getCellByColumnAndRow(4, $storRow)->getValue();
                if ($company === 'ZZZ') {
                    $storSheet->setCellValueByColumnAndRow(23, $storRow, $this->vbaRound($lmsfInvAmt, 2));
                }

                // — Final VAT & EWT → Cols 20–22 (T–V) —
                // Round VATExInvAmt to fix floating point precision issues
                $VATExInvAmt = $this->vbaRound($VATExInvAmt, 2);
                $vatInInvAmt = $this->vbaRound($VATExInvAmt * 1.12, 2);
                $storSheet->setCellValueByColumnAndRow(20, $storRow, $this->vbaRound($vatInInvAmt, 2));
                $storSheet->setCellValueByColumnAndRow(21, $storRow, $this->vbaRound($VATExInvAmt, 2));

                // FIX 4: Apply number formatting to column T (column 20)
                $storSheet->getStyleByColumnAndRow(20, $storRow)
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');

                $company = (string)$storSheet->getCellByColumnAndRow(4, $storRow)->getValue();
                $ewt = $company === 'ZZZ'
                    ? 0
                    : $this->vbaRound($VATExInvAmt * 0.02, 2);
                $storSheet->setCellValueByColumnAndRow(22, $storRow, $this->vbaRound($ewt, 2));


                $storRow++;
            }

            //
            // 7. Sort StorSheet by Company (col D) then Cluster (col A) - KEEP EXACTLY AS ORIGINAL
            //
            $allRows = [];
            for ($r = 6; ; $r++) {
                $cluster = trim((string)$storSheet->getCellByColumnAndRow(1, $r)->getValue());
                if ($cluster === '') {
                    break;
                }
                $rowVals = [];
                for ($c = 1; $c <= 67; $c++) {
                    $rowVals[$c] = $storSheet->getCellByColumnAndRow($c, $r)->getValue();
                }
                $allRows[] = $rowVals;
            }

            usort($allRows, function ($a, $b) {
                if ($a[4] === $b[4]) {
                    return $a[1] <=> $b[1];
                }

                return $a[4] <=> $b[4];
            });

            // clear and rewrite
            $storSheet->removeRow(6, count($allRows));
            $r = 6;
            foreach ($allRows as $rowVals) {
                for ($c = 1; $c <= 67; $c++) {
                    $storSheet->setCellValueByColumnAndRow($c, $r, $rowVals[$c]);
                }
                // Reapply formatting after sorting
                $storSheet->getStyleByColumnAndRow(9, $r)
                    ->getNumberFormat()
                    ->setFormatCode('m/d/yyyy');
                $storSheet->getStyleByColumnAndRow(20, $r)
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');

                $storSheet->getStyleByColumnAndRow(65, $r)->getNumberFormat()->setFormatCode('#,##0.00');
                $storSheet->getStyleByColumnAndRow(66, $r)->getNumberFormat()->setFormatCode('#,##0.00');
                $storSheet->getStyleByColumnAndRow(67, $r)->getNumberFormat()->setFormatCode('#,##0.00');

                $r++;
            }

            //
            // 8. Consolidate by Cluster → ConsSheet-0 - KEEP EXACTLY AS ORIGINAL
            //
            $consSheet = clone $storSheet;
            $consSheet->setTitle('ConsSheet-0');
            $proFormaBook->addSheet($consSheet);

            // Replicate VBA's in-place consolidation algorithm exactly
            $consolidationRowCount = 1; // VBA starts at 1
            $prevRow = 6;
            $thisRow = 7;

            // Get first cluster for comparison
            $prevCluster = trim((string)$consSheet->getCellByColumnAndRow(1, $prevRow)->getCalculatedValue());
            $prevStoreCode = trim((string)$consSheet->getCellByColumnAndRow(2, $prevRow)->getCalculatedValue());

            // Process remaining rows using VBA's algorithm
            while (true) {
                $thisCluster = trim((string)$consSheet->getCellByColumnAndRow(1, $thisRow)->getCalculatedValue());

                if ($thisCluster === '') {
                    break;
                }

                $consolidationRowCount++;
                $thisStoreCode = trim((string)$consSheet->getCellByColumnAndRow(2, $thisRow)->getCalculatedValue());

                if ($thisCluster === $prevCluster) {
                    // Same cluster - add amounts and delete this row (VBA logic)
                    // Sum columns 10-23 (VBA: ConsCol1 = 10 To 23)
                    for ($c = 10; $c <= 23; $c++) {
                        $prevVal = (float)$consSheet->getCellByColumnAndRow($c, $prevRow)->getCalculatedValue();
                        $thisVal = (float)$consSheet->getCellByColumnAndRow($c, $thisRow)->getCalculatedValue();
                        $consSheet->setCellValueByColumnAndRow($c, $prevRow, $prevVal + $thisVal);
                    }

                    // Delete the current row (VBA: ConsSheet.Cells(ThisRow, 1).EntireRow.Delete)
                    $consSheet->removeRow($thisRow, 1);

                    // Don't increment thisRow since we deleted a row (VBA: GoTo CompNextRow)
                    continue;
                } else {
                    // Different cluster - recalculate VAT inclusive for previous cluster (VBA logic)
                    $vatExAmt = (float)$consSheet->getCellByColumnAndRow(21, $prevRow)->getCalculatedValue();
                    $vatInAmt = $this->vbaRound($vatExAmt * 1.12, 2);
                    $consSheet->setCellValueByColumnAndRow(20, $prevRow, $vatInAmt);

                    // Move to next row
                    $prevRow++;
                    $prevCluster = $thisCluster;
                    $prevStoreCode = $thisStoreCode;
                }

                $thisRow++;
            }

            // Handle the last cluster's VAT calculation
            if (!empty($prevCluster)) {
                $vatExAmt = (float)$consSheet->getCellByColumnAndRow(21, $prevRow)->getCalculatedValue();
                $vatInAmt = $this->vbaRound($vatExAmt * 1.12, 2);
                $consSheet->setCellValueByColumnAndRow(20, $prevRow, $vatInAmt);
            }


            // Apply formatting to the consolidated data (VBA doesn't need rewriting since it's in-place)
            for ($r = 6; ; $r++) {
                $cluster = trim((string)$consSheet->getCellByColumnAndRow(1, $r)->getCalculatedValue());
                if ($cluster === '') {
                    break;
                }

                // Apply number formatting to column T (20)
                $consSheet->getStyleByColumnAndRow(20, $r)
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');

                // Apply date formatting to column I (9)
                $consSheet->getStyleByColumnAndRow(9, $r)
                    ->getNumberFormat()
                    ->setFormatCode('m/d/yyyy');
            }

            //
            // 9. Copy "this month" columns (10–23 → +25) on both sheets - KEEP EXACTLY AS ORIGINAL
            // Use the exact data ranges, not the break-on-blank logic
            $lastStorRow = $storSheet->getHighestRow();
            for ($row = 6; $row <= $lastStorRow; $row++) {
                for ($col = 10; $col <= 23; $col++) {
                    $value = $storSheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
                    $storSheet->setCellValueByColumnAndRow($col + 25, $row, $this->vbaRound((float)$value, 2));
                }
            }

            // Now for ConsSheet-0
            $lastConsRow = $consSheet->getHighestRow();
            for ($row = 6; $row <= $lastConsRow; $row++) {
                for ($col = 10; $col <= 23; $col++) {
                    $value = $consSheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
                    $consSheet->setCellValueByColumnAndRow($col + 25, $row, $this->vbaRound((float)$value, 2));
                }
            }

            // 10. Totals → Summary sheet (with per-addition rounding) - KEEP EXACTLY AS ORIGINAL
            // assume $lastConsRow holds the last data row in ConsSheet-0
            $lastConsRow = $consSheet->getHighestRow();
            $summSheet->setCellValue(
                'D5', "=ROUND(SUM('ConsSheet-0'!T6:T{$lastConsRow}), 2)"
            );
            $summSheet->setCellValue(
                'E5', "=ROUND(SUM('ConsSheet-0'!U6:U{$lastConsRow}), 2)"
            );
            $summSheet->setCellValue(
                'D6', "=ROUND(SUM('ConsSheet-0'!W6:W{$lastConsRow}), 2)"
            );
            $summSheet->setCellValue(
                'E6', "=ROUND(SUM('ConsSheet-0'!W6:W{$lastConsRow}), 2)"
            );
            $summSheet->setCellValue(
                'D8', "=-ROUND(SUM('ConsSheet-0'!V6:V{$lastConsRow}), 2)"
            );
            $summSheet->setCellValue(
                'E8', "=-ROUND(SUM('ConsSheet-0'!V6:V{$lastConsRow}), 2)"
            );

            $writer = new Xlsx($proFormaBook);
            $writer->save($localRoyaltyFileOutputPath);

            $macroOutputPath = $this->generateUploadBasePath() . '/royalty/generated/' . $batch_id . "/Created-Royalty-Workbook-{$salesYear}-{$monthAbbrev}.xlsx";
            $macroCachedPath = $this->generateUploadBasePath() . "/royalty/generated/{$batch_id}/Created-Royalty-Workbook-{$salesYear}-{$monthAbbrev}.json";

            $this->upload($localRoyaltyFileOutputPath, $macroOutputPath);

            // Upload cached JSON data for royalty workbook
            $royaltyCachedData = [
                'batch_id' => $batch_id,
                'sales_month' => $salesMonth,
                'sales_year' => $salesYear,
                'generated_at' => now()->toISOString(),
                'mnsr_data' => $this->mnsrJsonData
            ];
            $this->uploadData(json_encode($royaltyCachedData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $macroCachedPath);

            // Clean up temporary files
            @unlink($modifiedMnsrLocalPath);
            @unlink($localRoyaltyFileOutputPath);
            @rmdir($tempDir);

            $macroOutput = new MacroOutput;
            $macroOutput->batch_id = $batch_id;
            $macroOutput->status = MacroBatchStatusEnum::Successful()->value;
            $macroOutput->file_name = "Royalty-Workbook-{$salesYear}-{$monthAbbrev}.xlsx";
            $macroOutput->file_type_id = MacroFileTypeEnum::Royalty()->value;
            $macroOutput->file_revision_id = MacroFileRevisionEnum::RoyaltyDefault()->value;
            $macroOutput->file_path = $macroOutputPath;
            $macroOutput->cached_path = $macroCachedPath;
            $macroOutput->month = $salesMonth;
            $macroOutput->year = $salesYear;
            $macroOutput->completed_at = now();
            $macroOutput->save();

            $macroBatch = MacroBatch::find($batch_id);
            $macroBatch->status = MacroBatchStatusEnum::Successful()->value;
            $macroBatch->completed_at = now();
            $macroBatch->save();

            $user = User::find($macroBatch->user_id);
            $user->notify(new GenerateRoyaltyNotification($batch_id));

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // Add this new method to handle estimated sales columns
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
     * Create Excel file from all sheets in JSON data
     */
    private function createExcelFromAllSheets($allSheetsData, $outputPath)
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
        foreach ($allSheetsData as $sheetName => $sheetData) {
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
                            $this->writeExcelDateColumn($sheet, $col . $r, $val);
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

    private function writeExcelDateColumn($sheet, $cell, $dateValue)
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
