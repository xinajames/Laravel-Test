<?php

namespace App\Services\Royalty;

use App\Enums\MacroBatchStatusEnum;
use App\Enums\MacroFileRevisionEnum;
use App\Enums\MacroFileTypeEnum;
use App\Jobs\Royalty\AddFranchiseeDataToMnsrJob;
use App\Models\Royalty\MacroFixedCache;
use App\Models\Royalty\MacroOutput;
use App\Traits\ErrorLogger;
use App\Traits\HandlesRoyaltyData;
use App\Traits\ManageFilesystems;
use DateTime;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

class MNSRService
{
    use ErrorLogger;
    use HandlesRoyaltyData;
    use ManageFilesystems;

    /**
     * @param  $batch_id  int Batch ID of the Macro Batch
     * @param  $salesMonth  int 1 - January to 12 - December
     * @param  $salesYear  int
     */
    public function createMNSR($batch_id, $salesMonth, $salesYear)
    {
        DB::beginTransaction();

        try {

            $startDate = Carbon::createFromDate($salesYear, $salesMonth, 1)->setTime(0, 0);
            $futureDate = Carbon::createFromDate(2049, 12, 31)->setTime(0, 0);

            $basePath = $this->generateUploadBasePath() . '/royalty/fixed-caches/';
            $proFormaData = json_decode($this->readFile($basePath . 'proforma-natl-sales-by-store-template.json'), true);

            $branchFranMasterCache = MacroFixedCache::where('batch_id', $batch_id)
                ->where('file_type_id', MacroFileTypeEnum::BranchFranMaster()->value)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$branchFranMasterCache) {
                throw new RuntimeException('Branch Fran Master data not found for batch ID: ' . $batch_id);
            }

            $branchFranMasterData = json_decode($this->readFile($branchFranMasterCache->cached_path), true);
            $branchesData = $branchFranMasterData[0];
            $branchHistoriesData = $branchFranMasterData[1];
            $branchAddressesData = $branchFranMasterData[2];
            $franchiseesData = $branchFranMasterData[3];

            $salesHistoryByStoreCache = MacroFixedCache::where('batch_id', $batch_id)
                ->where('file_type_id', MacroFileTypeEnum::JBSSalesHistoryByStore()->value)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$salesHistoryByStoreCache) {
                throw new RuntimeException('JBS Sales History By Store data not found for batch ID: ' . $batch_id);
            }

            $allSalesHistorySheets = json_decode($this->readFile($salesHistoryByStoreCache->cached_path), true);

            if (!isset($allSalesHistorySheets['Last12Mos'])) {
                throw new RuntimeException('Last12Mos sheet not found in JBS Sales History By Store data for batch ID: ' . $batch_id);
            }

            $salesHistoryByStoreData = $allSalesHistorySheets['Last12Mos'];

            $dates = $this->getFirstAndLastDaysOfEachWeek($salesMonth, $salesYear);

            $proFormaData[2][16] = $dates[0]['start_date'];
            $proFormaData[3][16] = $dates[0]['end_date'];
            $proFormaData[4][16] = $dates[0]['days'];

            $proFormaData[2][20] = $dates[1]['start_date'];
            $proFormaData[3][20] = $dates[1]['end_date'];
            $proFormaData[4][20] = $dates[1]['days'];

            $proFormaData[2][24] = $dates[2]['start_date'];
            $proFormaData[3][24] = $dates[2]['end_date'];
            $proFormaData[4][24] = $dates[2]['days'];

            $proFormaData[2][28] = $dates[3]['start_date'];
            $proFormaData[3][28] = $dates[3]['end_date'];
            $proFormaData[4][28] = $dates[3]['days'];

            if (isset($dates[4])) {
                $proFormaData[2][33] = $dates[4]['start_date'];
                $proFormaData[3][33] = $dates[4]['end_date'];
                $proFormaData[4][33] = $dates[4]['days'];
            }

