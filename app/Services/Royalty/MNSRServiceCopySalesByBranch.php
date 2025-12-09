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
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;
use function count;

class MNSRServiceCopySalesByBranch
{
    use ErrorLogger, ManageFilesystems, VbaRounding;

    private const HISTORY_FILE = 'JBS-Sales-History.xlsx';

    private const MAX_ROW_CHECK = 2_500;

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

    public function processData($batchId): array
    {
        $this->buildLetterTable();

        // Get file paths from database
        $mnsrPath = $this->getMnsrPath($batchId);
        $salesHistoryPath = $this->getSalesHistoryPath($batchId);

        // Create temporary directory for processing
        $tempDir = sys_get_temp_dir() . '/royalty_copy_sales_' . $batchId . '_' . uniqid();
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Create temporary file paths
        $tempMnsrFile = $tempDir . '/' . basename($mnsrPath);
        $tempHistoryFile = $tempDir . '/' . basename($salesHistoryPath);

        // Download files from storage to local temp storage
        file_put_contents($tempMnsrFile, $this->readFile($mnsrPath));

        $salesHistoryContent = $this->readFile($salesHistoryPath);
        if ($salesHistoryContent === null) {
            throw new RuntimeException("Sales History file does not exist: {$salesHistoryPath}");
        }
        file_put_contents($tempHistoryFile, $salesHistoryContent);


        // Get month/year from current batch
        $batch = MacroBatch::find($batchId);
        if (!$batch) {
            throw new Exception('MacroBatch not found for ID: ' . $batchId);
        }

        $monthNo = $batch->month;        // 1-12
        $salesMon = $this->monthNames[$monthNo];  // Convert to month name
        $salesYr = (string)$batch->year;
        $leapYear = $this->isLeapYear($batch->year);

        /* load workbooks from temp files */
        $mnsrBook = IOFactory::load($tempMnsrFile);

        $histBook = IOFactory::load($tempHistoryFile);

        /* active Adjust-n sheet in MNSR */
        [$salesSheet, $adjustNbr] = $this->getLatestAdjustSheet($mnsrBook, $batchId);

        /* yearly sheet in history */
        $histSheet = ($monthNo === 1 && $adjustNbr === 0)
            ? $this->createNewYearSheet($histBook, (int)$salesYr, $batchId)
            : $this->openExistingYearSheet($histBook, (int)$salesYr, $batchId);

        /* zero-out this month's columns in history */
        $breadColCur = 15 + $monthNo;
        $nonBdColCur = 29 + $monthNo;
        $this->zeroMonthColumns($histSheet, $breadColCur, $nonBdColCur);

        /* match / insert clusters & post sales */
        $this->matchClusters($salesSheet, $histSheet, $breadColCur, $nonBdColCur);

        /* Y-T-D totals & same-store flags */
        $lastDataRow = $this->computeYtdAndSameStoreFlags($histSheet, $breadColCur, $nonBdColCur);

        /* column totals row */
        $this->addColumnTotals($histSheet, $lastDataRow + 1, $lastDataRow);

        /* Last12Mos sheet sync */
        $l12mSheet = $histBook->getSheetByName('Last12Mos');
        if (!$l12mSheet) {
            throw new Exception('Sheet "Last12Mos" not found.');
        }
        $l12mSheet->getProtection()->setSheet(false);
        $this->syncLast12Months($salesSheet, $l12mSheet, $monthNo);

        /* mark Adjust-n sheet closed */
        $this->markSalesSheetClosed($salesSheet);

        /* if Adjust-0:  flag full-year rows + compute averages */
        if ($adjustNbr === 0) {
            $this->flagFullYearInLast12($l12mSheet);

            $days = $this->daysInMonths($leapYear);      // helper already defined
            $this->computeMonthlyAverages($l12mSheet, $days);

            $estTable = $this->setupEstTableConstants($l12mSheet, $monthNo, $days);

            $this->estimateNextMonthSales($l12mSheet, $estTable);
        }

        /* Save processed files to temp location */
        IOFactory::createWriter($histBook, 'Xlsx')->save($tempHistoryFile);
        IOFactory::createWriter($mnsrBook, 'Xlsx')->save($tempMnsrFile);

        /* Create new file paths for processed files */
        $processedMnsrPath = $this->generateProcessedMnsrPath($batchId, $salesMon, $salesYr);
        $processedHistoryPath = $this->generateProcessedHistoryPath($batchId, $salesMon, $salesYr);


        /* Upload processed files to new storage locations */
        $this->upload($tempMnsrFile, $processedMnsrPath);
        $this->upload($tempHistoryFile, $processedHistoryPath);

        /* Create MacroOutput records for processed files */
        $this->createProcessedMnsrOutput($batchId, $processedMnsrPath, $monthNo, (int)$salesYr);

        $salesPerformance = $this->processSalesPerformance($tempHistoryFile, $batchId, $monthNo, (int)$salesYr, $processedHistoryPath);

        /* Clean up temporary files */
        @unlink($tempMnsrFile);
        @unlink($tempHistoryFile);
        @rmdir($tempDir);


        return ['success' => true, 'message' => "Processed {$salesMon}-{$salesYr}", 'sales_performance_id' => $salesPerformance->id];
    }

