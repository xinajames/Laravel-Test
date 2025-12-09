<?php

namespace App\Services\Royalty;

use App\Enums\MacroBatchStatusEnum;
use App\Enums\MacroFileRevisionEnum;
use App\Enums\MacroFileTypeEnum;
use App\Jobs\Royalty\GenerateRoyaltyWorkBookJob;
use App\Jobs\Royalty\UpdateRoyaltyWorkBookJob;
use App\Models\Royalty\MacroBatch;
use App\Models\Royalty\MacroBatchConfig;
use App\Models\Royalty\MacroFixedCache;
use App\Models\Royalty\MacroOutput;
use App\Models\Royalty\MacroUpload;
use App\Models\SalesPerformance;
use App\Models\User;
use App\Notifications\Royalty\GenerateMnsrNotification;
use App\Traits\ErrorLogger;
use App\Traits\HandlesRoyaltyData;
use App\Traits\ManageFilesystems;
use DateTime;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

class MNSRJbmisPosDataService
{
    private int $adjustNbr = 0;
    private int $batchId = 0;

    use ErrorLogger;
    use HandlesRoyaltyData;
    use ManageFilesystems;

    /**
     * Main function to add JBMIS and POS data to MNSR
     */
    public function addJbmisPosDataToMNSR($batch_id, string $salesMonth, int $salesYear)
    {

        DB::beginTransaction();

        try {
            $this->batchId = $batch_id;

            // Helper for parsing comma-formatted strings into floats
            $parseDecimal = function ($value) {
                if ($value == null || $value == '') {
                    return 0; // Return 0 instead of null for consistent numeric operations
                }
                // Handle both string and numeric inputs
                if (is_string($value)) {
                    return floatval(str_replace(',', '', $value));
                }

                return floatval($value);
            };

            // Calculate week intervals
            $weekIntervals = $this->calculateWeekIntervals($salesMonth, $salesYear);
            extract($weekIntervals);

            // Process JBMIS data
            $jbmisData = $this->processJbmisData(
                $batch_id,
                $parseDecimal,
                $daysWk1,
                $daysWk5
            );

            // Process POS data
            $posData = $this->processPosData(
                $batch_id,
                $parseDecimal,
                $startWk1,
                $endWk1,
                $startWk2,
                $endWk2,
                $startWk3,
                $endWk3,
                $startWk4,
                $endWk4,
                $startWk5,
                $endWk5,
                $daysWk5
            );

            // Combine JBMIS and POS data into temporary dataset
            $tempData = $this->createTempData($jbmisData['rows'], $posData);

            // Consolidate data
            $consolidated = $this->consolidateData($tempData);

            // Update MNSR with consolidated data
            $mnsrData = $this->updateMnsrData(
                $batch_id,
                $consolidated,
                $daysWk5,
                $salesMonth, $salesYear,
                $weekIntervals
            );

            // Write output to Excel file with all sheets
            $this->writeOutputToExcelWithAllSheets(
                $mnsrData,
                $batch_id,
                $salesMonth,
                $salesYear
            );

            $salesMonthAbbrev = Carbon::createFromFormat('m', $salesMonth)->format('M');

            // Use temporary file for initial write
            $tempFilePath = sys_get_temp_dir() . '/3-Monthly-Natl-Sales-Rept-' . $salesMonthAbbrev . '-' . $salesYear . '.xlsx';

            $macroOutputPath = $this->generateUploadBasePath() . '/royalty/generated/' . $batch_id . '/3-Monthly-Natl-Sales-Rept-' . $salesMonthAbbrev . '-' . $salesYear . '.xlsx';
            $macroCachedPath = $this->generateUploadBasePath() . '/royalty/generated/' . $batch_id . '/3-Cached-Monthly-Natl-Sales-Rept-' . $salesMonthAbbrev . '-' . $salesYear . '.json';

            $this->ensureDateConsistency($mnsrData['mnsrSheets']);
            // Upload the temp file to royalty disk
            $this->upload($tempFilePath, $macroOutputPath);
            @unlink($tempFilePath);
            // Create temp file for cached data and upload with sheet structure
            $sheetName = 'Adjust-' . $this->adjustNbr;

            // Use the original MNSR data already loaded in updateMnsrData to avoid re-reading
            $originalMnsrData = $mnsrData['originalMnsrData'];

            // Extract all existing sheets and update only the current one
            $cachedMnsrData = [];

            foreach ($originalMnsrData as $key => $sheetData) {
                if ($key === $sheetName) {
                    // Update the current sheet with modifications
                    $cachedMnsrData[$key] = $mnsrData['mnsrSheets'];
                } else {
                    // Keep existing sheet unchanged - create deep copy to prevent contamination
                    $cachedMnsrData[$key] = json_decode(json_encode($sheetData), true);
                }
            }

            // If no sheets found, throw error
            if (empty($cachedMnsrData)) {
                throw new Exception('No sheets found in MNSR data. Data integrity issue detected.');
            }

            $tempCachedFile = sys_get_temp_dir() . '/temp_cached_mnsr_' . uniqid() . '.json';
            file_put_contents($tempCachedFile, json_encode($cachedMnsrData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            $this->upload($tempCachedFile, $macroCachedPath);
            @unlink($tempCachedFile);

            $macroOutput = new MacroOutput();
            $macroOutput->batch_id = $batch_id;
            $macroOutput->status = MacroBatchStatusEnum::Successful()->value;
            $macroOutput->file_name = 'Monthly-Natl-Sales-Rept-' . $salesMonthAbbrev . '-' . $salesYear . '.xlsx';
            $macroOutput->file_type_id = MacroFileTypeEnum::MNSR()->value;
            $macroOutput->file_revision_id = MacroFileRevisionEnum::MNSRAddedJBMISData()->value;
            $macroOutput->file_path = $macroOutputPath;
            $macroOutput->cached_path = $macroCachedPath;
            $macroOutput->month = $salesMonth;
            $macroOutput->year = $salesYear;
            $macroOutput->completed_at = now();
            $macroOutput->save();

            DB::commit();

            $macroBatch = MacroBatch::find($batch_id);
            $user = User::find($macroBatch->user_id);
            $user->notify(new GenerateMnsrNotification($batch_id));

            // Get MacroBatchConfig to determine royalty workflow
            $macroBatchConfig = MacroBatchConfig::where('batch_id', $batch_id)->first();

            if (!$macroBatchConfig) {
                throw new Exception("MacroBatchConfig not found for batch_id: {$batch_id}");
            }

            // Only dispatch royalty jobs if gen_rwb is true
            if ($macroBatchConfig->gen_rwb) {
                // Dispatch royalty jobs based on has_uploaded_mnsr flag
                if ($macroBatchConfig->has_uploaded_mnsr) {
                    // Check if there's an existing Royalty for the same month and year from successful, non-deleted batches
                    $existingRoyalty = MacroOutput::join('macro_batches', 'macro_outputs.batch_id', '=', 'macro_batches.id')
                        ->where('macro_outputs.file_type_id', MacroFileTypeEnum::Royalty()->value)
                        ->where('macro_outputs.month', $salesMonth)
                        ->where('macro_outputs.year', $salesYear)
                        ->where('macro_outputs.batch_id', '!=', $batch_id) // Exclude current batch
                        ->where('macro_batches.status', MacroBatchStatusEnum::Successful()->value)
                        ->whereNull('macro_batches.deleted_at') // Exclude soft-deleted batches
                        ->select('macro_outputs.*')
                        ->orderBy('macro_outputs.updated_at', 'desc')
                        ->orderBy('macro_outputs.id', 'desc')
                        ->first();

                    if ($existingRoyalty) {
                        dispatch(new UpdateRoyaltyWorkBookJob($batch_id, $existingRoyalty->id));
                    } else {
                        dispatch(new GenerateRoyaltyWorkBookJob($batch_id));
                    }
                } else {
                    // If MNSR was generated (not uploaded), always create royalty
                    dispatch(new GenerateRoyaltyWorkBookJob($batch_id));
                }
            } else {
                $macroBatch->status = MacroBatchStatusEnum::Successful()->value;
                $macroBatch->completed_at = now();
                $macroBatch->save();
            }
        } catch (Exception $e) {
            DB::rollBack();

            // Log the error to the batch
            $this->logErrorToMacroBatch(
                $batch_id,
                $e,
                'MNSRJbmisPosDataService::addJbmisPosDataToMNSR failed',
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

        return true;
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
     * Process JBMIS data
     */
    private function processJbmisData($batch_id, $parseDecimal, $daysWk1, $daysWk5)
    {
        // Find the JBMIS file for JBC region
        $uploadedJbmisFiles = MacroUpload::where('batch_id', $batch_id)
            ->where('file_type_id', MacroFileTypeEnum::JBMISData()->value)
            ->get();

        $uploadedJbmisFile = null;
        foreach ($uploadedJbmisFiles as $uploadedFile) {
            $jbmisParts = explode('-', $uploadedFile->file_name);
            $jbmisRegion = $jbmisParts[2] ?? '';
            if ($jbmisRegion != 'JBC') {
                continue;
            }
            $uploadedJbmisFile = $uploadedFile;
        }

        if (!$uploadedJbmisFile) {
            throw new Exception('No JBMIS file found for region JBC');
        }

        // Load JBMIS data
        $jbmisRows = json_decode($this->readFile($uploadedJbmisFile->cached_path), true);

        /** -----------------------------------------------------------------
         *  **MOD**  Remove rows where both MONTH_TOTAL & OTHERSALES_TOTAL
         *      are zero – iterate *backwards* so no row is skipped.
         * -----------------------------------------------------------------*/
        for ($i = count($jbmisRows) - 1; $i >= 1; $i--) {       // <-- MOD
            $currentCode = trim($jbmisRows[$i][1] ?? '');

            // End-of-data check
            if ($currentCode === '') {
                array_splice($jbmisRows, $i, 1);

                continue;
            }

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

        // Load conversion table
        $convMap = $this->loadConversionMap($batch_id);

        // Convert JBMIS store codes to standard store codes
        $convertedJbmisRows = [];
        $missingCodes = [];

        foreach ($jbmisRows as $i => $row) {
            if ($i == 0) {
                $row[0] = 'Cluster';
                $row[1] = 'Store Code';
                $convertedJbmisRows[] = $row;

                continue;
            }

            $jbmisCode = trim($row[1] ?? '');

            // Skip consolidation entries (ending with 9)
            if (substr($jbmisCode, -1) === '9') {
                continue;
            }

            if (!isset($convMap[$jbmisCode])) {
                $missingCodes[] = $jbmisCode;
                continue;
            }

            $map = $convMap[$jbmisCode];
            $row[0] = $map['cluster'];
            $row[1] = $map['store'];
            $row[2] = $map['name'];

            $convertedJbmisRows[] = $row;
        }

        if (!empty($missingCodes)) {
            throw new Exception(
                'The following JBMIS store codes are missing from the conversion table: '
                . implode(', ', $missingCodes) . '. '
                . 'Please add these codes to the conversion table and rerun this batch.'
            );
        }

        // Create temp data rows from JBMIS data
        $tempData = [];
        $tempData[] = ['Cluster', 'Store Code', 'Store Name', 'Wk1-Bread', 'Wk1-NonBd', 'Wk2-Bread', 'Wk2-NonBd', 'Wk3-Bread', 'Wk3-NonBd', 'Wk4-Bread', 'Wk4-NonBd', 'Wk5-Bread', 'Wk5-NonBd', 'Tot-Bread', 'Tot-NonBd'];

        // Copy JBMIS data to temp rows
        for ($i = 1; $i < count($convertedJbmisRows); $i++) {
            $row = $convertedJbmisRows[$i];
            $tempRow = [
                $row[0], // Cluster
                $row[1], // Store Code
                $row[2], // Store Name
            ];

            // Calculate weekly values using VBA's logic
            if ($daysWk1 == 8) {
                // First week has 8 days - VBA logic to combine JBMIS weeks 1 and 2
                // MNSR Week 1
                $tempRow[] = $parseDecimal($row[5]) + $parseDecimal($row[9]); // Wk1-Bread
                $tempRow[] = $parseDecimal($row[6]) + $parseDecimal($row[10]); // Wk1-NonBd

                // MNSR Week 2
                $tempRow[] = $parseDecimal($row[13]); // Wk2-Bread
                $tempRow[] = $parseDecimal($row[14]); // Wk2-NonBd

                // MNSR Week 3
                $tempRow[] = $parseDecimal($row[17]); // Wk3-Bread
                $tempRow[] = $parseDecimal($row[18]); // Wk3-NonBd

                // Divide JBMIS week 5 over MNSR weeks 4 and 5
                if ($daysWk5 > 0) {
                    $totDays = 7 + $daysWk5;

                    // MNSR Week 4
                    $tempRow[] = round($parseDecimal($row[21]) * 7 / $totDays, 2); // Wk4-Bread
                    $tempRow[] = round($parseDecimal($row[22]) * 7 / $totDays, 2); // Wk4-NonBd

                    // MNSR Week 5
                    $tempRow[] = round($parseDecimal($row[21]) * $daysWk5 / $totDays, 2); // Wk5-Bread
                    $tempRow[] = round($parseDecimal($row[22]) * $daysWk5 / $totDays, 2); // Wk5-NonBd
                } else {
                    // Week 4 is last week of the month
                    $tempRow[] = $parseDecimal($row[21]); // Wk4-Bread
                    $tempRow[] = $parseDecimal($row[22]); // Wk4-NonBd
                    $tempRow[] = 0; // Wk5-Bread
                    $tempRow[] = 0; // Wk5-NonBd
                }
            } else {
                // Standard mapping of JBMIS data to MNSR weeks
                $tempRow[] = $parseDecimal($row[5]); // Wk1-Bread
                $tempRow[] = $parseDecimal($row[6]); // Wk1-NonBd
                $tempRow[] = $parseDecimal($row[9]); // Wk2-Bread
                $tempRow[] = $parseDecimal($row[10]); // Wk2-NonBd
                $tempRow[] = $parseDecimal($row[13]); // Wk3-Bread
                $tempRow[] = $parseDecimal($row[14]); // Wk3-NonBd
                $tempRow[] = $parseDecimal($row[17]); // Wk4-Bread
                $tempRow[] = $parseDecimal($row[18]); // Wk4-NonBd
                $tempRow[] = $parseDecimal($row[21]); // Wk5-Bread
                $tempRow[] = $parseDecimal($row[22]); // Wk5-NonBd
            }

            // Add monthly totals
            $tempRow[] = $parseDecimal($row[23]); // Tot-Bread
            $tempRow[] = $parseDecimal($row[25]); // Tot-NonBd

            $tempData[] = $tempRow;
        }

        return [
            'rows' => $tempData,
            'convMap' => $convMap,
        ];
    }

    /**
     * Load conversion map
     */
    private function loadConversionMap($batch_id)
    {
        $jbmisCodeConvTableCache = MacroFixedCache::where('batch_id', $batch_id)
            ->where('file_type_id', MacroFileTypeEnum::JBMISCodeConversion()->value)
            ->where('file_revision_id', MacroFileRevisionEnum::JBMISCodeConversionDefault()->value)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$jbmisCodeConvTableCache) {
            throw new Exception('JBMIS Code Conversion Table not found for batch ID: ' . $batch_id);
        }

        $convRows = json_decode($this->readFile($jbmisCodeConvTableCache->cached_path), true);
        $convMap = [];
        foreach ($convRows as $i => $r) {
            if ($i == 0) {
                continue;
            }
            [, $orig, $clusterConv, $storeConv, $nameConv] = $r;
            $convMap[$orig] = ['cluster' => $clusterConv, 'store' => $storeConv, 'name' => $nameConv];
        }

        return $convMap;
    }

    /**
     * Process POS data
     */
    private function processPosData($batch_id, $parseDecimal, $startWk1, $endWk1, $startWk2, $endWk2, $startWk3, $endWk3, $startWk4, $endWk4, $startWk5, $endWk5, $daysWk5)
    {
        $posAggregated = [];

        $uploadedPosFile = MacroUpload::where('batch_id', $batch_id)
            ->where('file_type_id', MacroFileTypeEnum::POSData()->value)
            ->first();

        if (!$uploadedPosFile) {
            return $posAggregated; // Return empty array if no POS file found
        }

        $posRowsRaw = json_decode($this->readFile($uploadedPosFile->cached_path), true);

        // Find the date row (matching VBA logic)
        $dateHeader = null;
        foreach ($posRowsRaw as $i => $r) {
            if (($r[0] ?? '') == 'Date') {
                $dateHeader = $i;
                break;
            }
        }

        if ($dateHeader === null) {
            return $posAggregated; // Return empty array if date header not found
        }

        // Process POS data
        $posRows = array_slice($posRowsRaw, $dateHeader + 1);
        $processedPosRows = $this->processRawPosRows($posRows, $parseDecimal);

        // Aggregate POS data by store and week
        $posAggregated = $this->aggregatePosDataByStore(
            $processedPosRows,
            $startWk1,
            $endWk1,
            $startWk2,
            $endWk2,
            $startWk3,
            $endWk3,
            $startWk4,
            $endWk4,
            $startWk5,
            $endWk5,
            $daysWk5
        );

        return $posAggregated;
    }

    /**
     * Process raw POS rows
     */
    private function processRawPosRows($posRows, $parseDecimal)
    {
        // Load conversion map to get cluster codes
        $convMap = $this->loadConversionMap($this->batchId);

        // Create a store-to-cluster lookup map from the conversion data
        // IMPORTANT: Use first occurrence like VBA does, not last
        $storeToClusterMap = [];
        foreach ($convMap as $jbmisCode => $mapping) {
            if (!isset($storeToClusterMap[$mapping['store']])) {
                // Only set if not already set (use first occurrence like VBA)
                $storeToClusterMap[$mapping['store']] = $mapping['cluster'];
            }
        }

        $processedPosRows = [];

        foreach ($posRows as $row) {
            if (empty($row[0])) {
                continue;
            }

            $storeCode = $row[1] ?? ''; // Already formatted like "B10013"
            if (empty($storeCode)) {
                continue;
            }

            // Extract store name
            $storeName = $row[2] ?? '';
            if (!empty($storeName) && strpos($storeName, '-') !== false) {
                // Format typically: "B10013 - WIRELESS, MANDAUE"
                $parts = explode('-', $storeName, 2);
                $storeName = trim($parts[1] ?? $storeName);
            }

            // Look up the correct cluster code from store-to-cluster map
            $cluster = $storeCode; // Default fallback
            if (isset($storeToClusterMap[$storeCode])) {
                $cluster = $storeToClusterMap[$storeCode];
            }


            $processedRow = [];
            $processedRow[0] = $row[0]; // Date
            $processedRow[1] = $cluster; // Use correct cluster from conversion map
            $processedRow[2] = $storeCode; // Store Code
            $processedRow[3] = $row[2] ?? ''; // Store Name

            // Use parseDecimal for all numeric values
            $processedRow[4] = $parseDecimal($row[3]); // Bread
            $processedRow[5] = $parseDecimal($row[4] ?? 0) +
                $parseDecimal($row[5] ?? 0) +
                $parseDecimal($row[6] ?? 0); // Non-bread total

            $processedPosRows[] = $processedRow;
        }

        // Sort POS data by store code and date
        usort($processedPosRows, function ($a, $b) {
            // Sort by store code first
            $storeCodeCompare = strcmp($a[2], $b[2]);
            if ($storeCodeCompare !== 0) {
                return $storeCodeCompare;
            }

            // If same store, sort by date
            $dateA = new DateTime($this->convertSerialToDate($a[0]));
            $dateB = new DateTime($this->convertSerialToDate($b[0]));

            return $dateA <=> $dateB;
        });

        return $processedPosRows;
    }

    /**
     * Aggregate POS data by store and week
     */
    private function aggregatePosDataByStore($processedPosRows, $startWk1, $endWk1, $startWk2, $endWk2, $startWk3, $endWk3, $startWk4, $endWk4, $startWk5, $endWk5, $daysWk5)
    {
        $posAggregated = [];
        $currentStore = null;
        $currentCluster = null;
        $currentName = null;
        $weekTotals = [
            'wk1Bread' => 0, 'wk1Non' => 0,
            'wk2Bread' => 0, 'wk2Non' => 0,
            'wk3Bread' => 0, 'wk3Non' => 0,
            'wk4Bread' => 0, 'wk4Non' => 0,
            'wk5Bread' => 0, 'wk5Non' => 0,
            'totBread' => 0, 'totNon' => 0,
        ];

        foreach ($processedPosRows as $row) {
            $storeCode = $row[2];
            $cluster = $row[1];
            $name = $row[3];

            // When we encounter a new store, save the previous one's data
            if ($currentStore !== null && $currentStore !== $storeCode) {
                $posAggregated[] = [
                    $currentCluster,
                    $currentStore,
                    $currentName,
                    $weekTotals['wk1Bread'], $weekTotals['wk1Non'],
                    $weekTotals['wk2Bread'], $weekTotals['wk2Non'],
                    $weekTotals['wk3Bread'], $weekTotals['wk3Non'],
                    $weekTotals['wk4Bread'], $weekTotals['wk4Non'],
                    $weekTotals['wk5Bread'], $weekTotals['wk5Non'],
                    $weekTotals['totBread'], $weekTotals['totNon'],
                ];

                // Reset totals for new store
                $weekTotals = [
                    'wk1Bread' => 0, 'wk1Non' => 0,
                    'wk2Bread' => 0, 'wk2Non' => 0,
                    'wk3Bread' => 0, 'wk3Non' => 0,
                    'wk4Bread' => 0, 'wk4Non' => 0,
                    'wk5Bread' => 0, 'wk5Non' => 0,
                    'totBread' => 0, 'totNon' => 0,
                ];
            }

            $currentStore = $storeCode;
            $currentCluster = $cluster;
            $currentName = $name;

            // Get date and determine which week it falls in
            $date = new DateTime($this->convertSerialToDate($row[0]));
            $bread = $row[4];
            $nonBread = $row[5];

            // Skip dates before the start of the month
            if ($date < $startWk1) {
                continue;
            }

            // Add values to appropriate week totals
            if ($date >= $startWk1 && $date <= $endWk1) {
                $weekTotals['wk1Bread'] += $bread;
                $weekTotals['wk1Non'] += $nonBread;
            } elseif ($date >= $startWk2 && $date <= $endWk2) {
                $weekTotals['wk2Bread'] += $bread;
                $weekTotals['wk2Non'] += $nonBread;
            } elseif ($date >= $startWk3 && $date <= $endWk3) {
                $weekTotals['wk3Bread'] += $bread;
                $weekTotals['wk3Non'] += $nonBread;
            } elseif ($date >= $startWk4 && $date <= $endWk4) {
                $weekTotals['wk4Bread'] += $bread;
                $weekTotals['wk4Non'] += $nonBread;
            } elseif ($daysWk5 > 0 && $date >= $startWk5 && $date <= $endWk5) {
                $weekTotals['wk5Bread'] += $bread;
                $weekTotals['wk5Non'] += $nonBread;
            }

            // Add to monthly totals
            $weekTotals['totBread'] += $bread;
            $weekTotals['totNon'] += $nonBread;
        }

        // Don't forget to add the last store processed
        if ($currentStore !== null) {
            $posAggregated[] = [
                $currentCluster,
                $currentStore,
                $currentName,
                $weekTotals['wk1Bread'], $weekTotals['wk1Non'],
                $weekTotals['wk2Bread'], $weekTotals['wk2Non'],
                $weekTotals['wk3Bread'], $weekTotals['wk3Non'],
                $weekTotals['wk4Bread'], $weekTotals['wk4Non'],
                $weekTotals['wk5Bread'], $weekTotals['wk5Non'],
                $weekTotals['totBread'], $weekTotals['totNon'],
            ];
        }

        return $posAggregated;
    }

    /**
     * Create temporary data combining JBMIS and POS data
     */
    private function createTempData($jbmisRows, $posAggregated)
    {
        $tempData = $jbmisRows;

        // Add POS data to the temp data
        foreach ($posAggregated as $row) {
            $tempData[] = $row;
        }

        return $tempData;
    }

    /**
     * Consolidate data by combining duplicate entries based on cluster AND store code (matching VBA logic)
     */
    private function consolidateData($tempData): array
    {
        $header = array_shift($tempData);

        // Sort by cluster code first, then store code (matching VBA behavior)
        usort($tempData, function ($a, $b) {
            // First compare by cluster code
            $clusterCompare = strcmp($a[0], $b[0]);
            if ($clusterCompare !== 0) {
                return $clusterCompare;
            }
            // If clusters are the same, compare by store code
            return strcmp($a[1], $b[1]);
        });

        // Consolidate duplicate entries (same cluster AND store code - matching VBA logic)
        $consolidated = [$header];
        $prevClusterCode = null;
        $prevStoreCode = null;

        foreach ($tempData as $row) {
            $currentClusterCode = $row[0] ?? '';
            $currentStoreCode = $row[1] ?? '';

            // First row or different cluster/store combination than previous
            if ($prevClusterCode === null || $prevStoreCode === null ||
                $prevClusterCode !== $currentClusterCode || $prevStoreCode !== $currentStoreCode) {
                $consolidated[] = $row;
                $prevClusterCode = $currentClusterCode;
                $prevStoreCode = $currentStoreCode;

                continue;
            }

            // Same cluster AND store code as previous - add values (matching VBA logic)
            $lastIndex = count($consolidated) - 1;


            // Add numeric values (columns 3 and onwards)
            for ($c = 3; $c < count($row); $c++) {
                $consolidated[$lastIndex][$c] = ($consolidated[$lastIndex][$c] ?? 0) + ($row[$c] ?? 0);
            }

            // Note: No need to update cluster or store code since they must be identical for consolidation
            // Only update store name if the first entry is empty
            if (empty($consolidated[$lastIndex][2]) && !empty($row[2])) {
                $consolidated[$lastIndex][2] = $row[2]; // Store name
            }
        }

        return $consolidated;
    }

    /**
     * Update MNSR data with consolidated data
     */
    private function updateMnsrData($batch_id, $consolidated, $daysWk5, $salesMonth, $salesYear, $weekIntervals)
    {
        // Load MNSR sheet data (franchisee stage)
        $outputMnsrFile = MacroOutput::where('batch_id', $batch_id)
            ->where('file_type_id', MacroFileTypeEnum::MNSR()->value)
            ->where('file_revision_id', MacroFileRevisionEnum::MNSRAddedFranchiseeData()->value)
            ->latest('created_at')
            ->first();

        if ($outputMnsrFile == null) {
            $macroUploads = MacroUpload::where('batch_id', $batch_id)->get();
            $non_jbc_count = 0;

            foreach ($macroUploads as $macroUpload) {
                if ($macroUpload->file_type_id == MacroFileTypeEnum::JBMISData()->value) {
                    $jbmisParts = explode('-', $macroUpload->file_name);
                    $jbmisRegion = $jbmisParts[2];

                    if ($jbmisRegion != 'JBC') {
                        $non_jbc_count++;
                    }
                }
            }

            if (count($macroUploads) == 0 || $non_jbc_count == 0) {
                $outputMnsrFile = MacroOutput::where('batch_id', $batch_id)
                    ->where('file_type_id', MacroFileTypeEnum::MNSR()->value)
                    ->where('file_revision_id', MacroFileRevisionEnum::MNSRDefault()->value)
                    ->latest('created_at')
                    ->first();
            }
        }
        $mnsrData = json_decode($this->readFile($outputMnsrFile->cached_path), true);

        // Dynamic sheet handling - find the latest open sheet regardless of format
        $latestSheetData = $this->getLatestOpenSheet($mnsrData);
        // Create a deep copy of the latest sheet data for processing
        $mnsrSheets = json_decode(json_encode($latestSheetData), true);

        // Extract week dates from weekIntervals for store closure validation
        $weekDates = [
            ['start' => $weekIntervals['startWk1'], 'end' => $weekIntervals['endWk1']],
            ['start' => $weekIntervals['startWk2'], 'end' => $weekIntervals['endWk2']],
            ['start' => $weekIntervals['startWk3'], 'end' => $weekIntervals['endWk3']],
            ['start' => $weekIntervals['startWk4'], 'end' => $weekIntervals['endWk4']],
            ['start' => $weekIntervals['startWk5'] ?? null, 'end' => $weekIntervals['endWk5'] ?? null],
        ];

        // Process each consolidated entry, matching against all MNSR rows each time
        $header = array_shift($consolidated); // Remove header row

        foreach ($consolidated as $tempRow) {
            $tempStoreCode = $tempRow[1] ?? null;
            if (!$tempStoreCode) {
                continue;
            }


            // For each entry in consolidated data, scan through all MNSR rows
            foreach ($mnsrSheets as $ri => &$mnsrRow) {
                if ($ri < 7) {
                    continue;
                } // Skip header rows
                $mnsrStoreCode = $mnsrRow[1] ?? null;
                if (!$mnsrStoreCode || $mnsrStoreCode !== $tempStoreCode) {
                    continue;
                }


                // Debug logging for store B10049
                $mnsrClusterCode = $mnsrRow[0] ?? null; // Column A (0-based index 0)
                $isB10049Debug = ($mnsrStoreCode === 'B10049' && $mnsrClusterCode === 'B10049');


                // This is a match between temp row and MNSR row
                $this->reApplyMnsrFormulaValueAtIndex($mnsrSheets, $ri);

                // Check if date opened is valid before updating any sales data
                $dateOpened = $mnsrRow[3] ?? null; // Column D (0-based index 3)


                // Only skip stores with future opening dates (2049-12-31)
                if ($dateOpened !== null && $dateOpened !== '' && $dateOpened !== 0) {
                    $dateToCheck = $this->parseDateValue($dateOpened);
                    if ($dateToCheck && $dateToCheck->format('Y-m-d') === '2049-12-31') {
                        continue; // Skip future stores only
                    }
                }

                // Get store closure dates for validation
                $tempClosure = $mnsrRow[59] ?? null; // Column BH (Temp Closure)
                $reOpening = $mnsrRow[60] ?? null;   // Column BI (Re-opening)

                // Update the MNSR data with values from temp data
                // Week 1
                $wasOperationalWk1 = $this->validateStoreOperationalStatus(
                    $dateOpened,
                    $weekDates[0]['end'],
                    $weekDates[0]['start'],
                    $tempClosure,
                    $reOpening,
                    $mnsrRow[4] ?? null  // Date Closed (Column E)
                );


                if ($wasOperationalWk1) {
                    if ($tempRow[3] != 0) { // wk1Bread
                        $mnsrRow[15] = $tempRow[3]; // Bread
                        $mnsrRow[16] = $tempRow[4]; // Non-bread
                    } elseif ($mnsrRow[14] == 'A') { // If marked as Actual but no data
                        $mnsrRow[15] = 0;
                        $mnsrRow[16] = 0;
                    }
                }

                // Week 2
                $wasOperationalWk2 = $this->validateStoreOperationalStatus(
                    $dateOpened,
                    $weekDates[1]['end'],
                    $weekDates[1]['start'],
                    $tempClosure,
                    $reOpening,
                    $mnsrRow[4] ?? null  // Date Closed (Column E)
                );

                if ($wasOperationalWk2) {
                    if ($tempRow[5] != 0) { // wk2Bread
                        $mnsrRow[19] = $tempRow[5]; // Bread
                        $mnsrRow[20] = $tempRow[6]; // Non-bread
                    } elseif ($mnsrRow[18] == 'A') { // If marked as Actual but no data
                        $mnsrRow[19] = 0;
                        $mnsrRow[20] = 0;
                    }
                }

                // Week 3
                $wasOperationalWk3 = $this->validateStoreOperationalStatus(
                    $dateOpened,
                    $weekDates[2]['end'],
                    $weekDates[2]['start'],
                    $tempClosure,
                    $reOpening,
                    $mnsrRow[4] ?? null  // Date Closed (Column E)
                );

                if ($wasOperationalWk3) {
                    if ($tempRow[7] != 0) { // wk3Bread
                        $mnsrRow[23] = $tempRow[7]; // Bread
                        $mnsrRow[24] = $tempRow[8]; // Non-bread
                    } elseif ($mnsrRow[22] == 'A') { // If marked as Actual but no data
                        $mnsrRow[23] = 0;
                        $mnsrRow[24] = 0;
                    }
                }

                // Week 4 - This is where AB (Column 28) gets updated
                $wasOperationalWk4 = $this->validateStoreOperationalStatus(
                    $dateOpened,
                    $weekDates[3]['end'],
                    $weekDates[3]['start'],
                    $tempClosure,
                    $reOpening,
                    $mnsrRow[4] ?? null  // Date Closed (Column E)
                );


                if ($wasOperationalWk4) {
                    if ($tempRow[9] != 0) { // wk4Bread
                        $mnsrRow[27] = $tempRow[9]; // Bread - Column AB
                        $mnsrRow[28] = $tempRow[10]; // Non-bread
                    } elseif ($mnsrRow[26] == 'A') { // If marked as Actual but no data
                        $mnsrRow[27] = 0;
                        $mnsrRow[28] = 0;
                    }
                }

                // Week 5
                if ($weekDates[4]['start'] !== null && $weekDates[4]['end'] !== null) {
                    $wasOperationalWk5 = $this->validateStoreOperationalStatus(
                        $dateOpened,
                        $weekDates[4]['end'],
                        $weekDates[4]['start'],
                        $tempClosure,
                        $reOpening,
                        $mnsrRow[4] ?? null  // Date Closed (Column E)
                    );

                    if ($wasOperationalWk5) {
                        if ($tempRow[11] != 0) { // wk5Bread
                            $mnsrRow[32] = $tempRow[11]; // Bread
                            $mnsrRow[33] = $tempRow[12]; // Non-bread
                        } elseif ($mnsrRow[31] == 'A') { // If marked as Actual but no data
                            $mnsrRow[32] = 0;
                            $mnsrRow[33] = 0;
                        }
                    }
                }

                // Total
                if ($tempRow[13] != 0) { // totBread
                    $mnsrRow[45] = $tempRow[13]; // Total Bread
                    $mnsrRow[46] = $tempRow[14]; // Total Non-bread
                }

            }
        }

        $this->reApplyMnsrFormulaValues($mnsrSheets);

        // IMPORTANT: Only apply formula calculations AFTER updating all data
        // Mark complete/incomplete sales reporting
        foreach ($mnsrSheets as $ri => &$mnsrRow) {
            if ($ri < 7) {
                continue;
            } // Skip header rows
            if (empty($mnsrRow[0])) {
                continue;
            } // Skip empty rows
            $hasFiveWeeks = $daysWk5 > 0;
            if ($hasFiveWeeks) {
                // There are 5 weeks of sales data
                if ($mnsrRow[14] == 'A' &&
                    $mnsrRow[18] == 'A' &&
                    $mnsrRow[22] == 'A' &&
                    $mnsrRow[26] == 'A' &&
                    $mnsrRow[31] == 'A') {
                    $mnsrRow[35] = 'Y';
                } else {
                    $mnsrRow[35] = 'N';
                }
            } else {
                // There are only 4 weeks of sales data
                if ($mnsrRow[14] == 'A' &&
                    $mnsrRow[18] == 'A' &&
                    $mnsrRow[22] == 'A' &&
                    $mnsrRow[26] == 'A') {
                    $mnsrRow[35] = 'Y';
                } else {
                    $mnsrRow[35] = 'N';
                }
            }
        }
        unset($mnsrRow);

        // 1. Add estimated sales first
        $this->addEstimatedSalesColumns($mnsrSheets, $salesMonth, $salesYear);

        // 2. Distribute estimated sales across weeks marked as "E"
        $this->distributeEstimatedSalesAcrossWeeks($mnsrSheets, $weekIntervals);

        // 3. Set Excel formulas for final output (handles A/E calculation automatically)
        $this->reApplyMnsrFormulas($mnsrSheets);

        $proFormaDataWithTotal = $this->addMnsrTotals($mnsrSheets);

        // Update the original data structure with the modified sheet
        $currentSheetName = 'Adjust-' . $this->adjustNbr;

        // Check if original data has sheet structure
        $hasSheetStructure = false;
        foreach (array_keys($mnsrData) as $key) {
            if (preg_match('/^Adjust-\d+$/', $key)) {
                $hasSheetStructure = true;
                break;
            }
        }

        if ($hasSheetStructure) {
            // Multi-sheet format: update the current sheet with modifications
            $mnsrData[$currentSheetName] = $mnsrSheets;
        } else {
            // Single array format: the modifications are already in mnsrSheets
            $mnsrData = $mnsrSheets;
        }

        return [
            'mnsrSheets' => $mnsrSheets,
            'proFormaDataWithTotal' => $proFormaDataWithTotal,
            'originalMnsrData' => $mnsrData,
        ];
    }

    /**
     * Add estimated sales columns (BM, BN, BO) based on 6-month average
     *
     * Logic:
     * 1. For each store, collect actual sales data from the past 6 months
     * 2. For months with missing or zero sales data, use projected sales (BJ/BK columns)
     * 3. Calculate 6-month average including all available data (actual + projected)
     */
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

        // Week start and end dates for validation (Carbon objects from calculateWeekIntervals)
        $weekStartDates = [
            $weekIntervals['startWk1'],
            $weekIntervals['startWk2'],
            $weekIntervals['startWk3'],
            $weekIntervals['startWk4'],
            isset($weekIntervals['startWk5']) ? $weekIntervals['startWk5'] : null
        ];

        $weekEndDates = [
            $weekIntervals['endWk1'],
            $weekIntervals['endWk2'],
            $weekIntervals['endWk3'],
            $weekIntervals['endWk4'],
            isset($weekIntervals['endWk5']) ? $weekIntervals['endWk5'] : null
        ];

        // Column mappings for A/E indicators and sales amounts
        $weekIndicators = [14, 18, 22, 26, 31]; // O, S, W, AA, AF
        $weekSalesColumns = [
            [15, 16], // Week 1: P(bread), Q(non-bread)
            [19, 20], // Week 2: T(bread), U(non-bread)
            [23, 24], // Week 3: X(bread), Y(non-bread)
            [27, 28], // Week 4: AB(bread), AC(non-bread)
            [32, 33]  // Week 5: AG(bread), AH(non-bread)
        ];
        $estimatedColumns = [51, 52, 53, 54, 55]; // AZ, BA, BB, BC, BD

        foreach ($mnsrSheets as $ri => &$mnsrRow) {
            if ($ri < 7 || empty($mnsrRow[0])) {
                continue; // Skip headers and empty rows
            }

            // Debug logging for store B10049
            $storeCode = $mnsrRow[1] ?? null;
            $clusterCode = $mnsrRow[0] ?? null;
            $isB10049Debug = ($storeCode === 'B10049' && $clusterCode === 'B10049');


            // Get all date fields
            $dateOpened = $mnsrRow[3] ?? null; // Column D (Date Opened)
            $tempClosure = $mnsrRow[59] ?? null; // Column BH (Temp Closure)
            $reOpening = $mnsrRow[60] ?? null; // Column BI (Re-opening)


            // Check for future date skip (2049-12-31) - skip entire store
            $dateOpenedParsed = $this->parseDateValue($dateOpened);
            if ($dateOpenedParsed && $dateOpenedParsed->format('Y-m-d') === '2049-12-31') {
                continue;
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


            // Process each week with enhanced validation
            for ($week = 0; $week < 5; $week++) {
                if ($weekDays[$week] <= 0 || !isset($weekStartDates[$week])) {
                    continue;
                }

                // Calculate weekly amounts
                $weeklyBreadAmount = round($dailyBreadRate * $weekDays[$week], 2);
                $weeklyNonBreadAmount = round($dailyNonBreadRate * $weekDays[$week], 2);


                // Enhanced validation with permanent closure check

                $wasOpenDuringWeek = $this->validateStoreOperationalStatus(
                    $dateOpened,
                    $weekEndDates[$week],
                    $weekStartDates[$week],
                    $tempClosure,
                    $reOpening,
                    $mnsrRow[4] ?? null  // Date Closed (Column E)
                );


                // Only populate AZ-BD columns if store was operational
                if ($wasOpenDuringWeek && $weeklyBreadAmount > 0) {
                    $mnsrRow[$estimatedColumns[$week]] = $weeklyBreadAmount;
                }

                // Also update actual sales columns if marked "E" and store was open
                if ($wasOpenDuringWeek &&
                    ($mnsrRow[$weekIndicators[$week]] ?? '') === 'E' &&
                    $weekDays[$week] > 0) {

                    $mnsrRow[$weekSalesColumns[$week][0]] = $weeklyBreadAmount;
                    $mnsrRow[$weekSalesColumns[$week][1]] = $weeklyNonBreadAmount;

                }
            }

        }
        unset($mnsrRow);
    }

    /**
     * Write output to Excel file with all sheets
     */
    private function writeOutputToExcelWithAllSheets($mnsrData, $batch_id, $salesMonth, $salesYear)
    {
        $monthAbbrev = Carbon::createFromFormat('m', $salesMonth)->format('M');
        $fullPath = sys_get_temp_dir() . '/3-Monthly-Natl-Sales-Rept-' . $monthAbbrev . '-' . $salesYear . '.xlsx';

        // Prepare all sheets data
        $allSheetsData = [];
        $currentSheetName = 'Adjust-' . $this->adjustNbr;

        // Get the original MNSR data structure with all sheets
        $originalMnsrData = $mnsrData['originalMnsrData'];

        // Process all sheets - copy all original sheets and update only the current one
        foreach ($originalMnsrData as $sheetName => $sheetData) {
            if ($sheetName === $currentSheetName) {
                // Use the modified data for the current sheet only
                $allSheetsData[$sheetName] = $mnsrData['mnsrSheets'];
            } else {
                // Keep original data for other sheets unchanged - create deep copy to prevent contamination
                $allSheetsData[$sheetName] = json_decode(json_encode($sheetData), true);
            }
        }

        // Create Excel file with all sheets
        $this->createExcelFromAllSheets($allSheetsData, $fullPath);

        return $fullPath;
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

    private function ensureDateConsistency(&$mnsrSheets)
    {
        // When loading cached data, ensure dates are in consistent format
        foreach ($mnsrSheets as &$row) {
            if (!is_array($row)) {
                continue;
            }

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
                    trim($salesSheet[3][0]) === 'T H I S   S H E E T   I S   C L O S E D') {
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
     * Create Excel file from all sheets in JSON data
     */
    private function createExcelFromAllSheets($allSheetsData, $outputPath)
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
            $sheetDataWithTotals = $this->addMnsrTotals($isolatedSheetData);

            // Populate sheet with data
            $freshSheet->fromArray($sheetDataWithTotals, null, 'A1');

            // For the first sheet, use this as the main spreadsheet
            if ($sheetIndex === 0) {
                $mainSpreadsheet = $freshTemplate;
            } else {
                // For subsequent sheets, add the fresh sheet to the main spreadsheet
                $mainSpreadsheet->addSheet($freshSheet);
            }

            $sheetIndex++;

            // Apply date formatting - columns Q, U, Y, AC, AH for rows 3 and 4
            foreach (['Q', 'U', 'Y', 'AC', 'AH'] as $col) {
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
                        $freshSheet->getStyle($col . $row)
                            ->getNumberFormat()
                            ->setFormatCode('d-MMM-yy');
                    }
                }
            }

            // Apply number formatting to columns BE and BF to show 0.000 format AND force values
            $maxRow = $freshSheet->getHighestRow();
            foreach (['BE', 'BF'] as $col) {
                for ($row = 8; $row <= $maxRow; $row++) {
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
                    if ($currentValue === null || $currentValue === '' || $currentValue === 0) {
                        $cell->setValue(0.000);
                    }

                    // Apply number formatting to show 0.000 format
                    $freshSheet->getStyle($col . $row)
                        ->getNumberFormat()
                        ->setFormatCode('0.000');
                }
            }

            // Format dates for columns D, E, BG, BH, BI for rows 8 and beyond
            foreach (['D', 'E', 'BG', 'BH', 'BI'] as $col) {
                for ($row = 8; $row <= $maxRow; $row++) {
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

                    if ($col === 'D') {
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
            } // Sub-case 4B: Unusual sequence (TC >= RO)
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
    private function wasStoreOpenDuringWeek($dateOpened, $weekEndDate): bool
    {
        // Convert weekEndDate to Carbon if it's a DateTime
        if ($weekEndDate instanceof DateTime) {
            $weekEndDate = Carbon::instance($weekEndDate);
        }

        // Use enhanced validation with null for temp closure dates (backward compatibility)
        return $this->validateStoreOperationalStatus($dateOpened, $weekEndDate, null, null, null);
    }

}