            $iterationIndex = 0;
            foreach ($branchesData as $branchData) {
                if ($iterationIndex < 6) {
                    $iterationIndex++;

                    continue;
                }

                // convert date columns in sheets
                $this->convertSheetDatesFromSerialToDate($iterationIndex, $branchesData, $branchHistoriesData, $branchAddressesData, $franchiseesData, $salesHistoryByStoreData);

                if ($branchesData[$iterationIndex][7] == 'Closed') {
                    // convert branch history 1st renewal column to carbon format
                    if (isset($branchHistoriesData[$iterationIndex][16])) {
                        $date = ($branchHistoriesData[$iterationIndex][16] != null) ? Carbon::parse($branchHistoriesData[$iterationIndex][16]) : null;

                        if ($date == null || $date < $startDate) { // if date from branch < start date from branch-history
                            $iterationIndex++;

                            continue;
                        }
                    } else {
                        $iterationIndex++;

                        continue;
                    }
                }

                $proFormaData[$iterationIndex + 1][0] = $branchesData[$iterationIndex][58] ?? null;
                $proFormaData[$iterationIndex + 1][1] = $branchesData[$iterationIndex][1] ?? null;
                $proFormaData[$iterationIndex + 1][2] = $branchAddressesData[$iterationIndex][15] ?? null;


                // In your createMNSR method, when handling the Future branches:
                if ($branchesData[$iterationIndex][7] == 'Future') {
                    // Store the future date as a string in Y-m-d format
                    $proFormaData[$iterationIndex + 1][3] = $futureDate->format('Y-m-d');
                    // Column E (index 4) should remain empty for Future branches
                    $proFormaData[$iterationIndex + 1][4] = null;
                } elseif ($branchesData[$iterationIndex][7] == 'TemporaryClosed') {
                    // Handle TemporaryClosed branches
                    $openDate = $branchesData[$iterationIndex][31] ?? null;
                    $tempClosure = $branchesData[$iterationIndex][29] ?? null;

                    // Set open date (column D, index 3)
                    if ($openDate !== null && $openDate !== '') {
                        $proFormaData[$iterationIndex + 1][3] = $openDate;
                    } else {
                        $proFormaData[$iterationIndex + 1][3] = null; // This will be converted to 0 later
                    }

                    // Set sales date (column E, index 4) to temp closure date
                    if ($tempClosure !== null && $tempClosure !== '') {
                        $proFormaData[$iterationIndex + 1][4] = $tempClosure;
                    } else {
                        $proFormaData[$iterationIndex + 1][4] = null; // This will be converted to 0 later
                    }
                } else {
                    // Handle regular branches
                    $openDate = $branchesData[$iterationIndex][31] ?? null;
                    $salesDate = $branchHistoriesData[$iterationIndex][16] ?? null;

                    if ($openDate !== null && $openDate !== '') {
                        $proFormaData[$iterationIndex + 1][3] = $openDate;
                    } else {
                        $proFormaData[$iterationIndex + 1][3] = null; // This will be converted to 0 later
                    }

                    if ($salesDate !== null && $salesDate !== '') {
                        $proFormaData[$iterationIndex + 1][4] = $salesDate;
                    } else {
                        $proFormaData[$iterationIndex + 1][4] = null; // This will be converted to 0 later
                    }
                }

                $proFormaData[$iterationIndex + 1][5] = $branchesData[$iterationIndex][11] ?? null;
                $proFormaData[$iterationIndex + 1][8] = 'ZZZ';

                if ($branchesData[$iterationIndex][11] == 'F12258') {
                    $proFormaData[$iterationIndex + 1][8] = 'RMG';
                } elseif ($branchesData[$iterationIndex][11] == 'F12261') {
                    $proFormaData[$iterationIndex + 1][8] = 'BMI';
                } elseif ($branchesData[$iterationIndex][11] == 'F12463') {
                    $proFormaData[$iterationIndex + 1][8] = 'ESC';
                } elseif ($branchesData[$iterationIndex][11] == 'F12804') {
                    $proFormaData[$iterationIndex + 1][8] = 'COM';
                } elseif ($branchesData[$iterationIndex][11] == 'F12917') {
                    $proFormaData[$iterationIndex + 1][8] = 'KKM';
                } elseif ($branchesData[$iterationIndex][11] == 'F10976') {
                    $proFormaData[$iterationIndex + 1][8] = 'JFC';
                }

                if ($branchesData[$iterationIndex][13] == '') {
                    $proFormaData[$iterationIndex + 1][6] = $branchesData[$iterationIndex][14] . ', ' . $branchesData[$iterationIndex][15];
                } else {
                    $proFormaData[$iterationIndex + 1][6] = $branchesData[$iterationIndex][13];
                }

                $proFormaData[$iterationIndex + 1][11] = '';
                $proFormaData[$iterationIndex + 1][56] = $branchesData[$iterationIndex][21] ?? 0.000; // old royalty %
                $proFormaData[$iterationIndex + 1][57] = $branchesData[$iterationIndex][22] ?? 0.000; // new royalty %
                $proFormaData[$iterationIndex + 1][58] = $branchesData[$iterationIndex][23]; // royalty effect dt
                $proFormaData[$iterationIndex + 1][59] = $branchesData[$iterationIndex][29]; // temp closure
                $proFormaData[$iterationIndex + 1][60] = $branchesData[$iterationIndex][30]; // re-opening
                $proFormaData[$iterationIndex + 1][61] = $branchesData[$iterationIndex][60]; // projected peso sales bread
                $proFormaData[$iterationIndex + 1][62] = $branchesData[$iterationIndex][61]; // projected peso sales non-bread

                $proFormaData[$iterationIndex + 1][63] = $branchAddressesData[$iterationIndex][73] ?? null; // warehouse code
                $proFormaData[$iterationIndex + 1][7] = $branchAddressesData[$iterationIndex][22] ?? null; // region

                if (isset($branchAddressesData[$iterationIndex]) && $branchAddressesData[$iterationIndex][25] == 0) {
                    $proFormaData[$iterationIndex + 1][9] = $branchAddressesData[$iterationIndex][24]; // franchise district code
                } elseif (isset($branchAddressesData[$iterationIndex])) {
                    $proFormaData[$iterationIndex + 1][9] = $branchAddressesData[$iterationIndex][25]; // bgc district code
                }

                if (isset($branchAddressesData[$iterationIndex])) {
                    $proFormaData[$iterationIndex + 1][10] = $branchAddressesData[$iterationIndex][26]; // district name
                }

                // note :: can be removed?
                $proRowIndex = $iterationIndex + 1;

                // if the branch open date (col 4; index 3) equals the future date, skip estimated sales processing.
                if ($proFormaData[$proRowIndex][3] === '2049-12-31') {
                    $iterationIndex++;
                    continue;
                }

                // find the matching sales history record by comparing the store code.
                $storeCode = trim($proFormaData[$proRowIndex][1] ?? '');
                $matchingHistory = null;
                foreach ($salesHistoryByStoreData as $histIndex => $histRow) {
                    if ($histIndex < 4) { // assuming header rows (up to row 4) are skipped
                        continue;
                    }

                    if (trim($histRow[2] ?? '') === $storeCode) {
                        $matchingHistory = $histRow;
                        break;
                    }
                }

                if (!$matchingHistory) {
                    // Log::warning("A Store in the Monthly Sales Report is not found in Sales History Report: {$proFormaData[$proRowIndex][1]} - {$proFormaData[$proRowIndex][2]}");
                    $iterationIndex++;

                    continue;
                }

                // if column 12 (index 11) is not empty, skip estimated sales calculation.
                if (!empty($proFormaData[$proRowIndex][11])) {
                    $iterationIndex++;

                    continue;
                }

                // define week boundaries
                $week1 = $dates[0];
                $week2 = $dates[1] ?? null;
                $week3 = $dates[2] ?? null;
                $week4 = $dates[3] ?? null;
                $week5 = $dates[4] ?? null;

                // helper function to determine if estimated sales should be calculated for a given week.
                // Note: In this context, $proFormaData[$proRowIndex][3] holds the branch open date (col 4)
                // and $proFormaData[$proRowIndex][4] holds the sales date value (col 5).
                // The TC and RO values are in columns 60 and 61 (indices 59 and 60).
                $shouldEstimateForWeek = function ($row, $weekStart, $weekEnd) {
                    // VBA Logic: First check the sales date and open date basic conditions
                    $basicCondition = (empty($row[4]) || $row[4] > $weekStart) &&
                                     (($row[3] < $weekStart) || ($row[3] >= $weekStart && $row[3] <= $weekEnd));

                    // VBA Logic: Second condition - check open date range
                    $openDateCondition = ($row[3] < $weekStart) || ($row[3] >= $weekStart && $row[3] <= $weekEnd);

                    // If open date condition is met, check TC/RO logic
                    if ($openDateCondition) {
                        $tc = $row[59] ?? '';
                        $ro = $row[60] ?? '';

                        // VBA Case 1: Both TC and RO are empty - bypass sales date check ONLY if sales date >= week start
                        // This prevents estimating weeks that are far in the future from the sales date
                        if (empty($tc) && empty($ro)) {
                            // Only bypass if sales date is empty OR sales date >= week start (not way before)
                            if (empty($row[4]) || $row[4] >= $weekStart) {
                                return true;
                            }
                        }

                        // For other TC/RO cases, still need basic sales date condition
                        if ($basicCondition) {
                            // TC is empty but RO is not
                            if (empty($tc) && !empty($ro)) {
                                return $ro <= $weekEnd;
                            }

                            // RO is empty but TC is not
                            if (!empty($tc) && empty($ro)) {
                                return $tc > $weekStart;
                            }

                            // Both TC and RO have values
                            if (!empty($tc) && !empty($ro)) {
                                if ($tc < $ro) {
                                    // TC < RO branch
                                    if ($tc > $weekStart) {
                                        return true;
                                    }
                                    if ($ro <= $weekEnd) {
                                        return true;
                                    }
                                } else {
                                    // TC >= RO branch
                                    if ($tc > $weekStart) {
                                        return true;
                                    }
                                    if ($ro <= $weekEnd && $tc > $weekStart) {
                                        return true;
                                    }
                                }
                            }
                        }
                    }

                    return false;
                };

                // apply the estimated sales calculation for each week.
                // note: the mapping is as follows:
                // Week 1: Estimated Bread Sales → col 16 (index 15) and duplicate to col 52 (index 51);
                //         Estimated Non-Bread Sales → col 17 (index 16).
                // Week 2: Bread → col 20 (index 19), duplicate to col 53 (index 52); Non-Bread → col 21 (index 20).
                // Week 3: Bread → col 24 (index 23), duplicate to col 54 (index 53); Non-Bread → col 25 (index 24).
                // Week 4: Bread → col 28 (index 27), duplicate to col 55 (index 54); Non-Bread → col 29 (index 28).
                // Week 5: Bread → col 33 (index 32), duplicate to col 56 (index 55); Non-Bread → col 34 (index 33).

                // Week 1:
                $shouldEstimate = $shouldEstimateForWeek($proFormaData[$proRowIndex], $week1['start_date'], $week1['end_date']);


                if ($shouldEstimate) {
                    $proFormaData[$proRowIndex][15] = round(($matchingHistory[20] ?? 0) * $week1['days'], 2);
                    $proFormaData[$proRowIndex][51] = round(($matchingHistory[20] ?? 0) * $week1['days'], 2);
                    $proFormaData[$proRowIndex][16] = round(($matchingHistory[35] ?? 0) * $week1['days'], 2);

                }

                // Week 2:
                if ($week2 && $shouldEstimateForWeek($proFormaData[$proRowIndex], $week2['start_date'], $week2['end_date'])) {
                    $proFormaData[$proRowIndex][19] = round(($matchingHistory[20] ?? 0) * $week2['days'], 2);
                    $proFormaData[$proRowIndex][52] = round(($matchingHistory[20] ?? 0) * $week2['days'], 2);
                    $proFormaData[$proRowIndex][20] = round(($matchingHistory[35] ?? 0) * $week2['days'], 2);

                }

                // Week 3:
                if ($week3 && $shouldEstimateForWeek($proFormaData[$proRowIndex], $week3['start_date'], $week3['end_date'])) {
                    $proFormaData[$proRowIndex][23] = round(($matchingHistory[20] ?? 0) * $week3['days'], 2);
                    $proFormaData[$proRowIndex][53] = round(($matchingHistory[20] ?? 0) * $week3['days'], 2);
                    $proFormaData[$proRowIndex][24] = round(($matchingHistory[35] ?? 0) * $week3['days'], 2);
                }

                // Week 4:
                if ($week4 && $shouldEstimateForWeek($proFormaData[$proRowIndex], $week4['start_date'], $week4['end_date'])) {
                    $proFormaData[$proRowIndex][27] = round(($matchingHistory[20] ?? 0) * $week4['days'], 2);
                    $proFormaData[$proRowIndex][54] = round(($matchingHistory[20] ?? 0) * $week4['days'], 2);
                    $proFormaData[$proRowIndex][28] = round(($matchingHistory[35] ?? 0) * $week4['days'], 2);
                }

                // Week 5:
                if ($week5 && $week5['days'] > 0 && $shouldEstimateForWeek($proFormaData[$proRowIndex], $week5['start_date'], $week5['end_date'])) {
                    $proFormaData[$proRowIndex][32] = round(($matchingHistory[20] ?? 0) * $week5['days'], 2);
                    $proFormaData[$proRowIndex][55] = round(($matchingHistory[20] ?? 0) * $week5['days'], 2);
                    $proFormaData[$proRowIndex][33] = round(($matchingHistory[35] ?? 0) * $week5['days'], 2);
                }

                // $this->reApplyMnsrFormulaValueAtIndex($proFormaData, $iterationIndex + 1);
                $iterationIndex++;
            }