    private function processSalesPerformance(string $historyFilePath, $batchId, int $month, int $year, string $processedHistoryPath): SalesPerformance
    {
        // Check if cached data exists on storage
        $cachedDataPath = $this->generateUploadBasePath() . '/fixed-caches/jbs-sales-history-data.json';
        if ($this->fileExists($cachedDataPath)) {
            $cachedFileContent = $this->readFile($cachedDataPath);
            if ($cachedFileContent === null) {
                throw new RuntimeException("Sales History cached data file does not exist: {$cachedDataPath}");
            }
            $salesHistorySheetsWithName = json_decode($cachedFileContent, true);
        } else {
            // read all sheets into a Collection
            $collection = Excel::toCollection([], $historyFilePath, null, \Maatwebsite\Excel\Excel::XLSX);
            // get sheet names via PhpSpreadsheet
            $reader = IOFactory::createReaderForFile($historyFilePath);
            $reader->setReadDataOnly(true);
            $ss = $reader->load($historyFilePath);
            $names = $ss->getSheetNames();

            $salesHistorySheetsWithName = [];
            foreach ($collection as $i => $rows) {
                $title = $names[$i] ?? "Sheet{$i}";
                $salesHistorySheetsWithName[$title] = $rows;
            }
            // Upload cached data to storage
            $this->uploadData(json_encode($salesHistorySheetsWithName), $cachedDataPath);
        }

        // Variables removed as SalesPerformanceDetail processing moved to SalesPerformanceSeeder

        $recorded_at = now();
        $salesPerformance = new SalesPerformance;
        $salesPerformance->recorded_at = $recorded_at;
        $salesPerformance->save();

        // Use the processed history file path that was already uploaded
        $outputPath = $processedHistoryPath;
        $cachedPath = $this->generateUploadBasePath() . '/jbs-sales-history/' . $salesPerformance->id . '/Cached-JBS-Sales-History-' . $recorded_at->format('Y-m-d_H-i-s') . '.json';

        // No need to upload history file again as it's already uploaded to processedHistoryPath

        // Upload cached data
        $this->uploadData(json_encode($salesHistorySheetsWithName), $cachedPath);

        $salesPerformance->path = $outputPath;
        $salesPerformance->cached_path = $cachedPath;
        $salesPerformance->save();

        // Create MacroOutput record
        MacroOutput::create([
            'batch_id' => $batchId,
            'file_name' => basename($outputPath),
            'file_type_id' => MacroFileTypeEnum::JBSSalesHistory()->value,
            'file_revision_id' => MacroFileRevisionEnum::JBSSalesHistoryDefault()->value,
            'month' => $month,
            'year' => $year,
            'file_path' => $outputPath,
            'cached_path' => $cachedPath,
            'completed_at' => $recorded_at,
        ]);

        // Note: SalesPerformanceDetail processing is handled by SalesPerformanceSeeder
        // This service only handles main file caching and SalesPerformance record creation
        
        return $salesPerformance;
    }

