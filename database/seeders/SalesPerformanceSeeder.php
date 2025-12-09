<?php

namespace Database\Seeders;

use App\Models\SalesPerformance;
use App\Models\SalesPerformanceDetail;
use App\Traits\ManageFilesystems;
use App\Traits\ManageRoyaltyFiles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SalesPerformanceSeeder extends Seeder
{
    use ManageFilesystems, ManageRoyaltyFiles;

    public function run(): void
    {
        DB::beginTransaction();

        // Check if cached data exists
        $cachedDataPath = $this->generateUploadBasePath() . '/royalty/fixed-caches/jbs-sales-history-data.json';
//        if ($this->fileExists($cachedDataPath)) {
//            $salesHistorySheetsWithName = json_decode($this->readFile($cachedDataPath), true);
//        } else {
            // Read from template file
            $salesHistoryPath = storage_path('app/private/royalty/cache/JBS-Sales-History.xlsx');
            if (!file_exists($salesHistoryPath)) {
                throw new \RuntimeException('JBS Sales History template not found: ' . $salesHistoryPath);
            }

            $salesHistorySheets = Excel::toCollection([], $salesHistoryPath, null, \Maatwebsite\Excel\Excel::XLSX);
            $reader = IOFactory::createReaderForFile($salesHistoryPath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($salesHistoryPath);
            $sheetNames = $spreadsheet->getSheetNames();
            $salesHistorySheetsWithName = [];
            foreach ($salesHistorySheets as $index => $rows) {
                $title = $sheetNames[$index] ?? "Sheet{$index}";
                $salesHistorySheetsWithName[$title] = $rows;
            }

            // Upload cached data
            $jsonData = json_encode($salesHistorySheetsWithName, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $this->uploadData($jsonData, $cachedDataPath);
//        }

        $year = now()->year;
        $endYear = $year - 20;

        // Column mappings (0-based index)
        $breadColumns = [15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26]; // P to AA (Jan-Dec)
        $nonBreadColumns = [29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40]; // AD to AO (Jan-Dec)

        $recorded_at = now();
        $salesPerformance = new SalesPerformance;
        $salesPerformance->recorded_at = $recorded_at;
        $salesPerformance->save();

        $outputPath = $this->generateUploadBasePath().'/royalty/jbs-sales-history/'.$salesPerformance->id.'/JBS-Sales-History-'.$recorded_at->format('Y-m-d_H-i-s').'.xlsx';
        $cachedPath = $this->generateUploadBasePath().'/royalty/jbs-sales-history/'.$salesPerformance->id.'/Cached-JBS-Sales-History-'.$recorded_at->format('Y-m-d_H-i-s').'.json';

        // Upload JBS Sales History file
        $salesHistoryTemplatePath = storage_path('app/private/royalty/cache/JBS-Sales-History.xlsx');
        if (!file_exists($salesHistoryTemplatePath)) {
            throw new \RuntimeException('JBS Sales History template not found for upload');
        }

        // Upload to output path
        $this->upload($salesHistoryTemplatePath, $outputPath);

        // Upload cached data
        $jsonData = json_encode($salesHistorySheetsWithName, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->uploadData($jsonData, $cachedPath);

        $salesPerformance->path = $outputPath;
        $salesPerformance->cached_path = $cachedPath;

        // Process JBS-Sales-History-By-Store file
        $byStoreTemplatePath = storage_path('app/private/royalty/cache/JBS-Sales-History-By-Store.xlsx');
        if (file_exists($byStoreTemplatePath)) {
            // Read By-Store file
            $byStoreData = Excel::toCollection([], $byStoreTemplatePath, null, \Maatwebsite\Excel\Excel::XLSX);

            // Get sheet names for by-store data
            $byStoreReader = IOFactory::createReaderForFile($byStoreTemplatePath);
            $byStoreReader->setReadDataOnly(true);
            $byStoreSpreadsheet = $byStoreReader->load($byStoreTemplatePath);
            $byStoreSheetNames = $byStoreSpreadsheet->getSheetNames();

            $byStoreSheetsWithName = [];
            foreach ($byStoreData as $index => $rows) {
                $title = $byStoreSheetNames[$index] ?? "Sheet{$index}";
                $byStoreSheetsWithName[$title] = $rows;
            }

            // Create By-Store paths
            $byStoreOutputPath = $this->generateUploadBasePath().'/royalty/jbs-sales-history/'.$salesPerformance->id.'/JBS-Sales-History-By-Store-'.$recorded_at->format('Y-m-d_H-i-s').'.xlsx';
            $byStoreCachedPath = $this->generateUploadBasePath().'/royalty/jbs-sales-history/'.$salesPerformance->id.'/Cached-JBS-Sales-History-By-Store-'.$recorded_at->format('Y-m-d_H-i-s').'.json';

            // Upload By-Store file
            $this->upload($byStoreTemplatePath, $byStoreOutputPath);

            // Create and upload By-Store cache with sheet names as keys
            $byStoreJsonData = json_encode($byStoreSheetsWithName, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $this->uploadData($byStoreJsonData, $byStoreCachedPath);

            // Create fixed cache for By-Store data
            $fixedCachePath = $this->generateUploadBasePath() . '/royalty/fixed-caches/jbs-sales-history-by-store-data.json';
            $this->uploadData($byStoreJsonData, $fixedCachePath);

            // Update SalesPerformance with By-Store paths
            $salesPerformance->by_store_path = $byStoreOutputPath;
            $salesPerformance->by_store_cached_path = $byStoreCachedPath;
        }

        $salesPerformance->save();

        // Process each year's data from by-store file if it exists using bulk inserts
        if (isset($byStoreSheetsWithName) && !empty($byStoreSheetsWithName)) {
            $this->processSalesDataWithBulkInserts(
                $byStoreSheetsWithName,
                $salesPerformance->id,
                $year,
                $endYear,
                $breadColumns,
                $nonBreadColumns,
                $recorded_at
            );
        }

        DB::commit();
    }

    /**
     * Process sales data using bulk inserts for better performance
     */
    private function processSalesDataWithBulkInserts(
        array $byStoreSheetsWithName,
        int $salesPerformanceId,
        int $startYear,
        int $endYear,
        array $breadColumns,
        array $nonBreadColumns,
        $recordedAt
    ): void {
        $batchSize = 125; // Process in chunks of 100 records (SQL Server parameter limit: 125 × 14 fields = 1750 parameters)
        $insertData = [];
        $currentBatchSize = 0;
        $totalRecords = 0;

        for ($year = $startYear; $year >= $endYear; $year--) {
            if (empty($byStoreSheetsWithName[$year])) {
                continue;
            }

            foreach ($byStoreSheetsWithName[$year] as $rowIndex => $row) {
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

                    // truncate to 2 decimal places
                    $breadValue = (float)number_format($breadValue, 4, '.', '');
                    $nonBreadValue = (float)number_format($nonBreadValue, 4, '.', '');
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

                    $totalRecords++;

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
        if (!empty($insertData)) {
            DB::table('sales_performance_details')->insert($insertData);
        }

        // Bulk processing completed
    }
}