            // filter out rows where the first and second columns are empty
            $skippedRows = array_slice($proFormaData, 0, 7); // Extract first 7 rows (unchanged)
            $filteredRows = array_filter(array_slice($proFormaData, 7), function ($row) {
                $isEmpty = (empty($row[0]) || trim($row[0]) === '');
                return !$isEmpty;
            });

            // ensure each row has consecutive indexes and fill missing values with null
            $maxIndex = max(array_map('max', array_map('array_keys', $proFormaData)));

            $filteredRows = array_map(function ($row) use ($maxIndex) {
                return array_replace(array_fill(0, $maxIndex + 1, null), $row);
            }, $filteredRows);

            $this->sortProFormaData($filteredRows);
            $proFormaData = array_merge($skippedRows, $filteredRows); // Combine skipped and filtered data

            $this->populateFormaData($proFormaData, $franchiseesData);

            // Load template directly from local storage
            $templatePath = storage_path('app/private/royalty/cache/Z-ProForma-Natl-Monthly-Sales-by-Store.xlsx');
            if (!file_exists($templatePath)) {
                throw new RuntimeException('MNSR template not found: ' . $templatePath);
            }
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            // re-apply formulas to have the same format vs the macro generated files
            $proFormaData = $this->reApplyMnsrFormulas($proFormaData);