    private function estimateNextMonthSales(Worksheet $l12, array $est): void
    {
        $natlBread = (float)$l12->getCellByColumnAndRow(21, 3)->getCalculatedValue(); // header row 3
        $natlNon = (float)$l12->getCellByColumnAndRow(36, 3)->getCalculatedValue();

        // loop through every branch row
        for ($row = 5; $row <= self::MAX_ROW_CHECK; $row++) {

            // stop when column B blank
            if (trim((string)$l12->getCellByColumnAndRow(2, $row)->getValue()) === '') {
                break;
            }

            // ------------------------------------------------------------------
            //    build arrays of DAILY amounts for the three past months
            //    (substitute national average when blank or 0)
            // ------------------------------------------------------------------
            $pastBread = [];   // EstTable(col 5)
            $pastNon = [];   // EstTable(col 6)

            for ($idx = 1; $idx <= 3; $idx++) {
                $mon = $est[$idx][1];           // month number
                $daysInMo = $est[$idx][2];

                // ---- Bread ----
                $cellVal = (float)$l12->getCellByColumnAndRow($mon + 8, $row)->getCalculatedValue();
                $pastBread[$idx] = ($cellVal <= 0)
                    ? $natlBread
                    : $cellVal / $daysInMo;

                // ---- Non-bread ----
                $cellVal = (float)$l12->getCellByColumnAndRow($mon + 23, $row)->getCalculatedValue();
                $pastNon[$idx] = ($cellVal <= 0)
                    ? $natlNon
                    : $cellVal / $daysInMo;
            }

            // ------------------------------------------------------------------
            //     apply monthly deviation corrections
            //     Corr = daily / (1 + adjustment%)
            // ------------------------------------------------------------------
            $corrBread = [];
            $corrNon = [];
            for ($idx = 1; $idx <= 3; $idx++) {
                $corrBread[$idx] = $pastBread[$idx] /
                    (1.0 + (float)$est[$idx][3]);
                $corrNon[$idx] = $pastNon[$idx] /
                    (1.0 + (float)$est[$idx][4]);
            }

            // ------------------------------------------------------------------
            //    weighted 3-month moving average, then correct for NEXT month
            // ------------------------------------------------------------------
            $weightedBread = ($corrBread[1] + 2 * $corrBread[2] + 3 * $corrBread[3]) / 6.0;
            $weightedNon = ($corrNon[1] + 2 * $corrNon[2] + 3 * $corrNon[3]) / 6.0;

            $nextBread = $this->vbaRound($weightedBread *
                (1.0 + (float)$est[4][3]), 2);
            $nextNon = $this->vbaRound($weightedNon *
                (1.0 + (float)$est[4][4]), 2);

            // write to sheet (col 21 Bread, col 36 Non-bread)
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
        int       $monthNo,     // 1-12 (MonthNbr in VBA)
        array     $daysInMonth  // result of daysInMonths()
    ): array
    {
        // helper to roll months (Jan->Nov etc.) the same way VBA does
        $months = [
            ($monthNo + 10) % 12 ?: 12,          // month-2
            ($monthNo + 11) % 12 ?: 12,          // month-1
            $monthNo,                            // current
            ($monthNo % 12) + 1,                 // next month
        ];

        // 1-based EstTable[row][col] just like VBA
        $est = [];
        foreach ($months as $idx => $m) {
            $row = $idx + 1;         // rows 1-4
            $est[$row][1] = $m;                      // month #
            $est[$row][2] = $daysInMonth[$m];        // days in that month

            // adjustment % bread  (header row 2, col = m+8)
            $est[$row][3] = (float)$l12->getCellByColumnAndRow($m + 8, 2)->getCalculatedValue();
            // adjustment % non-bread (header row 2, col = m+23)
            $est[$row][4] = (float)$l12->getCellByColumnAndRow($m + 23, 2)->getCalculatedValue();
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

        /* accumulate totals for rows flagged “Y” in column 8 / 23 */
        for ($row = 5; $row <= self::MAX_ROW_CHECK; $row++) {
            if (trim((string)$l12->getCellByColumnAndRow(2, $row)->getValue()) === '') {
                break;
            }
            if (strtoupper((string)$l12->getCellByColumnAndRow(8, $row)->getValue()) === 'Y') {
                for ($m = 1; $m <= 12; $m++) {
                    $totBread[$m] += (float)$l12->getCellByColumnAndRow($m + 8, $row)->getCalculatedValue();
                }
                $qualBreadRows++;
            }
            if (strtoupper((string)$l12->getCellByColumnAndRow(23, $row)->getValue()) === 'Y') {
                for ($m = 1; $m <= 12; $m++) {
                    $totNonBread[$m] += (float)$l12->getCellByColumnAndRow($m + 23, $row)->getCalculatedValue();
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
        // Column 8 == column H
        $sheet->setCellValueByColumnAndRow(8, 2, 'T H E S E   A M O U N T S   W E R E');
        $sheet->setCellValueByColumnAndRow(8, 3, 'A D D E D   T O   C L U S T E R   H I S T O R Y   O N');
        $sheet->setCellValueByColumnAndRow(8, 4, Carbon::now()->toDateTimeString());
        $sheet->setCellValueByColumnAndRow(8, 5, 'T H I S   S H E E T   I S   C L O S E D');

        // TODO: Re-protect (PhpSpreadsheet: just enable protection flag)
        // $sheet->getProtection()->setSheet(true)->setPassword(self::SALES_PASSWORD);
    }

    /* ───────────────────────────────────────────────────────────── */
    /*  flag clusters with 12 months of data */
    /* ───────────────────────────────────────────────────────────── */
    private function flagFullYearInLast12(Worksheet $l12): void
    {
        for ($row = 5; $row <= self::MAX_ROW_CHECK; $row++) {
            if (trim((string)$l12->getCellByColumnAndRow(2, $row)->getValue()) === '') {
                break; // reached end
            }

            // Bread months cols 9-20  → flag col 8
            $complete = true;
            for ($c = 9; $c <= 20; $c++) {
                $val = (float)$l12->getCellByColumnAndRow($c, $row)->getCalculatedValue();
                if ($val == 0.0) {
                    $complete = false;
                    break;
                }
            }
            $l12->setCellValueByColumnAndRow(8, $row, $complete ? 'Y' : 'N');

            // Non-Bread months cols 24-35 → flag col 23
            $complete = true;
            for ($c = 24; $c <= 35; $c++) {
                $val = (float)$l12->getCellByColumnAndRow($c, $row)->getCalculatedValue();
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
            if (trim((string)$sheet->getCellByColumnAndRow(2, $r)->getValue()) === '') {
                break;
            }
            $sheet->setCellValueByColumnAndRow($breadCol, $r, 0);
            $sheet->setCellValueByColumnAndRow($nonBdCol, $r, 0);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────────
    // Sync “Last12Mos” sheet (VBA: DoneSynching block)
    // ──────────────────────────────────────────────────────────────────────────────
    private function syncLast12Months(
        Worksheet $salesSheet,
        Worksheet $l12mSheet,
        int       $monthNo
    ): void
    {
        // posting columns for Last12Mos: Bread = 8+MonthNo, NonBread = 23+MonthNo
        $breadCol = 8 + $monthNo;
        $nonBdCol = 23 + $monthNo;

        // ------------------------------------------------------------------
        // first clear existing values for that month
        // ------------------------------------------------------------------
        $this->zeroMonthColumns($l12mSheet, $breadCol, $nonBdCol);

        // ------------------------------------------------------------------
        // now walk both sheets & sync
        // ------------------------------------------------------------------
        $salesRow = 8;   // first data row in Sales sheet
        $l12mRow = 5;   // first data row in Last12Mos

        while (true) {
            $salesCode = trim((string)$salesSheet->getCellByColumnAndRow(1, $salesRow)->getValue());
            if ($salesCode === '') {
                break;          // reached blank row in sales sheet
            }

            $l12Code = trim((string)$l12mSheet->getCellByColumnAndRow(2, $l12mRow)->getValue());

            // ----------------------------------------------------------
            // Case A – l12 sheet is exhausted OR salesCode > l12Code
            // ----------------------------------------------------------
            if ($l12Code === '' || $salesCode > $l12Code) {

                if ($l12Code === '') {
                    // append new row at end
                    $l12mSheet->insertNewRowBefore($l12mRow, 1);
                    $this->copyToLast12($salesSheet, $salesRow,
                        $l12mSheet, $l12mRow,
                        $breadCol, $nonBdCol);
                    $l12mRow++;                                    // move to next row
                    $salesRow++;
                } else {
                    $l12mRow++;                                    // advance l12 pointer
                }

                continue;
            }

            // ----------------------------------------------------------
            // Case B – exact match
            // ----------------------------------------------------------
            if ($salesCode === $l12Code) {
                $this->updateLast12Existing($salesSheet, $salesRow,
                    $l12mSheet, $l12mRow,
                    $breadCol, $nonBdCol);
                $salesRow++;

                continue;              // keep same l12mRow for next compare
            }

            // ----------------------------------------------------------
            // Case C – salesCode < l12Code  ➜ insert before current l12 row
            // ----------------------------------------------------------
            $l12mSheet->insertNewRowBefore($l12mRow, 1);
            $this->copyToLast12($salesSheet, $salesRow,
                $l12mSheet, $l12mRow,
                $breadCol, $nonBdCol);
            $salesRow++;
            /* l12mRow now points to newly inserted row – keep for next compare */
        }
    }

    /** insert a fresh row into Last12Mos populated from sales sheet */
    private function copyToLast12(
        Worksheet $src,
        int       $srcRow,
        Worksheet $dst,
        int       $dstRow,
        int       $breadCol,
        int       $nonBdCol
    ): void
    {
        // columns: dst B..F ← src A..E
        for ($i = 1; $i <= 5; $i++) {
            $dst->getCellByColumnAndRow($i + 1, $dstRow)
                ->setValue($src->getCellByColumnAndRow($i, $srcRow)->getCalculatedValue());
        }

        $bread = $this->cell($src, 38, $srcRow) + $this->cell($src, 42, $srcRow);
        $nonBd = $this->cell($src, 39, $srcRow) + $this->cell($src, 43, $srcRow);

        $dst->getCellByColumnAndRow($breadCol, $dstRow)->setValue($bread);
        $dst->getCellByColumnAndRow($nonBdCol, $dstRow)->setValue($nonBd);
    }

    /** update existing row in Last12Mos (cols C..F and accumulate sales) */
    private function updateLast12Existing(
        Worksheet $src,
        int       $srcRow,
        Worksheet $dst,
        int       $dstRow,
        int       $breadCol,
        int       $nonBdCol
    ): void
    {
        // columns dst C..F ← src B..E
        for ($dstCol = 3; $dstCol <= 6; $dstCol++) {
            $srcCol = $dstCol - 1;
            $dst->getCellByColumnAndRow($dstCol, $dstRow)
                ->setValue($src->getCellByColumnAndRow($srcCol, $srcRow)->getValue());
        }

        $addBread = $this->cell($src, 38, $srcRow) + $this->cell($src, 42, $srcRow);
        $addNonBd = $this->cell($src, 39, $srcRow) + $this->cell($src, 43, $srcRow);

        $dstBread = $dst->getCellByColumnAndRow($breadCol, $dstRow);
        $dstNonBd = $dst->getCellByColumnAndRow($nonBdCol, $dstRow);

        $dstBread->setValue(((float)$dstBread->getValue()) + $addBread);
        $dstNonBd->setValue(((float)$dstNonBd->getValue()) + $addNonBd);
    }

    private function cell(Worksheet $sh, int $col, int $row): float
    {
        return (float)$sh->getCellByColumnAndRow($col, $row)->getCalculatedValue();
    }

    // ───────────────────────── AddColTotals ─────────────────────────
    private function addColumnTotals(
        Worksheet $hist,
        int       $totalsRow,    // where the Σ formulas will be written
        int       $lastDataRow   // last row that contains data
    ): void
    {
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
    // MatchClusters (VBA port)
    // ──────────────────────────────────────────────────────────────────────────────
    private function matchClusters(
        Worksheet $salesSheet,
        Worksheet $histSheet,
        int       $breadCol,
        int       $nonBdCol
    ): void
    {
        $salesRow = 8;   // rows in Monthly Sales report
        $histRow = 5;   // rows in History sheet

        while (true) {
            $salesCode = trim((string)$salesSheet->getCellByColumnAndRow(1, $salesRow)->getValue());
            if ($salesCode === '') {
                // reached blank row → done
                break;
            }

            $histCode = trim((string)$histSheet->getCellByColumnAndRow(2, $histRow)->getValue());

            // ------------------------------------------------------------------
            // Case 1: history sheet row blank  OR  salesCode > histCode  → move
            // ------------------------------------------------------------------
            if ($histCode === '' || $salesCode > $histCode) {

                if ($histCode === '') {
                    // end-of-history: append new cluster
                    $histSheet->insertNewRowBefore($histRow, 1);
                    $this->copyClusterData($salesSheet, $salesRow, $histSheet, $histRow,
                        $breadCol, $nonBdCol);
                    $histRow++;          // move past the row we just filled
                    $salesRow++;         // next sales line
                } else {
                    // not yet at end: just advance history pointer
                    $histRow++;
                }

                continue;
            }

            // ------------------------------------------------------------------
            // Case 2: exact match — update & accumulate
            // ------------------------------------------------------------------
            if ($salesCode === $histCode) {
                $this->updateExistingClusterRow(
                    $salesSheet, $salesRow, $histSheet, $histRow, $breadCol, $nonBdCol
                );
                $salesRow++;             // next sales line

                continue;                // keep same histRow for next compare (VBA logic)
            }

            // ------------------------------------------------------------------
            // Case 3: salesCode < histCode — insert new cluster *before* histRow
            // ------------------------------------------------------------------
            $histSheet->insertNewRowBefore($histRow, 1);
            $this->copyClusterData($salesSheet, $salesRow, $histSheet, $histRow,
                $breadCol, $nonBdCol);
            $salesRow++;                 // next sales line; histRow stays (points to new row)
        }
    }

    /** clone the VBA “insert new cluster” payload (columns 2-12 + values) */
    private function copyClusterData(
        Worksheet $src,
        int       $srcRow,
        Worksheet $dst,
        int       $dstRow,
        int       $breadCol,
        int       $nonBdCol
    ): void
    {
        // columns 2-12 on history ⇐ columns 1-11 on sales
        for ($i = 1; $i <= 11; $i++) {
            $dst->getCellByColumnAndRow($i + 1, $dstRow)
                ->setValue($src->getCellByColumnAndRow($i, $srcRow)->getValue());
        }

        $bread = $this->cell($src, 38, $srcRow) + $this->cell($src, 42, $srcRow);
        $nonBd = $this->cell($src, 39, $srcRow) + $this->cell($src, 43, $srcRow);

        $dst->getCellByColumnAndRow($breadCol, $dstRow)->setValue($bread);
        $dst->getCellByColumnAndRow($nonBdCol, $dstRow)->setValue($nonBd);
    }

    /** update existing row (columns 4-12) and accumulate sales */
    private function updateExistingClusterRow(
        Worksheet $src,
        int       $srcRow,
        Worksheet $dst,
        int       $dstRow,
        int       $breadCol,
        int       $nonBdCol
    ): void
    {
        // columns 4-12 ⇐ sales columns 3-11
        for ($dstCol = 4; $dstCol <= 12; $dstCol++) {
            $srcCol = $dstCol - 1;
            $dst->getCellByColumnAndRow($dstCol, $dstRow)
                ->setValue($src->getCellByColumnAndRow($srcCol, $srcRow)->getValue());
        }

        $addBread = $this->cell($src, 38, $srcRow) + $this->cell($src, 42, $srcRow);
        $addNonBd = $this->cell($src, 39, $srcRow) + $this->cell($src, 43, $srcRow);

        $dstBreadCell = $dst->getCellByColumnAndRow($breadCol, $dstRow);
        $dstNonBdCell = $dst->getCellByColumnAndRow($nonBdCol, $dstRow);

        $dstBreadCell->setValue(((float)$dstBreadCell->getValue()) + $addBread);
        $dstNonBdCell->setValue(((float)$dstNonBdCell->getValue()) + $addNonBd);
    }

    // ──────────────────────────────────────────────────────────────────────────────
    // 1️⃣/2️⃣ helpers (unchanged from earlier versions)
    // ──────────────────────────────────────────────────────────────────────────────
    private function computeYtdAndSameStoreFlags(
        Worksheet $hist,
        int       $breadCol,   // current month’s posting col
        int       $nonBdCol
    ): int
    {
        for ($row = 5; $row <= self::MAX_ROW_CHECK; $row++) {
            if (trim((string)$hist->getCellByColumnAndRow(2, $row)->getValue()) === '') {
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
                $val = is_numeric($raw) ? (float)$raw : 0.0;

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

                $val = is_numeric($raw) ? (float)$raw : 0.0;

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
            if (!$ws) {
                $this->logErrorToMacroBatch($batchId, 'Sheet ' . $tab . ' not found. Available sheets: ' . implode(', ', $allSheets) . ' for batch ID: ' . $batchId);
                throw new Exception('Sheet ' . $tab . ' not found.');
            }


            $flag = trim((string)$ws->getCellByColumnAndRow(8, 5)->getCalculatedValue());

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
        $tpl = $book->getSheetByName((string)($year - 1));
        if (!$tpl) {
            throw new Exception('Missing template sheet for ' . ($year - 1));
        }
        $clone = clone $tpl;
        $book->addSheet($clone, $book->getIndex($tpl));
        $clone->setTitle((string)$year);
        $clone->getProtection()->setSheet(false);

        for ($row = 5; $row <= self::MAX_ROW_CHECK; $row++) {
            if (trim((string)$clone->getCellByColumnAndRow(2, $row)->getValue()) === '') {
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
        $ws = $book->getSheetByName((string)$year);
        if (!$ws) {
            throw new Exception('Year sheet ' . $year . ' not found.');
        }
        $ws->getProtection()->setSheet(false);

        return $ws;
    }

    private function getMnsrPath(int $batchId): string
    {
        // Get current batch to match month/year
        $batch = MacroBatch::find($batchId);
        if (!$batch) {
            throw new Exception('MacroBatch not found for ID: ' . $batchId);
        }

        $macroOutput = MacroOutput::where('file_type_id', MacroFileTypeEnum::MNSR()->value)
            ->where('month', $batch->month)
            ->where('year', $batch->year)
            ->whereHas('macroBatch', function ($query) {
                $query->where('status', MacroBatchStatusEnum::Successful()->value);
            })
            ->orderByDesc('created_at')
            ->first();

        if (!$macroOutput) {
            $this->logErrorToMacroBatch($batchId, 'No MNSR file found for month ' . $batch->month . ' year ' . $batch->year . ' from successful batch', 'getMnsrPath');
            throw new Exception('No MNSR file found for the specified month/year from successful batch.');
        }


        // Log if file exists in storage
        if ($this->fileExists($macroOutput->file_path)) {
        } else {
            $this->logErrorToMacroBatch($batchId, 'MNSR file does not exist in storage: ' . $macroOutput->file_path, 'getMnsrPath');
        }

        return $macroOutput->file_path;
    }

    private function getMnsrRevisionType(int $batchId): int
    {
        $macroOutput = MacroOutput::where('file_type_id', MacroFileTypeEnum::MNSR()->value)
            ->whereHas('macroBatch', function ($query) {
                $query->where('status', MacroBatchStatusEnum::Successful()->value);
            })
            ->orderByDesc('created_at')
            ->first();

        return $macroOutput ? $macroOutput->file_revision_id : MacroFileRevisionEnum::MNSRDefault()->value;
    }

    private function getSalesHistoryPath(int $batchId): string
    {
        // Get the latest Sales History file from SalesPerformance table
        $salesPerformance = SalesPerformance::orderBy('created_at', 'desc')->first();

        if (!$salesPerformance || !$salesPerformance->path) {
            $this->logErrorToMacroBatch($batchId, 'No Sales History file found for batch ID: ' . $batchId);
            throw new Exception('No Sales History file found');
        }

        $path = $salesPerformance->path;

        // Log if file exists in storage
        if ($this->fileExists($path)) {
        } else {
            $this->logErrorToMacroBatch($batchId, 'Sales History file does not exist in storage: ' . $path);
        }

        return $path;
    }

    /** @return array{string,string} */
    private function parseMonthYear(string $file, int $batchId): array
    {
        if (!preg_match('/Rept-([A-Za-z]{3})-(\d{4})\.xlsx$/', $file, $m)) {
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
                $this->letterTable[] = $c1 . $c2;
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
        return $this->generateUploadBasePath() . "/royalty/generated/{$batchId}/{$fileName}";
    }

    private function generateProcessedHistoryPath(int $batchId, string $month, string $year): string
    {
        $fileName = "JBS-Sales-History-" . now()->format('Y-m-d_H-i-s') . ".xlsx";
        return $this->generateUploadBasePath() . "/royalty/generated/{$batchId}/{$fileName}";
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
