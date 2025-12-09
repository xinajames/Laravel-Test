<?php

namespace App\Services\Royalty;

use App\Enums\MacroBatchStatusEnum;
use App\Enums\MacroFileRevisionEnum;
use App\Enums\MacroFileTypeEnum;
use App\Models\Royalty\MacroBatch;
use App\Models\Royalty\MacroOutput;
use App\Models\SalesPerformance;
use App\Models\SalesPerformanceDetail;
use App\Traits\ErrorLogger;
use App\Traits\ManageFilesystems;
use App\Traits\VbaRounding;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use function count;

class MNSRServiceCopySalesByStore
{
    use ErrorLogger, ManageFilesystems, VbaRounding;

    protected const HISTORY_FILE = 'JBS-Sales-History-By-Store.xlsx';

    private const MAX_ROW_CHECK = 30_000;

    private array $monthMap = [
        'Jan' => 1, 'Feb' => 2, 'Mar' => 3, 'Apr' => 4,
        'May' => 5, 'Jun' => 6, 'Jul' => 7, 'Aug' => 8,
        'Sep' => 9, 'Oct' => 10, 'Nov' => 11, 'Dec' => 12,
    ];

    private array $monthNames = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun',
        7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
    ];

    private array $letterTable = [];

    public function processData($batchId, $salesPerformanceId = null): array
    {
        $this->buildLetterTable();

        // Get file paths from database
        $mnsrPath = $this->getMnsrPath($batchId);
        $salesHistoryPath = $this->getSalesHistoryPath($batchId);

        // Create temporary directory for processing
        $tempDir = sys_get_temp_dir().'/royalty_copy_sales_by_store_'.$batchId.'_'.uniqid();
        if (! file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Create temporary file paths
        $tempMnsrFile = $tempDir.'/'.basename($mnsrPath);
        $tempHistoryFile = $tempDir.'/'.basename($salesHistoryPath);

        // Download files from storage to local temp storage
        file_put_contents($tempMnsrFile, $this->readFile($mnsrPath));

        $salesHistoryContent = $this->readFile($salesHistoryPath);
        if ($salesHistoryContent === null) {
            throw new RuntimeException("Sales History by-store file does not exist: {$salesHistoryPath}");
        }
        file_put_contents($tempHistoryFile, $salesHistoryContent);

        // Get month/year from current batch
        $batch = MacroBatch::find($batchId);
        if (! $batch) {
            throw new Exception('MacroBatch not found for ID: '.$batchId);
        }

        $monthNo = $batch->month;        // 1-12
        $salesMon = $this->monthNames[$monthNo];  // Convert to month name
        $salesYr = (string) $batch->year;
        $leapYear = $this->isLeapYear($batch->year);

        /* load workbooks from temp files */
        $mnsrBook = IOFactory::load($tempMnsrFile);
        $histBook = IOFactory::load($tempHistoryFile);

        /* active Adjust-n sheet in MNSR */
        [$salesSheet, $adjustNbr] = $this->getLatestAdjustSheet($mnsrBook, $batchId);

        /* yearly sheet in history */
        $histSheet = ($monthNo === 1 && $adjustNbr === 0)
            ? $this->createNewYearSheet($histBook, (int) $salesYr, $batchId)
            : $this->openExistingYearSheet($histBook, (int) $salesYr, $batchId);

        /* zero-out this month's columns in history */
        $breadColCur = 15 + $monthNo;
        $nonBdColCur = 29 + $monthNo;
        $this->zeroMonthColumns($histSheet, $breadColCur, $nonBdColCur);

        /* match / insert clusters & post sales */
        $this->matchStores($salesSheet, $histSheet, $breadColCur, $nonBdColCur);

        /* Y-T-D totals & same-store flags */
        $lastDataRow = $this->computeYtdAndSameStoreFlags($histSheet, $breadColCur, $nonBdColCur);

        /* column totals row */
        $this->addColumnTotals($histSheet, $lastDataRow + 1, $lastDataRow);

        /* Last12Mos sheet sync */
        $l12mSheet = $histBook->getSheetByName('Last12Mos');
        if (! $l12mSheet) {
            throw new Exception('Sheet "Last12Mos" not found.');
        }
        $l12mSheet->getProtection()->setSheet(false);
        $this->syncLast12Months($salesSheet, $l12mSheet, $monthNo);

        /* mark Adjust-n sheet closed */
        $this->markSalesSheetClosed($salesSheet);

        /* if Adjust-0:  flag full-year rows + compute averages */
        if ($adjustNbr === 0) {
            $this->flagFullYearInLast12($l12mSheet);

            $days = $this->daysInMonths($leapYear);
            $this->computeMonthlyAverages($l12mSheet, $days);

            $estTable = $this->setupEstTableConstants($l12mSheet, $monthNo, $days);

            $this->estimateNextMonthSales($l12mSheet, $estTable);
        }

        /* Save processed files to temp location */
        IOFactory::createWriter($histBook, 'Xlsx')->save($tempHistoryFile);
        IOFactory::createWriter($mnsrBook, 'Xlsx')->save($tempMnsrFile);

        /* Create new file paths for processed files */
        $processedMnsrPath = $this->generateProcessedMnsrPath($batchId, $salesMon, $salesYr);
        $processedHistoryPath = $this->generateProcessedHistoryByStorePath($batchId, $salesMon, $salesYr);

        /* Upload processed files to new storage locations */
        $this->upload($tempMnsrFile, $processedMnsrPath);
        $this->upload($tempHistoryFile, $processedHistoryPath);

        /* Create MacroOutput records for processed files */
        $this->createProcessedMnsrOutput($batchId, $processedMnsrPath, $monthNo, (int) $salesYr);

        $this->processSalesPerformanceByStore($tempHistoryFile, $batchId, $monthNo, (int) $salesYr, $processedHistoryPath, $salesPerformanceId);

        /* Clean up temporary files */
        @unlink($tempMnsrFile);
        @unlink($tempHistoryFile);
        @rmdir($tempDir);

        /* Update macrobatch status to successful */
        $macroBatch = MacroBatch::find($batchId);
        $macroBatch->status = MacroBatchStatusEnum::Successful()->value;
        $macroBatch->completed_at = now();
        $macroBatch->save();

        return ['success' => true, 'message' => "Processed {$salesMon}-{$salesYr}"];
    }

    /**
     * Exactly the same logic as SalesPerformanceSeeder,
     * but keyed off your “By-Store” history file.
     */
    private function processSalesPerformanceByStore(string $historyFilePath, $batchId, int $month, int $year, string $processedHistoryPath, $salesPerformanceId = null): void
    {
        // Always read from the current updated Excel file (not old cache)
        $collection = Excel::toCollection([], $historyFilePath, null, \Maatwebsite\Excel\Excel::XLSX);

        // Get sheet names via PhpSpreadsheet
        $reader = IOFactory::createReaderForFile($historyFilePath);
        $reader->setReadDataOnly(true);
        $ss = $reader->load($historyFilePath);
        $names = $ss->getSheetNames();

        $salesHistorySheetsWithName = [];
        foreach ($collection as $i => $rows) {
            $title = $names[$i] ?? "Sheet{$i}";
            $salesHistorySheetsWithName[$title] = $rows;
        }

        // Use direct ID lookup if provided, otherwise fallback to the old lookup method
        if ($salesPerformanceId) {
            $salesPerformance = SalesPerformance::findOrFail($salesPerformanceId);
        } else {
            // Fallback to the old lookup method for backward compatibility
            $salesPerformance = SalesPerformance::whereNull('by_store_path')
                ->whereNull('by_store_cached_path')
                ->orderBy('created_at', 'desc')
                ->first();

            if (! $salesPerformance) {
                throw new Exception('No SalesPerformance found with empty by_store_path and by_store_cached_path.');
            }
        }

        $recorded_at = $salesPerformance->recorded_at ? \Carbon\Carbon::parse($salesPerformance->recorded_at) : now();

        $outputPath = $processedHistoryPath;
        $cachedPath = $this->generateUploadBasePath().'/jbs-sales-history-by-store/'.$salesPerformance->id.'/Cached-JBS-Sales-History-By-Store-'.$recorded_at->format('Y-m-d_H-i-s').'.json';

        // Upload cached data
        $this->uploadData(json_encode($salesHistorySheetsWithName), $cachedPath);

        $salesPerformance->by_store_path = $outputPath;
        $salesPerformance->by_store_cached_path = $cachedPath;
        $salesPerformance->save();

        // Create MacroOutput record
        MacroOutput::create([
            'batch_id' => $batchId,
            'file_name' => basename($outputPath),
            'file_type_id' => MacroFileTypeEnum::JBSSalesHistoryByStore()->value,
            'file_revision_id' => MacroFileRevisionEnum::JBSSalesHistoryByStoreDefault()->value,
            'month' => $month,
            'year' => $year,
            'file_path' => $outputPath,
            'cached_path' => $cachedPath,
            'completed_at' => $recorded_at,
        ]);

        // Process SalesPerformanceDetail records from by-store data
        $this->createSalesPerformanceDetails($salesHistorySheetsWithName, $salesPerformance->id);
    }

    /**
     * Create SalesPerformanceDetail records from by-store data using bulk inserts for better performance
     */
    private function createSalesPerformanceDetails($salesHistorySheetsWithName, $salesPerformanceId): void
    {
        $currentYear = now()->year;
        $endYear = $currentYear - 20;

        // Column mappings (0-based index) - same as seeder
        $breadColumns = [15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26]; // P to AA (Jan-Dec)
        $nonBreadColumns = [29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40]; // AD to AO (Jan-Dec)

        $batchSize = 125; // Process in chunks of 125 records (SQL Server parameter limit: 125 × 14 fields = 1750 parameters)
        $insertData = [];
        $currentBatchSize = 0;
        $recordedAt = now();

        // Process each year's data from by-store file
        for ($year = $currentYear; $year >= $endYear; $year--) {
            if (empty($salesHistorySheetsWithName[$year])) {
                continue;
            }

            foreach ($salesHistorySheetsWithName[$year] as $rowIndex => $row) {
                if ($rowIndex < 4) {
                    continue; // Skip header rows
                }

                // Extract store information from the row
                $clusterCode = $row[1] ?? '';
                $storeCode = $row[2] ?? '';
                $franchiseCode = $row[6] ?? '';
                $region = $row[8] ?? '';
                $area = $row[9] ?? '';
                $district = $row[10] ?? '';

                // Skip if essential data is missing
                if (empty($storeCode) || empty($region)) {
                    continue;
                }

                // Process each month for this store
                for ($monthIndex = 1; $monthIndex <= 12; $monthIndex++) {
                    $breadValue = floatval($row[$breadColumns[$monthIndex - 1]] ?? 0);
                    $nonBreadValue = floatval($row[$nonBreadColumns[$monthIndex - 1]] ?? 0);

                    // truncate to 4 decimal places
                    $breadValue = (float) number_format($breadValue, 4, '.', '');
                    $nonBreadValue = (float) number_format($nonBreadValue, 4, '.', '');
                    $combinedValue = $breadValue + $nonBreadValue;

                    $insertData[] = [
                        'sales_performance_id' => $salesPerformanceId,
                        'cluster_code' => $clusterCode,
                        'store_code' => $storeCode,
                        'franchise_code' => $franchiseCode,
                        'region' => $region,
                        'area' => $area,
                        'district' => $district,
                        'year' => $year,
                        'month' => $monthIndex,
                        'bread' => $breadValue,
                        'non_bread' => $nonBreadValue,
                        'combined' => $combinedValue,
                        'created_at' => $recordedAt,
                        'updated_at' => $recordedAt,
                    ];

                    $currentBatchSize++;

                    // Insert when batch is full
                    if ($currentBatchSize >= $batchSize) {
                        DB::table('sales_performance_details')->insert($insertData);
                        $insertData = [];
                        $currentBatchSize = 0;
                    }
                }
            }
        }

        // Insert remaining records
        if (! empty($insertData)) {
            DB::table('sales_performance_details')->insert($insertData);
        }
    }

    private function estimateNextMonthSales(Worksheet $l12, array $est): void
    {
        // Get national average daily sales from row 3
        $natlBread = (float) $l12->getCellByColumnAndRow(21, 3)->getCalculatedValue();
        $natlNon = (float) $l12->getCellByColumnAndRow(36, 3)->getCalculatedValue();

        // Loop through every branch row
        for ($row = 5; $row <= self::MAX_ROW_CHECK; $row++) {
            // Stop when column B blank
            if (trim((string) $l12->getCellByColumnAndRow(2, $row)->getValue()) === '') {
                break;
            }

            // ------------------------------------------------------------------
            //    Build arrays of bread and non-bread sales for three past months
            // ------------------------------------------------------------------
            $pastBread = [];
            $pastNon = [];

            for ($idx = 1; $idx <= 3; $idx++) {
                $mon = $est[$idx][1];           // month number
                $colBread = $mon + 8;           // Column for bread sales
                $colNonBread = $mon + 23;       // Column for non-bread sales

                // Get bread sales - if blank or 0, use projected/national average
                $breadValue = (float) $l12->getCellByColumnAndRow($colBread, $row)->getCalculatedValue();
                if ($breadValue <= 0) {
                    // Use projected sales (cell U - column 21) if no actual data
                    $pastBread[$idx] = (float) $l12->getCellByColumnAndRow(37, $row)->getCalculatedValue();
                } else {
                    $pastBread[$idx] = (float) $l12->getCellByColumnAndRow(21, $row)->getCalculatedValue();
                }

                // Get non-bread sales - if blank or 0, use projected/national average
                $nonBreadValue = (float) $l12->getCellByColumnAndRow($colNonBread, $row)->getCalculatedValue();
                if ($nonBreadValue <= 0) {
                    // Use projected sales (cell AK - column 36) if no actual data
                    $pastNon[$idx] = (float) $l12->getCellByColumnAndRow(38, $row)->getCalculatedValue();
                } else {
                    $pastNon[$idx] = (float) $l12->getCellByColumnAndRow(36, $row)->getCalculatedValue();
                }
            }

            // ------------------------------------------------------------------
            //     Apply monthly deviation corrections
            // ------------------------------------------------------------------
            $corrBread = [];
            $corrNon = [];

            for ($idx = 1; $idx <= 3; $idx++) {
                $corrBread[$idx] = $pastBread[$idx] / (1.0 + (float) $est[$idx][3]);
                $corrNon[$idx] = $pastNon[$idx] / (1.0 + (float) $est[$idx][4]);
            }

            // ------------------------------------------------------------------
            //    Weighted 3-month moving average, then correct for NEXT month
            // ------------------------------------------------------------------
            $sumProdBread = $corrBread[1] + ($corrBread[2] * 2) + ($corrBread[3] * 3);
            $nextBread = $this->vbaRound(($sumProdBread * (1.0 + (float) $est[4][3])) / 6.0, 2);

            $sumProdNon = $corrNon[1] + ($corrNon[2] * 2) + ($corrNon[3] * 3);
            $nextNon = $this->vbaRound(($sumProdNon * (1.0 + (float) $est[4][4])) / 6.0, 2);

            // Write to sheet
            $l12->setCellValueByColumnAndRow(21, $row, $nextBread);
            $l12->setCellValueByColumnAndRow(36, $row, $nextNon);
        }
    }

    /* ───────────────────────────────────────────────────────────────
       Build EstTable rows 1-4 and write the coming month’s short name
       into row 4 col 21 (Bread hdr) and col 36 (Non-bread hdr)
    ------------------------------------------------------------------ */
    private function setupEstTableConstants(
        Worksheet $l12,
        int $monthNo,     // 1-12 (MonthNbr in VBA)
        array $daysInMonth  // result of daysInMonths()
    ): array {
        // helper to roll months (Jan->Nov etc.) the same way VBA does
        $months = [
            ($monthNo + 10) % 12 ?: 12,          // month-2
            ($monthNo + 11) % 12 ?: 12,          // month-1
            $monthNo,                            // current
            ($monthNo % 12) + 1,                 // next month
        ];

        // 1-based EstTable[row][col] just like VBA
        $est = [];
        for ($row = 1; $row <= 4; $row++) {
            $m = $months[$row - 1];
            $est[$row][1] = $m;                      // month #
            $est[$row][2] = $daysInMonth[$m];        // days in that month

            // adjustment % bread  (header row 2, col = m+8)
            $est[$row][3] = (float) $l12->getCellByColumnAndRow($m + 8, 2)->getCalculatedValue();
            // adjustment % non-bread (header row 2, col = m+23)
            $est[$row][4] = (float) $l12->getCellByColumnAndRow($m + 23, 2)->getCalculatedValue();
        }

        // put new-month short name in row 4 headers (cols 21 and 36)
        $nextMonthName = $this->monthNames[$months[3]];
        $l12->setCellValueByColumnAndRow(21, 4, $nextMonthName);   // Bread sect hdr
        $l12->setCellValueByColumnAndRow(36, 4, $nextMonthName);   // Non-bread hdr

        return $est;   // will be used by the forthcoming estimation logic
    }

    private function daysInMonths(bool $leapYear): array
    {
        return [
            1 => 31, 2 => ($leapYear ? 29 : 28), 3 => 31, 4 => 30,
            5 => 31, 6 => 30, 7 => 31, 8 => 31, 9 => 30, 10 => 31, 11 => 30, 12 => 31,
        ];
    }

    private function computeMonthlyAverages(Worksheet $l12, array $days): void
    {
        $totBread = array_fill(1, 12, 0.0);
        $totNonBread = array_fill(1, 12, 0.0);
        $qualBreadRows = 0;
        $qualNonRows = 0;

        /* accumulate totals for rows flagged "Y" in column 8 / 23 */
        for ($row = 5; $row <= self::MAX_ROW_CHECK; $row++) {
            if (trim((string) $l12->getCellByColumnAndRow(2, $row)->getValue()) === '') {
                break;
            }
            if (strtoupper((string) $l12->getCellByColumnAndRow(8, $row)->getValue()) === 'Y') {
                for ($m = 1; $m <= 12; $m++) {
                    $totBread[$m] += (float) $l12->getCellByColumnAndRow($m + 8, $row)->getCalculatedValue();
                }
                $qualBreadRows++;
            }
            if (strtoupper((string) $l12->getCellByColumnAndRow(23, $row)->getValue()) === 'Y') {
                for ($m = 1; $m <= 12; $m++) {
                    $totNonBread[$m] += (float) $l12->getCellByColumnAndRow($m + 23, $row)->getCalculatedValue();
                }
                $qualNonRows++;
            }
        }

        $qualBreadRows = max($qualBreadRows, 1);   // avoid ÷0
        $qualNonRows = max($qualNonRows, 1);

        /* write avg-daily numbers into the sheet header (row 3) */
        for ($m = 1; $m <= 12; $m++) {
            $avgBread = ($totBread[$m] / $qualBreadRows) / $days[$m];
            $avgNon = ($totNonBread[$m] / $qualNonRows) / $days[$m];

            $l12->setCellValueByColumnAndRow($m + 8, 3, $avgBread);
            $l12->setCellValueByColumnAndRow($m + 23, 3, $avgNon);
        }
    }

    private function markSalesSheetClosed(Worksheet $sheet): void
    {
        // Column 5 == column E (for store-level, vs column 8/H for branch-level)
        $sheet->setCellValueByColumnAndRow(5, 2, 'T H E S E   A M O U N T S   W E R E');
        $sheet->setCellValueByColumnAndRow(5, 3, 'A D D E D   T O   S T O R E   H I S T   O N');
        $sheet->setCellValueByColumnAndRow(5, 4, now()->toDateTimeString());
        $sheet->setCellValueByColumnAndRow(5, 5, 'T H I S   S H E E T   I S   C L O S E D');

        // TODO: Re-protect (PhpSpreadsheet: just enable protection flag)
        // $sheet->getProtection()->setSheet(true)->setPassword(self::SALES_PASSWORD);
    }

    /* ───────────────────────────────────────────────────────────── */
    /*  flag clusters with 12 months of data */
    /* ───────────────────────────────────────────────────────────── */
    private function flagFullYearInLast12(Worksheet $l12): void
    {
        for ($row = 5; $row <= self::MAX_ROW_CHECK; $row++) {
            if (trim((string) $l12->getCellByColumnAndRow(2, $row)->getValue()) === '') {
                break; // reached end
            }

            // Bread months cols 9-20  → flag col 8
            $complete = true;
            for ($c = 9; $c <= 20; $c++) {
                $val = (float) $l12->getCellByColumnAndRow($c, $row)->getCalculatedValue();
                if ($val == 0.0) {
                    $complete = false;
                    break;
                }
            }
            $l12->setCellValueByColumnAndRow(8, $row, $complete ? 'Y' : 'N');

            // Non-Bread months cols 24-35 → flag col 23
            $complete = true;
            for ($c = 24; $c <= 35; $c++) {
                $val = (float) $l12->getCellByColumnAndRow($c, $row)->getCalculatedValue();
                if ($val == 0.0) {
                    $complete = false;
                    break;
                }
            }
            $l12->setCellValueByColumnAndRow(23, $row, $complete ? 'Y' : 'N');
        }
    }

    /* ───────────────────────────────────────────────────────────── */
    /*  small utility used a few times */
    /* ───────────────────────────────────────────────────────────── */
    private function zeroMonthColumns(Worksheet $sheet, int $breadCol, int $nonBdCol): void
    {
        for ($r = 5; $r <= self::MAX_ROW_CHECK; $r++) {
            if (trim((string) $sheet->getCellByColumnAndRow(2, $r)->getValue()) === '') {
                break;
            }
            $sheet->setCellValueByColumnAndRow($breadCol, $r, 0);
            $sheet->setCellValueByColumnAndRow($nonBdCol, $r, 0);
        }
    }

    protected function syncLast12Months(
        Worksheet $sales, Worksheet $l12, int $monthNo
    ): void {
        $breadCol = 8 + $monthNo;      // same maths as VBA: 8+N and 23+N
        $nonBdCol = 23 + $monthNo;

        // ❶ Wipe any previous numbers for that month
        $this->zeroMonthColumns($l12, $breadCol, $nonBdCol);

        $salesRow = 8;
        $l12Row = 5;

        /* ---- main merge loop ------------------------------------------------ */
        while (true) {
            $salesStore = trim((string) $sales->getCellByColumnAndRow(1, $salesRow)->getValue());
            if ($salesStore === '') {                // reached blank row in sales sheet
                break;
            }

            $salesStore = trim((string) $sales->getCellByColumnAndRow(2, $salesRow)->getValue());
            $histStore = trim((string) $l12->getCellByColumnAndRow(3, $l12Row)->getValue());

            /* ❷ CASE A – exact Store-code match → update & accumulate */
            if ($salesStore === $histStore) {
                $this->updateStoreLast12Existing(
                    $sales, $salesRow,
                    $l12, $l12Row,
                    $breadCol, $nonBdCol
                );
                $l12Row = 5;       // VBA resets to top
                $salesRow++;

                continue;
            }

            $histStore = trim((string) $l12->getCellByColumnAndRow(2, $l12Row)->getValue());

            /* ❸ CASE B – sheet exhausted OR salesStore should be inserted before histStore */
            if ($histStore === '') {
                $l12->insertNewRowBefore($l12Row, 1);
                $this->copyStoreToLast12(
                    $sales, $salesRow,
                    $l12, $l12Row,
                    $breadCol, $nonBdCol
                );
                $l12Row = 5;
                $salesRow++;

                continue;
            }

            /* ❹ CASE C – move down the Last12 sheet until we find match / insert point */
            $l12Row++;
        }
    }

    /** insert a fresh row into Last12Mos populated from sales sheet */
    private function copyStoreToLast12(
        Worksheet $src, int $srcRow,
        Worksheet $dst, int $dstRow,
        int $breadCol, int $nonBdCol
    ): void {
        /* dst cols 2-6  ←  src cols 1-5  (JBS-#, Store-code, Name, City, Province) */
        for ($i = 1; $i <= 5; $i++) {
            $dst->setCellValueByColumnAndRow(
                $i + 1, $dstRow,
                $src->getCellByColumnAndRow($i, $srcRow)->getCalculatedValue()
            );
        }

        $bread = $this->cell($src, 38, $srcRow) + $this->cell($src, 42, $srcRow);
        $non = $this->cell($src, 39, $srcRow) + $this->cell($src, 43, $srcRow);

        $dst->setCellValueByColumnAndRow($breadCol, $dstRow, $bread);
        $dst->setCellValueByColumnAndRow($nonBdCol, $dstRow, $non);

        /* projected daily sales → cols 37 & 38 (always written on insert) */
        $dst->setCellValueByColumnAndRow(37, $dstRow,
            $this->cell($src, 62, $srcRow) / 30);
        $dst->setCellValueByColumnAndRow(38, $dstRow,
            $this->cell($src, 63, $srcRow) / 30);
    }

    /** update existing row in Last12Mos (cols C..F and accumulate sales) */
    private function updateStoreLast12Existing(
        Worksheet $src, int $srcRow,
        Worksheet $dst, int $dstRow,
        int $breadCol, int $nonBdCol
    ): void {
        /* dst col 2  (JBS-cluster)  ←  src col 1 */
        $dst->setCellValueByColumnAndRow(
            2, $dstRow,
            $src->getCellByColumnAndRow(1, $srcRow)->getValue()
        );

        /* dst cols 4-6  ←  src cols 3-5  (Name, City, Province) */
        for ($dstCol = 4; $dstCol <= 6; $dstCol++) {
            $srcCol = $dstCol - 1;
            $dst->setCellValueByColumnAndRow(
                $dstCol, $dstRow,
                $src->getCellByColumnAndRow($srcCol, $srcRow)->getValue()
            );
        }

        /* accumulate current-month sales */
        $addBread = $this->cell($src, 38, $srcRow) + $this->cell($src, 42, $srcRow);
        $addNon = $this->cell($src, 39, $srcRow) + $this->cell($src, 43, $srcRow);

        $dst->setCellValueByColumnAndRow(
            $breadCol, $dstRow,
            $this->cell($dst, $breadCol, $dstRow) + $addBread
        );
        $dst->setCellValueByColumnAndRow(
            $nonBdCol, $dstRow,
            $this->cell($dst, $nonBdCol, $dstRow) + $addNon
        );

        /* write projected daily columns 37/38 only if they’re still blank */
        if (trim((string) $dst->getCellByColumnAndRow(37, $dstRow)->getValue()) === '') {
            $dst->setCellValueByColumnAndRow(37, $dstRow,
                $this->cell($src, 62, $srcRow) / 30);
        }
        if (trim((string) $dst->getCellByColumnAndRow(38, $dstRow)->getValue()) === '') {
            $dst->setCellValueByColumnAndRow(38, $dstRow,
                $this->cell($src, 63, $srcRow) / 30);
        }
    }

    private function cell(Worksheet $sh, int $col, int $row): float
    {
        return (float) $sh->getCellByColumnAndRow($col, $row)->getCalculatedValue();
    }

    // ───────────────────────── AddColTotals ─────────────────────────
    private function addColumnTotals(
        Worksheet $hist,
        int $totalsRow,    // where the Σ formulas will be written
        int $lastDataRow   // last row that contains data
    ): void {
        // ! Note: should be 27 (AA) and 41 (AO)
        $finalBreadCol = 18;
        $finalNonBdCol = 32;

        // ---- Bread columns (15 → 18) ----
        for ($col = 15; $col <= $finalBreadCol; $col++) {
            $colLetter = $this->letterTable[$col] ?? null;
            if ($colLetter === null) {
                continue;   // safety – we only built table up to AO
            }
            $range = "{$colLetter}5:{$colLetter}{$lastDataRow}";
            $hist->getCellByColumnAndRow($col, $totalsRow)
                ->setValue("=SUM({$range})");
        }

        // ---- Non-bread columns (29 → 32) ----
        for ($col = 29; $col <= $finalNonBdCol; $col++) {
            $colLetter = $this->letterTable[$col] ?? null;
            if ($colLetter === null) {
                continue;
            }
            $range = "{$colLetter}5:{$colLetter}{$lastDataRow}";
            $hist->getCellByColumnAndRow($col, $totalsRow)
                ->setValue("=SUM({$range})");
        }
    }

    // ──────────────────────────────────────────────────────────────────────────────
    // matchStores (VBA port)
    // ──────────────────────────────────────────────────────────────────────────────
    protected function matchStores(
        Worksheet $sales,
        Worksheet $hist,
        int $breadCol,
        int $nonBdCol
    ): void {
        $salesRow = 8;   // first data row in Monthly Sales
        $histRow = 5;   // first data row in History

        while (true) {
            $salesJbs = trim((string) $sales->getCellByColumnAndRow(1, $salesRow)->getValue());
            if ($salesJbs === '') {                      // === VBA “If SalesSheet.Cells(...) = ""”
                break;                                  // DoneMatching
            }

            $histJbs = trim((string) $hist->getCellByColumnAndRow(2, $histRow)->getValue());

            /* ----------------------------------------------------------
               CASE A  –  same JBS number → check store code
               ---------------------------------------------------------- */
            if ($salesJbs === $histJbs) {
                $salesStore = trim((string) $sales->getCellByColumnAndRow(2, $salesRow)->getValue());
                $histStore = trim((string) $hist->getCellByColumnAndRow(3, $histRow)->getValue());

                if ($salesStore === $histStore) {
                    $this->updateExistingStoreRow(
                        $sales, $salesRow,
                        $hist, $histRow,
                        $breadCol, $nonBdCol
                    );
                    $salesRow++;
                    $histRow = 5;

                    continue;
                }

                /* A-2  same cluster but different store → move down History */
                $histRow++;

                continue;
            }

            /* ----------------------------------------------------------
               CASE B  –  History exhausted OR sales cluster < hist cluster
                          → insert new store row
               ---------------------------------------------------------- */
            if ($histJbs === '' || $salesJbs < $histJbs) {
                $hist->insertNewRowBefore($histRow, 1);
                $this->copyStoreRow(
                    $sales, $salesRow,
                    $hist, $histRow,
                    $breadCol, $nonBdCol
                );
                $salesRow++;
                $histRow = 5;

                continue;
            }

            $histRow++;
        }
    }

    /** clone the VBA “insert new cluster” payload (columns 2-12 + values) */
    private function copyStoreRow(
        Worksheet $src, int $srcRow,
        Worksheet $dst, int $dstRow,
        int $breadCol, int $nonBdCol
    ): void {
        /* src cols 1-11  →  dst cols 2-12 (same as VBA) */
        for ($i = 1; $i <= 11; $i++) {
            $dst->setCellValueByColumnAndRow(
                $i + 1, $dstRow,
                $src->getCellByColumnAndRow($i, $srcRow)->getValue()
            );
        }

        $bread = $this->cell($src, 38, $srcRow) + $this->cell($src, 42, $srcRow);
        $non = $this->cell($src, 39, $srcRow) + $this->cell($src, 43, $srcRow);

        $dst->setCellValueByColumnAndRow($breadCol, $dstRow, $bread);
        $dst->setCellValueByColumnAndRow($nonBdCol, $dstRow, $non);
    }

    /** update existing row (columns 4-12) and accumulate sales */
    private function updateExistingStoreRow(
        Worksheet $src, int $srcRow,
        Worksheet $dst, int $dstRow,
        int $breadCol, int $nonBdCol
    ): void {
        /* dst cols 4-12  ←  src cols 3-11   (keep JBS & Store code) */
        for ($dstCol = 4; $dstCol <= 12; $dstCol++) {
            $srcCol = $dstCol - 1;
            $dst->setCellValueByColumnAndRow(
                $dstCol, $dstRow,
                $src->getCellByColumnAndRow($srcCol, $srcRow)->getValue()
            );
        }

        $dst->setCellValueByColumnAndRow(
            $breadCol, $dstRow,
            $this->cell($dst, $breadCol, $dstRow) +
            $this->cell($src, 38, $srcRow) + $this->cell($src, 42, $srcRow)
        );
        $dst->setCellValueByColumnAndRow(
            $nonBdCol, $dstRow,
            $this->cell($dst, $nonBdCol, $dstRow) +
            $this->cell($src, 39, $srcRow) + $this->cell($src, 43, $srcRow)
        );
    }

    // ──────────────────────────────────────────────────────────────────────────────
    // 1️⃣/2️⃣ helpers (unchanged from earlier versions)
    // ──────────────────────────────────────────────────────────────────────────────
    private function computeYtdAndSameStoreFlags(
        Worksheet $hist,
        int $breadCol,   // current month’s posting col
        int $nonBdCol
    ): int {
        for ($row = 5; $row <= self::MAX_ROW_CHECK; $row++) {
            if (trim((string) $hist->getCellByColumnAndRow(2, $row)->getValue()) === '') {
                return $row - 1;                 // LastHistRow in VBA
            }

            $ytdBread = 0.0;
            $ytdNonBd = 0.0;

            // Assume qualified until proven otherwise
            $hist->getCellByColumnAndRow(14, $row)->setValue('Y');
            $hist->getCellByColumnAndRow(28, $row)->setValue('Y');

            // Bread Y-T-D  (cols 16 .. current BreadCol)
            for ($c = 16; $c <= $breadCol; $c++) {
                $raw = $hist->getCellByColumnAndRow($c, $row)->getValue();

                // if it isn’t a number (blank, text, or a formula string), treat as zero
                $val = is_numeric($raw) ? (float) $raw : 0.0;

                // flag branch as NOT same-store if any month is zero
                if ($val == 0.0) {
                    $hist->setCellValueByColumnAndRow(14, $row, 'N');
                }

                // accumulate the year-to-date total
                $ytdBread += $val;
            }
            $hist->getCellByColumnAndRow(15, $row)->setValue($ytdBread);     // put total

            // Non-Bread Y-T-D (cols 30 .. current NonBdCol)
            for ($c = 30; $c <= $nonBdCol; $c++) {

                $raw = $hist->getCellByColumnAndRow($c, $row)->getValue();

                $val = is_numeric($raw) ? (float) $raw : 0.0;

                // flag as not same-store if any month is zero
                if ($val == 0.0) {
                    $hist->setCellValueByColumnAndRow(28, $row, 'N');
                }

                $ytdNonBd += $val;
            }
            $hist->getCellByColumnAndRow(29, $row)->setValue($ytdNonBd);
        }

        return self::MAX_ROW_CHECK;  // should not happen
    }

    /** @return array{Worksheet,int} */
    private function getLatestAdjustSheet(Spreadsheet $book, int $batchId): array
    {
        // Log all available sheet names for debugging
        $allSheets = $book->getSheetNames();

        $n = 0;
        while (true) {
            $tab = "Adjust-{$n}";

            $ws = $book->getSheetByName($tab);
            if (! $ws) {
                // No more sheets exist - need to create a new one or use the last available
                if ($n === 0) {
                    // No Adjust sheets exist at all
                    $this->logErrorToMacroBatch($batchId, 'No Adjust sheets found in workbook. Available sheets: '.implode(', ', $allSheets), 'getLatestAdjustSheet');
                    throw new Exception('No Adjust sheets found in workbook.');
                }

                // All existing sheets are closed - this should not happen in normal flow
                // but we'll throw a descriptive error instead of looking for non-existent sheets
                $this->logErrorToMacroBatch($batchId, 'All Adjust sheets (0 to '.($n - 1).') are closed. Available sheets: '.implode(', ', $allSheets), 'getLatestAdjustSheet');
                throw new Exception('All Adjust sheets are closed, cannot find an open sheet to process.');
            }

            $flag = trim((string) $ws->getCellByColumnAndRow(5, 5)->getCalculatedValue());

            if ($flag === 'T H I S   S H E E T   I S   C L O S E D') {
                $n++;

                continue;
            }

            $ws->getProtection()->setSheet(false);

            return [$ws, $n];
        }
    }

    private function createNewYearSheet(Spreadsheet $book, int $year, int $batchId): Worksheet
    {
        $tpl = $book->getSheetByName((string) ($year - 1));
        if (! $tpl) {
            throw new Exception('Missing template sheet for '.($year - 1));
        }
        $clone = clone $tpl;
        $book->addSheet($clone, $book->getIndex($tpl));
        $clone->setTitle((string) $year);
        $clone->getProtection()->setSheet(false);

        for ($row = 5; $row <= self::MAX_ROW_CHECK; $row++) {
            if (trim((string) $clone->getCellByColumnAndRow(2, $row)->getValue()) === '') {
                break;
            }
            for ($col = 14; $col <= 41; $col++) {
                $clone->getCellByColumnAndRow($col, $row)->setValue(null);
            }
        }

        return $clone;
    }

    private function openExistingYearSheet(Spreadsheet $book, int $year, int $batchId): Worksheet
    {
        $ws = $book->getSheetByName((string) $year);
        if (! $ws) {
            throw new Exception('Year sheet '.$year.' not found.');
        }
        $ws->getProtection()->setSheet(false);

        return $ws;
    }

    // ──────────────────────────────────────────────────────────────────────────────
    // Utility
    // ──────────────────────────────────────────────────────────────────────────────

    private function getMnsrPath(int $batchId): string
    {
        // Look for MNSR file within the same batch (created by Branch service)
        $macroOutput = MacroOutput::where('file_type_id', MacroFileTypeEnum::MNSR()->value)
            ->where('batch_id', $batchId)
            ->orderByDesc('created_at')
            ->first();

        if (! $macroOutput) {
            $this->logErrorToMacroBatch($batchId, 'No MNSR file found within current batch ID: '.$batchId, 'getMnsrPath');
            throw new Exception('No MNSR file found within the current batch. Branch service should run first.');
        }

        // Log if file exists in storage
        if ($this->fileExists($macroOutput->file_path)) {
        } else {
            $this->logErrorToMacroBatch($batchId, 'MNSR file does not exist in storage: '.$macroOutput->file_path, 'getMnsrPath');
        }

        return $macroOutput->file_path;
    }

    private function getMnsrRevisionType(int $batchId): int
    {
        $macroOutput = MacroOutput::where('file_type_id', MacroFileTypeEnum::MNSR()->value)
            ->where('batch_id', $batchId)
            ->orderByDesc('created_at')
            ->first();

        return $macroOutput ? $macroOutput->file_revision_id : MacroFileRevisionEnum::MNSRDefault()->value;
    }

    private function getSalesHistoryPath(int $batchId): string
    {
        // Get the latest Sales History file with defined by_store_path
        $salesPerformance = SalesPerformance::orderBy('created_at', 'desc')
            ->whereNotNull('by_store_path')
            ->whereNotNull('by_store_cached_path')
            ->first();

        if (! $salesPerformance) {
            $this->logErrorToMacroBatch($batchId, 'No Sales History by-store file found for batch ID: '.$batchId, 'getSalesHistoryPath');
            throw new Exception("No Sales History file found for batch {$batchId}.");
        }

        $path = $salesPerformance->by_store_path;

        // Log if file exists in storage
        if ($this->fileExists($path)) {
        } else {
            $this->logErrorToMacroBatch($batchId, 'Sales History by-store file does not exist in storage: '.$path, 'getSalesHistoryPath');
        }

        return $path;
    }

    /** @return array{string,string} */
    private function parseMonthYear(string $file, int $batchId): array
    {
        if (! preg_match('/Rept-([A-Za-z]{3})-(\d{4})\.xlsx$/', $file, $m)) {
            throw new InvalidArgumentException("Bad MNSR filename {$file}");
        }

        return [$m[1], $m[2]];
    }

    private function buildLetterTable(): void
    {
        $this->letterTable = [0 => ''];
        foreach (range('A', 'Z') as $c) {
            $this->letterTable[] = $c;
        }
        foreach (range('A', 'Z') as $c1) {
            foreach (range('A', 'Z') as $c2) {
                $this->letterTable[] = $c1.$c2;
                if (count($this->letterTable) > 45) {
                    break 2;
                }
            }
        }
    }

    private function isLeapYear(int $year): bool
    {
        return ($year % 4 === 0 && $year % 100 !== 0) || ($year % 400 === 0);
    }

    private function generateProcessedMnsrPath(int $batchId, string $month, string $year): string
    {
        $fileName = "{$batchId}-Monthly-Natl-Sales-Rept-{$month}-{$year}.xlsx";

        return $this->generateUploadBasePath()."/royalty/generated/{$batchId}/{$fileName}";
    }

    private function generateProcessedHistoryByStorePath(int $batchId, string $month, string $year): string
    {
        $fileName = 'JBS-Sales-History-By-Store-'.now()->format('Y-m-d_H-i-s').'.xlsx';

        return $this->generateUploadBasePath()."/royalty/generated/{$batchId}/{$fileName}";
    }

    private function createProcessedMnsrOutput(int $batchId, string $filePath, int $month, int $year): void
    {
        // Generate filename without batch ID prefix for database storage
        $salesMon = $this->monthNames[$month];
        $cleanFileName = "Monthly-Natl-Sales-Rept-{$salesMon}-{$year}.xlsx";

        MacroOutput::create([
            'batch_id' => $batchId,
            'file_name' => $cleanFileName,
            'file_type_id' => MacroFileTypeEnum::MNSR()->value,
            'file_revision_id' => $this->getMnsrRevisionType($batchId),
            'month' => $month,
            'year' => $year,
            'file_path' => $filePath,
            'completed_at' => now(),
        ]);

    }
}