            // add totals row to a separate array
            $proFormaDataWithTotal = $this->addTotals($proFormaData);

            // instead of looping over each cell individually, write the entire array in one pass.
            $sheet->fromArray($proFormaDataWithTotal, null, 'A1');
            $sheet->getRowDimension(1)->setVisible(false);

            // fix date formats for columns Q, U, Y, AC, AH for rows 3 and 4:
            foreach (['H', 'Q', 'U', 'Y', 'AC', 'AH'] as $col) {
                for ($row = 3; $row <= 4; $row++) {
                    $cell = $sheet->getCell($col . $row);
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
                            $sheet->getStyle($col . $row)
                                ->getNumberFormat()
                                ->setFormatCode('m/d/yyyy h:mm');
                        } else {
                            $sheet->getStyle($col . $row)
                                ->getNumberFormat()
                                ->setFormatCode('d-MMM-yy');
                        }
                    }
                }
            }

            // Apply number formatting to columns BE and BF to show 0.000 format AND force values
            $highestRow = $sheet->getHighestRow();
            foreach (['BE', 'BF'] as $col) {
                for ($row = 8; $row <= $highestRow; $row++) {
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

            // fix date formats for columns D, E, BG, BH, BI for rows 8 and beyond:
            foreach (['D', 'E', 'BG', 'BH', 'BI'] as $col) {
                for ($row = 8; $row <= $highestRow; $row++) {
                    // Check if this row has actual data by looking at column A (cluster code) or column B (store code)
                    $clusterCode = $sheet->getCell('A' . $row)->getValue();
                    $storeCode = $sheet->getCell('B' . $row)->getValue();

                    // Skip rows that don't have branch data (except the TOTALS row)
                    if (empty($clusterCode) && empty($storeCode)) {
                        $totalLabel = $sheet->getCell('C' . $row)->getValue();
                        if ($totalLabel !== 'T O T A L S') {
                            continue; // Skip empty rows
                        }
                    }

                    $cell = $sheet->getCell($col . $row);
                    $value = $cell->getValue();

                    if ($col === 'D') {
                        // Special handling for column D - only if the row has data
                        if (!empty($clusterCode) || !empty($storeCode)) {
                            $this->writeExcelDateColumn($sheet, $col . $row, $value);
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
                            $sheet->getStyle($col . $row)
                                ->getNumberFormat()
                                ->setFormatCode('d-MMM-yy');
                        }
                    }
                }
            }

            // set default sheet name
            $sheet->setTitle('Adjust-0');
            $salesMonthAbbrev = Carbon::createFromFormat('m', $salesMonth)->format('M');

            // Create temporary directory for output
            $tempDir = sys_get_temp_dir() . '/royalty_mnsr_' . $batch_id . '_' . uniqid();
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $outputPath = $tempDir . '/Monthly-Natl-Sales-Rept-' . $salesMonthAbbrev . '-' . $salesYear . '.xlsx';

            $writer = new Xlsx($spreadsheet);
            $writer->save($outputPath);

            $macroOutputPath = $this->generateUploadBasePath() . '/royalty/generated/' . $batch_id . '/Monthly-Natl-Sales-Rept-' . $salesMonthAbbrev . '-' . $salesYear . '.xlsx';
            $macroCachedPath = $this->generateUploadBasePath() . '/royalty/generated/' . $batch_id . '/Cached-Monthly-Natl-Sales-Rept-' . $salesMonthAbbrev . '-' . $salesYear . '.json';
            $this->upload($outputPath, $macroOutputPath);

            // Upload cached JSON data with sheet structure (similar to CacheUploadedRoyaltyFilesJob)
            $mnsrData = ['Adjust-0' => $proFormaData];
            $tempCachedFile = $tempDir . '/cached_mnsr.json';
            file_put_contents($tempCachedFile, json_encode($mnsrData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            $this->upload($tempCachedFile, $macroCachedPath);

            // Clean up temporary files
            @unlink($outputPath);
            @unlink($tempCachedFile);
            @rmdir($tempDir);

            $macroOutput = new MacroOutput;
            $macroOutput->batch_id = $batch_id;
            $macroOutput->status = MacroBatchStatusEnum::Successful()->value;
            $macroOutput->file_name = 'Monthly-Natl-Sales-Rept-' . $salesMonthAbbrev . '-' . $salesYear . '.xlsx';
            $macroOutput->file_type_id = MacroFileTypeEnum::MNSR()->value;
            $macroOutput->file_revision_id = MacroFileRevisionEnum::MNSRDefault()->value;
            $macroOutput->file_path = $macroOutputPath;
            $macroOutput->cached_path = $macroCachedPath;
            $macroOutput->completed_at = now();
            $macroOutput->month = $salesMonth;
            $macroOutput->year = $salesYear;
            $macroOutput->save();

            DB::commit();

            dispatch(new AddFranchiseeDataToMnsrJob($batch_id));

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function addTotals($proFormaData)
    {
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
     * Sorts ProForma Data by 1st column [Cluster Code]
     *
     * @return void
     */
    private function sortProFormaData(&$proFormaData)
    {
        usort($proFormaData, function ($a, $b) {
            return strcmp(trim($a[0]), trim($b[0])); // trim to remove hidden spaces
        });
    }

    // NOTES :: old code -- for reference
    //    private function populateFormaData($proFormaData, $franchiseesData)
    //    {
    //        for ($i = 1; $i <= count($proFormaData) - 1; $i++) {
    //            if ($proFormaData[$i + 1][1] == '') {
    //                break;
    //            }
    //
    //            $proFranCode = $proFormaData[$i + 1][5];
    //            $franchiseFranCode = $franchiseesData[$i][8] ?? null;
    //
    //            if ($proFranCode == $franchiseFranCode) {
    //                $proFormaData[$i + 1][49] = $franchiseesData[$i][24] ?? null;
    //                $proFormaData[$i + 1][50] = $franchiseesData[$i][27] ?? null;
    //            }
    //        }
    //    }

    private function populateFormaData(&$proFormaData, $franchiseesData)
    {
        for ($i = 7; $i < count($proFormaData); $i++) {
            // if the branch row is empty, skip it.
            if (empty($proFormaData[$i][1])) {
                continue;
            }

            // get the franchisee code for this branch (assumed in column 6, index 5)
            $branchFranCode = $proFormaData[$i][5];

            // search through all franchisee rows for a matching code.
            foreach ($franchiseesData as $franchiseeRow) {
                $franchiseeCode = $franchiseeRow[8] ?? null; // franchisee code
                if ($branchFranCode == $franchiseeCode) {
                    // populate columns AX (index 49) and AY (index 50)
                    $proFormaData[$i][49] = $franchiseeRow[24] ?? null;
                    $proFormaData[$i][50] = $franchiseeRow[27] ?? null;
                    break;
                }
            }
        }
    }

    /**
     * Used to determine days per week and start/end dates per week
     * This method replicates the exact VBA logic for week calculations
     *
     * @return array
     */
    public function getFirstAndLastDaysOfEachWeek($salesMonth, $salesYear)
    {
        $startOfMonth = Carbon::createFromDate($salesYear, $salesMonth, 1)->setTime(0, 0, 0);
        $weeks = [];

        $firstDayOfWeek = $startOfMonth->dayOfWeekIso; // Monday = 1, Sunday = 7
        $daysInMonth = $startOfMonth->daysInMonth;

        // *********** Week 1 ************************* (VBA lines 164-174)
        $daysWk1 = 8 - $firstDayOfWeek;
        if ($daysWk1 < 2) {
            $daysWk1 += 7;
        }

        $startWk1 = $startOfMonth->copy();
        $endWk1 = $startWk1->copy()->addDays($daysWk1 - 1);

        $weeks[] = [
            'week' => 1,
            'days' => $daysWk1,
            'start_date' => $startWk1->format('Y-m-d'),
            'end_date' => $endWk1->format('Y-m-d'),
        ];

        // *********** Week 2 ************************* (VBA lines 176-182)
        $daysWk2 = 7;
        $startWk2 = $endWk1->copy()->addDay();
        $endWk2 = $endWk1->copy()->addDays($daysWk2);

        $weeks[] = [
            'week' => 2,
            'days' => $daysWk2,
            'start_date' => $startWk2->format('Y-m-d'),
            'end_date' => $endWk2->format('Y-m-d'),
        ];

        // *********** Week 3 ************************* (VBA lines 184-190)
        $daysWk3 = 7;
        $startWk3 = $endWk2->copy()->addDay();
        $endWk3 = $endWk2->copy()->addDays($daysWk3);

        $weeks[] = [
            'week' => 3,
            'days' => $daysWk3,
            'start_date' => $startWk3->format('Y-m-d'),
            'end_date' => $endWk3->format('Y-m-d'),
        ];

        // *********** Week 4 ************************* (VBA lines 192-205)
        $daysLeft = $daysInMonth - ($daysWk1 + $daysWk2 + $daysWk3);

        if ($daysLeft == 8) {
            $daysWk4 = 8;
        } else {
            $daysWk4 = 7;
        }

        $startWk4 = $endWk3->copy()->addDay();
        $endWk4 = $endWk3->copy()->addDays($daysWk4);

        $weeks[] = [
            'week' => 4,
            'days' => $daysWk4,
            'start_date' => $startWk4->format('Y-m-d'),
            'end_date' => $endWk4->format('Y-m-d'),
        ];

        // *********** Week 5 ************************* (VBA lines 207-219)
        $daysLeft = $daysInMonth - ($daysWk1 + $daysWk2 + $daysWk3 + $daysWk4);

        if ($daysLeft > 0) {
            $daysWk5 = $daysLeft;
            $startWk5 = $endWk4->copy()->addDay();
            $endWk5 = $endWk4->copy()->addDays($daysLeft);

            $weeks[] = [
                'week' => 5,
                'days' => $daysWk5,
                'start_date' => $startWk5->format('Y-m-d'),
                'end_date' => $endWk5->format('Y-m-d'),
            ];
        }

        return $weeks;
    }

    /**
     * Used for converting mapped columns read from Excel (as serial number) into dates
     * Must be accessed by index ($row[$index])
     * Will not work if used in foreach ($row as $data), $data[$index] will not work and will be read as serial if date
     *
     * @return void
     */
    public function convertSheetDatesFromSerialToDate($index, &$branchesData, &$branchHistoriesData, &$branchAddressesData, &$franchiseesData, &$salesHistoryByStoreData)
    {
        $branchesSheetDateConversionIndexes = [18, 19, 20, 23, 27, 28, 29, 30, 31, 32, 33, 35, 37, 38, 39, 41, 43, 45, 46];
        $branchHistoriesSheetDateConversionIndexes = [4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 16, 17, 18, 21, 22, 25, 26, 29, 30, 34];
        $branchAddressesSheetDateConversionIndexes = [76, 77, 78, 79, 81];
        $franchiseesSheetDateConversionIndexes = [33, 34, 35, 36, 37, 39, 40, 42, 43];
        $salesHistoryByStoreSheetDateConversionIndexes = [4, 5];

        foreach ($branchesSheetDateConversionIndexes as $dateConversionIndex) {
            if (isset($branchesData[$index][$dateConversionIndex])) {
                $serialNumber = $branchesData[$index][$dateConversionIndex];

                if ($serialNumber != null) {
                    // Skip conversion if already in YYYY-MM-DD format
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $serialNumber)) {
                        // Already in correct format, no conversion needed
                        continue;
                    }
                    $branchesData[$index][$dateConversionIndex] = $this->convertSerialToDate($serialNumber);
                }
            }
        }

        foreach ($branchHistoriesSheetDateConversionIndexes as $dateConversionIndex) {
            if (isset($branchHistoriesData[$index][$dateConversionIndex])) {
                $serialNumber = $branchHistoriesData[$index][$dateConversionIndex];
                if ($serialNumber != null) {
                    // Skip conversion if already in YYYY-MM-DD format
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $serialNumber)) {
                        // Already in correct format, no conversion needed
                        continue;
                    }
                    $branchHistoriesData[$index][$dateConversionIndex] = $this->convertSerialToDate($serialNumber);
                }
            }
        }

        foreach ($branchAddressesSheetDateConversionIndexes as $dateConversionIndex) {
            if (isset($branchAddressesData[$index][$dateConversionIndex])) {
                $serialNumber = $branchAddressesData[$index][$dateConversionIndex];

                if ($serialNumber != null) {
                    // Skip conversion if already in YYYY-MM-DD format
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $serialNumber)) {
                        // Already in correct format, no conversion needed
                        continue;
                    }
                    $branchAddressesData[$index][$dateConversionIndex] = $this->convertSerialToDate($serialNumber);
                }
            }
        }

        foreach ($franchiseesSheetDateConversionIndexes as $dateConversionIndex) {
            if (isset($franchiseesData[$index][$dateConversionIndex])) {
                $serialNumber = $franchiseesData[$index][$dateConversionIndex];

                if ($serialNumber != null) {
                    // Skip conversion if already in YYYY-MM-DD format
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $serialNumber)) {
                        // Already in correct format, no conversion needed
                        continue;
                    }
                    $franchiseesData[$index][$dateConversionIndex] = $this->convertSerialToDate($serialNumber);
                }
            }
        }

        foreach ($salesHistoryByStoreSheetDateConversionIndexes as $dateConversionIndex) {
            if (isset($salesHistoryByStoreData[$index][$dateConversionIndex])) {
                $serialNumber = $salesHistoryByStoreData[$index][$dateConversionIndex];

                if ($serialNumber != null) {
                    // Skip conversion if already in YYYY-MM-DD format
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $serialNumber)) {
                        // Already in correct format, no conversion needed
                        continue;
                    }
                    $salesHistoryByStoreData[$index][$dateConversionIndex] = $this->convertSerialToDate($serialNumber);
                }
            }
        }
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
}
