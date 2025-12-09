<?php

namespace App\Services\Royalty;

use App\Enums\MacroBatchStatusEnum;
use App\Enums\MacroFileRevisionEnum;
use App\Enums\MacroFileTypeEnum;
use App\Enums\MacroStepStatusEnum;
use App\Models\Royalty\MacroFixedCache;
use App\Models\Royalty\MacroBatch;
use App\Models\Royalty\MacroOutput;
use App\Models\Royalty\MacroStep;
use App\Models\Royalty\MacroUpload;
use App\Traits\ErrorLogger;
use App\Traits\HandlesRoyaltyData;
use App\Traits\ManageFilesystems;
use App\Traits\ManageRoyaltyFiles;
use DateTime;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

class MNSRFranchiseeDataService
{
    private int $adjustNbr = 0;

    use ErrorLogger;
    use HandlesRoyaltyData;
    use ManageFilesystems;
    use ManageRoyaltyFiles;

    /**
     * @param  $batch_id  int Batch ID of the Macro Batch
     * @param  $salesMonth  int 1 - January to 12 - December
     * @param  $salesYear  int
     * @param  $region  String - LUZ, VIS or MIN
     */
    public function addFranchiseeDataToMNSR($batch_id, $step_id, $salesMonth, $salesYear, $region)
    {
        DB::beginTransaction();

        try {
            $salesMonthAbbrev = Carbon::createFromFormat('m', $salesMonth)->format('M');
            $basePath = $this->generateUploadBasePath() . '/royalty/fixed-caches/';

            /** Read JBMIS Sales Workbook */
            $macroStep = MacroStep::where('id', $step_id)->first();

            $uploadedJbmisFile = MacroUpload::where('id', $macroStep->upload_id)->first();
            $jbmisRows = json_decode($this->readFile($uploadedJbmisFile->cached_path), true);

            $macroStep->status = MacroStepStatusEnum::Ongoing()->value;
            $macroStep->save();

        /** Read JBMIS Codes Converions Table */
        $jbmisCodeConvTableCache = MacroFixedCache::where('batch_id', $batch_id)
            ->where('file_type_id', MacroFileTypeEnum::JBMISCodeConversion()->value)
            ->where('file_revision_id', MacroFileRevisionEnum::JBMISCodeConversionDefault()->value)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$jbmisCodeConvTableCache) {
            throw new \RuntimeException('JBMIS Code Conversion Table not found for batch ID: ' . $batch_id);
        }

        $jbmisCodeConvTableRows = json_decode($this->readFile($jbmisCodeConvTableCache->cached_path), true);

        /** Read MNSR File */
        $macroStepSuccess = MacroStep::where('batch_id', $batch_id)
            ->where('file_type_id', MacroFileTypeEnum::MNSR()->value)
            ->where('file_revision_id', MacroFileRevisionEnum::MNSRAddedFranchiseeData()->value)
            ->where('status', MacroStepStatusEnum::Successful()->value)
            ->first();

            if ($macroStepSuccess != null) {
                $outputMnsrFile = MacroOutput::where('batch_id', $batch_id)->where('file_type_id', MacroFileTypeEnum::MNSR()->value)
                    ->where('file_revision_id', MacroFileRevisionEnum::MNSRAddedFranchiseeData()->value)
                    ->orderBy('created_at', 'desc')
                    ->first();
            } else {
                $outputMnsrFile = MacroOutput::where('batch_id', $batch_id)->where('file_type_id', MacroFileTypeEnum::MNSR()->value)
                    ->where('file_revision_id', MacroFileRevisionEnum::MNSRDefault()->value)->first();
            }

            $mnsrData = json_decode($this->readFile($outputMnsrFile->cached_path), true);

            // Dynamic sheet handling - find the latest open sheet regardless of format
            $latestSheetData = $this->getLatestOpenSheet($mnsrData);
            // Create a deep copy of the latest sheet data for processing
            $mnsrSheets = json_decode(json_encode($latestSheetData), true);

            $this->ensureDateConsistency($mnsrSheets);

            /** -----------------------------------------------------------------
             *  2.  **MOD**  Remove rows where both MONTH_TOTAL & OTHERSALES_TOTAL
             *      are zero – iterate *backwards* so no row is skipped.
             * -----------------------------------------------------------------*/
            for ($i = count($jbmisRows) - 1; $i >= 1; $i--) {       // <-- MOD
                // Columns are zero–based: 23 = Excel col 24, 25 = Excel col 26
                $monthTotal = floatval(str_replace(',', '', $jbmisRows[$i][23] ?? 0));
                $otherTotal = floatval(str_replace(',', '', $jbmisRows[$i][25] ?? 0));

                if ($monthTotal <= 0 && $otherTotal <= 0) {
                    array_splice($jbmisRows, $i, 1);                // delete row
                    continue;
                }

                // Reformat cols 24-26 (#,##0.00) – keeps VBA parity
                for ($col = 23; $col <= 25; $col++) {
                    if (isset($jbmisRows[$i][$col]) && is_numeric($jbmisRows[$i][$col])) {
                        $jbmisRows[$i][$col] =
                            number_format((float)$jbmisRows[$i][$col], 2, '.', ',');
                    }
                }
            }
            $jbmisRows = array_values($jbmisRows);                  // tidy indices

            $this->convertJBMISCodes($jbmisRows, $jbmisCodeConvTableRows, $region, $salesMonthAbbrev, (string)$salesYear, $batch_id);
            $this->consolidateJBMISSalesData($jbmisRows);

            // $this->getLatestSalesSheet($mnsrSheets);

            // $this->reApplyMnsrFormulaValues($mnsrSheets[$this->adjustNbr]);
            $this->reApplyMnsrFormulaValues($mnsrSheets);

            // $this->matchSalesData($jbmisRows, $mnsrSheets[$this->adjustNbr]);
            $this->matchSalesData($jbmisRows, $mnsrSheets);

            $this->reApplyMnsrFormulaValues($mnsrSheets);

            // $this->markBranches($mnsrSheets[$this->adjustNbr]);
            $this->markBranches($mnsrSheets);

            // Create temporary directory for output
            $tempDir = sys_get_temp_dir() . '/royalty_franchisee_' . $batch_id . '_' . uniqid();
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $outputPath = $tempDir . '/2-' . $macroStep->id . '-Monthly-Natl-Sales-Rept-' . $salesMonthAbbrev . '-' . $salesYear . '.xlsx';

            // Get the original MNSR data (with all sheets)
            $originalMnsrData = json_decode($this->readFile($outputMnsrFile->cached_path), true);

            // Update the current sheet with our modifications
            $currentSheetName = 'Adjust-' . $this->adjustNbr;

            // Process all sheets - preserve original data for all sheets except the current one
            $allSheetsData = [];
            foreach ($originalMnsrData as $sheetName => $sheetData) {
                if ($sheetName == $currentSheetName) {
                    // Use the modified data for the current sheet only
                    $allSheetsData[$sheetName] = $mnsrSheets;
                } else {
                    // Keep original data for other sheets unchanged - create deep copy to prevent contamination
                    $allSheetsData[$sheetName] = json_decode(json_encode($sheetData), true);
                }
            }

            // Create Excel file with all sheets
            $this->createExcelFromAllSheets($allSheetsData, $outputPath, $batch_id);

            $macroOutputPath = $this->generateUploadBasePath() . '/royalty/generated/' . $batch_id . '/2-' . $macroStep->id . '-Monthly-Natl-Sales-Rept-' . $salesMonthAbbrev . '-' . $salesYear . '.xlsx';
            $macroCachedPath = $this->generateUploadBasePath() . '/royalty/generated/' . $batch_id . '/2-' . $macroStep->id . '-Monthly-Natl-Sales-Rept-' . $salesMonthAbbrev . '-' . $salesYear . '.json';

            $this->upload($outputPath, $macroOutputPath);

            // Upload cached JSON data with sheet structure (similar to CacheUploadedRoyaltyFilesJob)
            $sheetName = 'Adjust-' . $this->adjustNbr;

            // Retain all existing sheets and only update the current one
            $originalMnsrData = json_decode($this->readFile($outputMnsrFile->cached_path), true);

            // Extract all existing sheets and update only the current one
            $mnsrDataToSave = [];

            foreach ($originalMnsrData as $key => $sheetData) {
                if ($key == $sheetName) {
                    // Update the current sheet with modifications
                    $mnsrDataToSave[$key] = $mnsrSheets;
                } else {
                    // Keep existing sheet unchanged - create deep copy to prevent contamination
                    $mnsrDataToSave[$key] = json_decode(json_encode($sheetData), true);
                }
            }

            // If no sheets found, throw error
            if (empty($mnsrDataToSave)) {
                throw new Exception('No sheets found in MNSR data. Data integrity issue detected.');
            }

            $tempCachedFile = $tempDir . '/cached_mnsr_franchisee.json';
            file_put_contents($tempCachedFile, json_encode($mnsrDataToSave, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            $this->upload($tempCachedFile, $macroCachedPath);

            // Clean up temporary files
            @unlink($outputPath);
            @unlink($tempCachedFile);
            @rmdir($tempDir);

            $macroOutput = new MacroOutput();
            $macroOutput->batch_id = $batch_id;
            $macroOutput->step_id = $macroStep->id;
            $macroOutput->status = MacroBatchStatusEnum::Successful()->value;
            $macroOutput->file_name = 'Monthly-Natl-Sales-Rept-' . $salesMonthAbbrev . '-' . $salesYear . '.xlsx';
            $macroOutput->file_type_id = MacroFileTypeEnum::MNSR()->value;
            $macroOutput->file_revision_id = MacroFileRevisionEnum::MNSRAddedFranchiseeData()->value;
            $macroOutput->file_path = $macroOutputPath;
            $macroOutput->cached_path = $macroCachedPath;
            $macroOutput->completed_at = now();
            $macroOutput->month = $salesMonth;
            $macroOutput->year = $salesYear;
            $macroOutput->save();

            $macroStep->status = MacroStepStatusEnum::Successful()->value;
            $macroStep->save();

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function addTotals($proFormaData)
    {
        // Remove any existing totals rows first to prevent duplication
        $proFormaData = $this->removeExistingTotals($proFormaData);
        
        $totalsRowIndex = count($proFormaData) + 1; // Excel row index (1-based)
        $totalsRow = array_fill(0, max(array_map('count', $proFormaData)), null);

        // Add label to Column C (index 2)
        $totalsRow[2] = 'T O T A L S';

        // Define target columns for summation
        $sumFormulaColumns = [
            37 => 'AL',
            38 => 'AM',
            41 => 'AP',
            42 => 'AQ',
            45 => 'AT',
            46 => 'AU',
        ];

        foreach ($sumFormulaColumns as $colIndex => $colLetter) {
            $totalsRow[$colIndex] = "=SUM({$colLetter}8:{$colLetter}" . ($totalsRowIndex - 1) . ')';
        }

        // Add the totals row to the dataset
        $proFormaData[] = $totalsRow;

        return $proFormaData;
    }

    /**
     * Remove existing totals rows from the data to prevent duplication.
     * Identifies totals rows by checking for "T O T A L S" in column C (index 2).
     *
     * @param array $data The data array to clean
     * @return array The data array with totals rows removed
     */
    private function removeExistingTotals(array $data): array
    {
        $cleanedData = [];
        
        foreach ($data as $rowIndex => $row) {
            // Skip rows that contain "T O T A L S" in column C (index 2)
            if (is_array($row) && isset($row[2]) && 
                is_string($row[2]) && trim($row[2]) === 'T O T A L S') {
                continue; // Skip this totals row
            }
            
            $cleanedData[] = $row;
        }
        
        return $cleanedData;
    }

    /**
     * Convert JBMIS codes into standard cluster and store codes.
     *
     * @param array  &$jbmisRows The JBMIS data as a 2D array.
     * @param array $convRows The conversion table as a 2D array.
     * @param string $region The sales region (e.g., "LUZ" or "VIS").
     * @param string $salesMonthAbbrev The sales month abbreviation.
     * @param string $salesYear The sales year.
     *
     * @throws Exception if missing conversion codes are detected.
     */
    private function convertJBMISCodes(
        array  &$jbmisRows,
        array  $convRows,
        string $region,
        string $salesMonthAbbrev,
        string $salesYear,
        int    $batch_id
    ): void
    {
        $jbmisIndex = 1;
        $convIndex = 1;
        $missingCodes = [];

        while ($jbmisIndex < count($jbmisRows)) {
            $currentCode = trim($jbmisRows[$jbmisIndex][1] ?? '');

            // End-of-data check
            if ($currentCode == '') {
                break;
            }

            // Skip consolidation rows (codes ending in 9)
            if (substr($currentCode, -1) == '9') {
                array_splice($jbmisRows, $jbmisIndex, 1);
                continue;                                       // don’t ++index
            }

            $convCode = trim($convRows[$convIndex][1] ?? '');

            // compare the JBMIS code with the conversion table code.
            if ($currentCode > $convCode) {
                // if the conversion table's code is empty, flag missing conversion code.
                if (empty(trim($convCode))) {
                    $missingCodes[] = $currentCode;
                    $jbmisIndex++;
                    continue;
                }

                $convIndex++;

                continue;
            }

            if ($currentCode === $convCode) {
                // if match is found.
                $jbmisRows[$jbmisIndex][0] = $convRows[$convIndex][2]; // cluster (conversion table col 3)
                $jbmisRows[$jbmisIndex][1] = $convRows[$convIndex][3]; // standard store code (conversion table col 4)
                $jbmisRows[$jbmisIndex][2] = $convRows[$convIndex][4]; // store name (conversion table col 5)
                $jbmisIndex++;

                continue;
            }

            if ($currentCode < $convCode) {                     // no match
                $missingCodes[] = $currentCode;
                $jbmisIndex++;
                continue;
            }
        }

        if (!empty($missingCodes)) {
            throw new Exception(
                'The following JBMIS store codes are missing from the conversion table: '
                . implode(', ', array_unique($missingCodes)) . '. '
                . 'Please add these codes to the conversion table and rerun this batch.'
            );
        }

        $header = $jbmisRows[0];
        $dataRows = array_slice($jbmisRows, 1);

        // sort the data first by Cluster (column 1, index 0) then by Store Code (column 2, index 1).
        usort($dataRows, function ($a, $b) {
            if ($a[0] == $b[0]) {
                return $a[1] <=> $b[1];
            }

            return $a[0] <=> $b[0];
        });

        array_unshift($dataRows, $header);
        $jbmisRows = $dataRows;
    }

    /**
     * Consolidate duplicate JBMIS rows based on matching cluster and store code.
     *
     * This function loops through the JBMIS data (skipping the header row) and, when two consecutive
     * rows have the same "Cluster" (column 1) and "Store Code" (column 2), it adds together the values
     * for a defined set of numeric columns and removes the duplicate row.
     *
     * @param array  &$jbmisRows The 2D array containing JBMIS data.
     */
    private function consolidateJBMISSalesData(array &$jbmisRows): void
    {
        // Ensure there is at least one data row.
        if (count($jbmisRows) <= 1) {
            return;
        }

        $prevIndex = 1;
        $thisIndex = 2;

        while ($thisIndex < count($jbmisRows)) {
            // if the first cell of this row is empty, we assume end-of-data.
            if (empty(trim($jbmisRows[$thisIndex][0] ?? ''))) {
                break;
            }

            // Retrieve the cluster and store code for the previous and current rows.
            $prevClust = $jbmisRows[$prevIndex][0];
            $prevStore = $jbmisRows[$prevIndex][1];
            $thisClust = $jbmisRows[$thisIndex][0];
            $thisStore = $jbmisRows[$thisIndex][1];

            if ($thisClust == $prevClust && $thisStore == $prevStore) {
                $columnsToAdd = [5, 6, 9, 10, 13, 14, 17, 18, 21, 22, 23, 24, 25];
                foreach ($columnsToAdd as $col) {
                    // Make sure both rows have the column defined.
                    if (isset($jbmisRows[$prevIndex][$col], $jbmisRows[$thisIndex][$col])) {
                        $jbmisRows[$prevIndex][$col] = (float)str_replace(',', '', $jbmisRows[$prevIndex][$col]) + (float)str_replace(',', '', $jbmisRows[$thisIndex][$col]);
                    }
                }

                // remove the duplicate row.
                unset($jbmisRows[$thisIndex]);

                // re-index the array to keep sequential keys.
                $jbmisRows = array_values($jbmisRows);

                // do not increment $thisIndex; the next row now has the same index.
                continue;
            } else {
                // no duplication: update the previous row pointer and move to the next row.
                $prevIndex = $thisIndex;
                $thisIndex++;
            }
        }
    }

    /**
     * Dynamically find the latest open sheet from MNSR data regardless of format.
     * Works with both single array format and multi-sheet format.
     *
     * @param array $mnsrData The MNSR data (can be single array or multi-sheet structure).
     * @return array The latest open sheet data.
     */
    private function getLatestOpenSheet(array $mnsrData): array
    {
        // Check if this is a multi-sheet structure (contains sheet names as keys)
        $adjustSheets = [];
        $hasSheetStructure = false;

        foreach ($mnsrData as $key => $value) {
            if (is_string($key) && preg_match('/^Adjust-(\d+)$/', $key, $matches)) {
                $adjustSheets[(int)$matches[1]] = $value;
                $hasSheetStructure = true;
            }
        }

        if ($hasSheetStructure) {
            // Multi-sheet structure - find the latest open sheet
            ksort($adjustSheets);

            // Find the first open sheet
            foreach ($adjustSheets as $adjustNum => $salesSheet) {
                // Check cell (4,1): row index 3, column index 0 for closure marker
                if (isset($salesSheet[3][0]) &&
                    trim($salesSheet[3][0]) == 'T H I S   S H E E T   I S   C L O S E D') {
                    continue; // Skip closed sheets
                } else {
                    // Found the first open sheet, set the adjust number
                    $this->adjustNbr = $adjustNum;
                    return $salesSheet;
                }
            }

            // If all sheets are closed, return the last one and set its number
            if (!empty($adjustSheets)) {
                $lastAdjustNum = max(array_keys($adjustSheets));
                $this->adjustNbr = $lastAdjustNum;
                return $adjustSheets[$lastAdjustNum];
            }
        } else {
            // Single array format (backwards compatibility)
            $this->adjustNbr = 0;
            return $mnsrData;
        }

        return [];
    }

    /**
     * Retrieve the first open sales sheet from sheet-based MNSR data.
     *
     * This function loops over sheets (named "Adjust-0", "Adjust-1", etc.) until it finds one where
     * cell (4,1) (i.e. row index 3, column index 0) does not equal the closed marker.
     *
     * @param array $mnsrData The sheet-based MNSR data.
     * @return array The sales sheet data.
     */
    private function getLatestSalesSheetFromData(array $mnsrData): array
    {
        $adjustSheets = [];

        // Filter and sort only Adjust-X sheets
        foreach ($mnsrData as $sheetName => $sheetData) {
            if (preg_match('/^Adjust-(\d+)$/', $sheetName, $matches)) {
                $adjustSheets[(int)$matches[1]] = $sheetData;
            }
        }

        // Sort by adjust number
        ksort($adjustSheets);

        // Find the first open sheet
        foreach ($adjustSheets as $adjustNum => $salesSheet) {
            // check cell (4,1): row index 3, column index 0.
            if (isset($salesSheet[3][0]) &&
                trim($salesSheet[3][0]) == 'T H I S   S H E E T   I S   C L O S E D') {
                continue; // Skip closed sheets
            } else {
                // Found the first open sheet, set the adjust number
                $this->adjustNbr = $adjustNum;
                return $salesSheet;
            }
        }

        // If all sheets are closed, return the last one and set its number
        if (!empty($adjustSheets)) {
            $lastAdjustNum = max(array_keys($adjustSheets));
            $this->adjustNbr = $lastAdjustNum;
            return $adjustSheets[$lastAdjustNum];
        }

        return [];
    }

    /**
     * Retrieve the first open sales sheet from the MNSR workbook.
     *
     * This function loops over sheets (named "Adjust-0", "Adjust-1", etc.) until it finds one where
     * cell (4,1) (i.e. row index 3, column index 0) does not equal the closed marker.
     *
     * @param array  &$mnsrSheets An array of sheets from the MNSR workbook.
     * @return array The sales sheet data.
     */
    private function getLatestSalesSheet(array &$mnsrSheets): array
    {
        $this->adjustNbr = 0;
        for ($i = 0; $i < count($mnsrSheets); $i++) {
            $salesSheet = $mnsrSheets[$i];

            // check cell (4,1): row index 3, column index 0.
            if (isset($salesSheet[3][0]) &&
                trim($salesSheet[3][0]) == 'T H I S   S H E E T   I S   C L O S E D') {
                $this->adjustNbr++;
            } else {
                break;
            }
        }

        return $mnsrSheets[$this->adjustNbr];
    }

    /**
     * Match the JBMIS rows to the Sales sheet rows and post weekly sales data.
     *
     * @param array $jbmisRows The consolidated JBMIS data.
     * @param array  &$mnsrSheet The sales sheet data from the MNSR workbook.
     */
    private function matchSalesData(array $jbmisRows, array &$mnsrSheet): void
    {
        $salesRow = 7;
        $jbmisRow = 1;
        $missingCount = 0;

        while (true) {
            // if we’ve reached an empty row in the JBMIS data, stop matching.
            if (empty(trim($jbmisRows[$jbmisRow][0] ?? ''))) {
                if ($missingCount > 10) {
                    break;
                }

                $jbmisRow++;
                $missingCount++;

                continue; // DoneMatching
            }

            // if the corresponding SalesSheet row (store code column at index 1) is empty,
            // it means a store exists in JBMIS that is not in the MNSR.
            if (empty(trim($mnsrSheet[$salesRow][1] ?? ''))) {
                $storeNbr = $jbmisRows[$jbmisRow][1];
                $salesRow = 7;
                $jbmisRow++;

                continue;
            }

            // if the store code in JBMIS does not match the one in the SalesSheet, move down SalesSheet.
            if ($jbmisRows[$jbmisRow][1] != $mnsrSheet[$salesRow][1]) {
                $salesRow++;

                continue;
            }

            // when a match is found, process the weekly sales.
            // check the header cell (SalesSheet row 5, col 17 → index [4][16])
            if (isset($mnsrSheet[4][16]) && $mnsrSheet[4][16] == 8) {
                // --- case 1: first week has 8 days (JBMIS week1 + week2 are combined) ---
                $breadSlsWk1 = $jbmisRows[$jbmisRow][5] + $jbmisRows[$jbmisRow][9];
                $nonBdSlsWk1 = $jbmisRows[$jbmisRow][6] + $jbmisRows[$jbmisRow][10];
                if ($breadSlsWk1 != 0) {
                    $mnsrSheet[$salesRow][15] = $breadSlsWk1;
                    $mnsrSheet[$salesRow][16] = $nonBdSlsWk1;
                } else {
                    $this->reApplyMnsrFormulaValueAtIndex($mnsrSheet, $salesRow);
                    if ($mnsrSheet[$salesRow][14] == 'A') {
                        $mnsrSheet[$salesRow][15] = 0;
                        $mnsrSheet[$salesRow][16] = 0;
                    }
                }

                if ($jbmisRows[$jbmisRow][13] != 0) {
                    $mnsrSheet[$salesRow][19] = $jbmisRows[$jbmisRow][13];
                    $mnsrSheet[$salesRow][20] = $jbmisRows[$jbmisRow][14];
                } else {
                    $this->reApplyMnsrFormulaValueAtIndex($mnsrSheet, $salesRow);
                    if ($mnsrSheet[$salesRow][18] == 'A') {
                        $mnsrSheet[$salesRow][19] = 0;
                        $mnsrSheet[$salesRow][20] = 0;
                    }
                }

                if ($jbmisRows[$jbmisRow][17] != 0) {
                    $mnsrSheet[$salesRow][23] = $jbmisRows[$jbmisRow][17];
                    $mnsrSheet[$salesRow][24] = $jbmisRows[$jbmisRow][18];
                } else {
                    $this->reApplyMnsrFormulaValueAtIndex($mnsrSheet, $salesRow);
                    if ($mnsrSheet[$salesRow][22] == 'A') {
                        $mnsrSheet[$salesRow][23] = 0;
                        $mnsrSheet[$salesRow][24] = 0;
                    }
                }

                // MNSR - week 4 & week 5: check if there are extra days in week 5 (cell (5,34) → index [4][33])
                if (isset($mnsrSheet[4][33]) && $mnsrSheet[4][33] > 0) {
                    $daysWk5 = $mnsrSheet[4][33];
                    $totDays = 7 + $daysWk5;

                    // prorate week 5 values: use JBMIS columns 22 & 23 (indices 21 and 22)
                    $breadSlsWk4 = round($jbmisRows[$jbmisRow][21] * 7 / $totDays, 2);
                    $nonBdSlsWk4 = round($jbmisRows[$jbmisRow][22] * 7 / $totDays, 2);
                    if ($breadSlsWk4 != 0) {
                        $mnsrSheet[$salesRow][27] = $breadSlsWk4;
                        $mnsrSheet[$salesRow][28] = $nonBdSlsWk4;
                    } else {
                        $this->reApplyMnsrFormulaValueAtIndex($mnsrSheet, $salesRow);
                        if ($mnsrSheet[$salesRow][26] == 'A') {
                            $mnsrSheet[$salesRow][27] = 0;
                            $mnsrSheet[$salesRow][28] = 0;
                        }
                    }

                    // calculate the remaining (week 5) values
                    $breadSlsWk5 = round($jbmisRows[$jbmisRow][21] * $daysWk5 / $totDays, 2);
                    $nonBdSlsWk5 = round($jbmisRows[$jbmisRow][22] * $daysWk5 / $totDays, 2);
                    if ($breadSlsWk5 != 0) {
                        $mnsrSheet[$salesRow][32] = $breadSlsWk5;
                        $mnsrSheet[$salesRow][33] = $nonBdSlsWk5;
                    } else {
                        $this->reApplyMnsrFormulaValueAtIndex($mnsrSheet, $salesRow);
                        if ($mnsrSheet[$salesRow][31] == 'A') {
                            $mnsrSheet[$salesRow][32] = 0;
                            $mnsrSheet[$salesRow][33] = 0;
                        }
                    }
                } else {
                    // mo extra days in week 5 – treat MNSR week 4 as the last week of the month.
                    if ($jbmisRows[$jbmisRow][21] != 0) {
                        $mnsrSheet[$salesRow][27] = $jbmisRows[$jbmisRow][21];
                        $mnsrSheet[$salesRow][28] = $jbmisRows[$jbmisRow][22];
                    } else {
                        $this->reApplyMnsrFormulaValueAtIndex($mnsrSheet, $salesRow);
                        if ($mnsrSheet[$salesRow][26] == 'A') {
                            $mnsrSheet[$salesRow][27] = 0;
                            $mnsrSheet[$salesRow][28] = 0;
                        }
                    }
                }
            } else {
                // --- Case 2: official calendar matches JBMIS extract (do not combine weeks 1 and 2) ---
                if ($jbmisRows[$jbmisRow][5] != 0) {
                    $mnsrSheet[$salesRow][15] = $jbmisRows[$jbmisRow][5];
                    $mnsrSheet[$salesRow][16] = $jbmisRows[$jbmisRow][6];
                } else {
                    $this->reApplyMnsrFormulaValueAtIndex($mnsrSheet, $salesRow);
                    if ($mnsrSheet[$salesRow][14] == 'A') {
                        $mnsrSheet[$salesRow][15] = 0;
                        $mnsrSheet[$salesRow][16] = 0;
                    }
                }

                if ($jbmisRows[$jbmisRow][9] != 0) {
                    $mnsrSheet[$salesRow][19] = $jbmisRows[$jbmisRow][9];
                    $mnsrSheet[$salesRow][20] = $jbmisRows[$jbmisRow][10];
                } else {
                    $this->reApplyMnsrFormulaValueAtIndex($mnsrSheet, $salesRow);
                    if ($mnsrSheet[$salesRow][18] == 'A') {
                        $mnsrSheet[$salesRow][19] = 0;
                        $mnsrSheet[$salesRow][20] = 0;
                    }
                }

                if ($jbmisRows[$jbmisRow][13] != 0) {
                    $mnsrSheet[$salesRow][23] = $jbmisRows[$jbmisRow][13];
                    $mnsrSheet[$salesRow][24] = $jbmisRows[$jbmisRow][14];
                } else {
                    $this->reApplyMnsrFormulaValueAtIndex($mnsrSheet, $salesRow);
                    if ($mnsrSheet[$salesRow][22] == 'A') {
                        $mnsrSheet[$salesRow][23] = 0;
                        $mnsrSheet[$salesRow][24] = 0;
                    }
                }

                if ($jbmisRows[$jbmisRow][17] != 0) {
                    $mnsrSheet[$salesRow][27] = $jbmisRows[$jbmisRow][17];
                    $mnsrSheet[$salesRow][28] = $jbmisRows[$jbmisRow][18];
                } else {
                    $this->reApplyMnsrFormulaValueAtIndex($mnsrSheet, $salesRow);
                    if ($mnsrSheet[$salesRow][26] == 'A') {
                        $mnsrSheet[$salesRow][27] = 0;
                        $mnsrSheet[$salesRow][28] = 0;
                    }
                }

                if ($jbmisRows[$jbmisRow][21] != 0) {
                    $mnsrSheet[$salesRow][32] = $jbmisRows[$jbmisRow][21];
                    $mnsrSheet[$salesRow][33] = $jbmisRows[$jbmisRow][22];
                } else {
                    $this->reApplyMnsrFormulaValueAtIndex($mnsrSheet, $salesRow);
                    if ($mnsrSheet[$salesRow][31] == 'A') {
                        $mnsrSheet[$salesRow][32] = 0;
                        $mnsrSheet[$salesRow][33] = 0;
                    }
                }
            }

            // --- post additional sales data
            if ($jbmisRows[$jbmisRow][23] != 0) {
                $mnsrSheet[$salesRow][45] = (float)str_replace(',', '', $jbmisRows[$jbmisRow][23]);
                $mnsrSheet[$salesRow][46] = (float)str_replace(',', '', $jbmisRows[$jbmisRow][25]);
            }

            // after processing the matching row, reset SalesRow and move to the next JBMIS row.
            $jbmisRow++;
            $salesRow = 7;
        }
    }

    /**
     * Mark each branch as having complete or incomplete sales reporting.
     *
     * This function iterates over the Sales sheet rows (starting at index 7, corresponding to Excel row 8)
     * and checks whether certain columns (e.g., columns 15, 19, 23, 27, and 32 for 5-week data)
     * contain the value "A". If they all equal "A", it sets column 36 (index 35) to "Y", otherwise "N".
     *
     * @param array  &$mnsrRows The Sales sheet data.
     */
    private function markBranches(array &$mnsrRows): void
    {
        for ($i = 7; $i < count($mnsrRows); $i++) {
            $this->reApplyMnsrFormulaValueAtIndex($mnsrRows, $i);
            // if the first cell is empty, we assume there are no more branches.
            if (empty(trim($mnsrRows[$i][0] ?? ''))) {
                break;
            }

            // check if there are 5 weeks of sales data.
            if (isset($mnsrRows[4][33]) && $mnsrRows[4][33] > 0) {
                // if 5 weeks, check columns corresponding to Excel: 15, 19, 23, 27, 32.
                if (
                    $mnsrRows[$i][14] == 'A' &&
                    $mnsrRows[$i][18] == 'A' &&
                    $mnsrRows[$i][22] == 'A' &&
                    $mnsrRows[$i][26] == 'A' &&
                    $mnsrRows[$i][31] == 'A'
                ) {
                    $mnsrRows[$i][35] = 'Y';
                } else {
                    $mnsrRows[$i][35] = 'N';
                }
            } else {
                // if 4 weeks, check columns corresponding to Excel: 15, 19, 23, 27.
                if (
                    $mnsrRows[$i][14] == 'A' &&
                    $mnsrRows[$i][18] == 'A' &&
                    $mnsrRows[$i][22] == 'A' &&
                    $mnsrRows[$i][26] == 'A'
                ) {
                    $mnsrRows[$i][35] = 'Y';
                } else {
                    $mnsrRows[$i][35] = 'N';
                }
            }
        }
    }

    private function writeExcelDateColumn($sheet, $cell, $dateValue)
    {
        if ($dateValue == null || $dateValue == '') {
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
                if ($dateObj->format('Y-m-d') == '2049-12-31') {
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

    private function ensureDateConsistency(&$mnsrSheets)
    {
        // When loading cached data, ensure dates are in string format
        foreach ($mnsrSheets as &$row) {
            if (!is_array($row)) continue;

            // For date columns that might contain Carbon/DateTime objects
            $dateColumns = [3, 4, 58, 59, 60]; // Columns D, E, BG, BH, BI (0-indexed)

            foreach ($dateColumns as $col) {
                if (isset($row[$col]) && $row[$col] instanceof Carbon) {
                    $row[$col] = $row[$col]->format('Y-m-d');
                } elseif (isset($row[$col]) && $row[$col] instanceof DateTime) {
                    $row[$col] = $row[$col]->format('Y-m-d');
                }
            }
        }
    }

    /**
     * Create Excel file from all sheets in JSON data
     */
    private function createExcelFromAllSheets($allSheetsData, $outputPath, $batch_id = null)
    {
        // Load template directly from local storage
        $templatePath = storage_path('app/private/royalty/cache/Z-ProForma-Natl-Monthly-Sales-by-Store.xlsx');
        if (!file_exists($templatePath)) {
            throw new RuntimeException('MNSR template not found: ' . $templatePath);
        }

        // FOOLPROOF APPROACH: Create a completely new spreadsheet and load fresh template for each sheet
        $mainSpreadsheet = null;
        $sheetIndex = 0;

        // Create all sheets from JSON data - each sheet gets its own completely fresh template
        foreach ($allSheetsData as $sheetName => $sheetData) {
            // Load a completely fresh template for each sheet to prevent ANY contamination
            $freshTemplate = IOFactory::load($templatePath);
            $freshSheet = $freshTemplate->getActiveSheet();
            $freshSheet->setTitle($sheetName);

            // Create deep copy of sheet data to prevent contamination
            $isolatedSheetData = json_decode(json_encode($sheetData), true);

            // Convert formulas to calculated values first, then reapply formulas
            $isolatedSheetData = $this->convertBulk($isolatedSheetData);
            $isolatedSheetData = $this->reApplyMnsrFormulas($isolatedSheetData);
            $sheetDataWithTotals = $this->addTotals($isolatedSheetData);

            // Populate sheet with data
            $freshSheet->fromArray($sheetDataWithTotals, null, 'A1');
            $freshSheet->getRowDimension(1)->setVisible(false);

            // For the first sheet, use this as the main spreadsheet
            if ($sheetIndex == 0) {
                $mainSpreadsheet = $freshTemplate;
            } else {
                // For subsequent sheets, add the fresh sheet to the main spreadsheet
                $mainSpreadsheet->addSheet($freshSheet);
            }

            $sheetIndex++;

            // Apply date formatting - columns Q, U, Y, AC, AH for rows 3 and 4
            foreach (['H', 'Q', 'U', 'Y', 'AC', 'AH'] as $col) {
                for ($row = 3; $row <= 4; $row++) {
                    $cell = $freshSheet->getCell($col . $row);
                    $value = $cell->getValue();

                    // Only process if there's actually a value to convert
                    if ($value !== null && $value !== '' && !is_numeric($value)) {
                        $dateObj = DateTime::createFromFormat('Y-m-d', $value);
                        if ($dateObj !== false) {
                            $excelDate = ExcelDate::PHPToExcel($dateObj);
                            $cell->setValue($excelDate);
                        }
                    }

                    // Apply date formatting if there's a value
                    if ($cell->getValue() !== null && $cell->getValue() !== '') {
                        // Special formatting for H4
                        if ($col === 'H') {
                            $freshSheet->getStyle($col . $row)
                                ->getNumberFormat()
                                ->setFormatCode('m/d/yyyy h:mm');
                        } else {
                            $freshSheet->getStyle($col . $row)
                                ->getNumberFormat()
                                ->setFormatCode('d-MMM-yy');
                        }
                    }
                }
            }

            // Apply number formatting to columns BE and BF to show 0.000 format AND force values
            $highestRow = $freshSheet->getHighestRow();
            foreach (['BE', 'BF'] as $col) {
                for ($row = 8; $row <= $highestRow; $row++) {
                    // Check if this row has actual data by looking at column A (cluster code) or column B (store code)
                    $clusterCode = $freshSheet->getCell('A' . $row)->getValue();
                    $storeCode = $freshSheet->getCell('B' . $row)->getValue();

                    // Stop the loop if both cluster and store codes are empty (end of data)
                    if (empty($clusterCode) && empty($storeCode)) {
                        break;
                    }

                    $cell = $freshSheet->getCell($col . $row);
                    $currentValue = $cell->getValue();

                    // Force 0.000 for empty/null values
                    if ($currentValue == null || $currentValue == '' || $currentValue == 0) {
                        $cell->setValue(0.000);
                    }

                    // Apply number formatting to show 0.000 format
                    $freshSheet->getStyle($col . $row)
                        ->getNumberFormat()
                        ->setFormatCode('0.000');
                }
            }

            // fix date formats for columns D, E, BG, BH, BI for rows 8 and beyond:
            foreach (['D', 'E', 'BG', 'BH', 'BI'] as $col) {
                for ($row = 8; $row <= $highestRow; $row++) {
                    // Check if this row has actual data by looking at column A (cluster code) or column B (store code)
                    $clusterCode = $freshSheet->getCell('A' . $row)->getValue();
                    $storeCode = $freshSheet->getCell('B' . $row)->getValue();

                    // Skip rows that don't have branch data (except the TOTALS row)
                    if (empty($clusterCode) && empty($storeCode)) {
                        $totalLabel = $freshSheet->getCell('C' . $row)->getValue();
                        if ($totalLabel !== 'T O T A L S') {
                            continue; // Skip empty rows
                        }
                    }

                    $cell = $freshSheet->getCell($col . $row);
                    $value = $cell->getValue();

                    if ($col == 'D') {
                        // Special handling for column D - only if the row has data
                        if (!empty($clusterCode) || !empty($storeCode)) {
                            $this->writeExcelDateColumn($freshSheet, $col . $row, $value);
                        }
                    } else {
                        // Regular handling for other columns
                        if ($value !== null && $value !== '' && !is_numeric($value)) {
                            $dateObj = DateTime::createFromFormat('Y-m-d', $value);
                            if ($dateObj !== false) {
                                $excelDate = ExcelDate::PHPToExcel($dateObj);
                                $cell->setValue($excelDate);
                            }
                        }

                        // Apply date formatting if there's a value
                        if ($cell->getValue() !== null && $cell->getValue() !== '') {
                            $freshSheet->getStyle($col . $row)
                                ->getNumberFormat()
                                ->setFormatCode('d-MMM-yy');
                        }
                    }
                }
            }
        }

        // Set the first sheet as active
        $mainSpreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($mainSpreadsheet);
        $writer->save($outputPath);
    }
}
