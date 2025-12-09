<?php

namespace App\Jobs\Royalty;

use App\Enums\MacroBatchStatusEnum;
use App\Enums\MacroFileRevisionEnum;
use App\Enums\MacroFileTypeEnum;
use App\Enums\StoreTypeEnum;
use App\Models\Royalty\MacroBatch;
use App\Models\Royalty\MacroBatchConfig;
use App\Models\Royalty\MacroFixedCache;
use App\Models\Royalty\MacroOutput;
use App\Models\Royalty\MacroStep;
use App\Models\Royalty\MacroUpload;
use App\Models\SalesPerformance;
use App\Models\Store;
use App\Traits\ErrorLogger;
use App\Traits\ManageFilesystems;
use App\Traits\ManageRoyaltyFiles;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CacheUploadedRoyaltyFilesJob implements ShouldQueue
{
    use ErrorLogger, ManageFilesystems, ManageRoyaltyFiles;
    use Queueable;

    private $batch_id;

    public function __construct($batch_id)
    {
        $this->batch_id = $batch_id;
        $this->queue = 'royalty';
    }

    public function handle(): void
    {
        $shouldThrow = null;

        try {
            // Update batch status to Ongoing at the start if still Pending
            $batch = MacroBatch::find($this->batch_id);
            if ($batch && $batch->status == MacroBatchStatusEnum::Pending()->value) {
                $batch->status = MacroBatchStatusEnum::Ongoing()->value;
                $batch->save();
            }

            DB::beginTransaction();

            $macroUploads = MacroUpload::where('batch_id', $this->batch_id)->get();

            // Get MacroBatchConfig to check has_uploaded_mnsr flag
            $macroBatchConfig = MacroBatchConfig::where('batch_id', $this->batch_id)->first();

            if (! $macroBatchConfig) {
                throw new Exception("MacroBatchConfig not found for batch_id: {$this->batch_id}");
            }

            foreach ($macroUploads as $macroUpload) {
                $upload_path = null;

                if ($macroUpload->file_type_id == MacroFileTypeEnum::JBMISData()->value) {
                    /** Read JBMIS File */
                    $parts = explode('-', $macroUpload->file_name);
                    $region = $parts[2];

                    // Create temporary directory for processing
                    $tempDir = sys_get_temp_dir().'/royalty_'.$this->batch_id;
                    if (! file_exists($tempDir)) {
                        mkdir($tempDir, 0755, true);
                    }
                    $tempPath = $tempDir.'/'.$macroUpload->file_name;
                    file_put_contents($tempPath, $this->readFile($macroUpload->file_path));
                    $jbmisFile = Excel::toCollection([], $tempPath, null, \Maatwebsite\Excel\Excel::XLSX);

                    $upload_path = $this->generateUploadBasePath().'/royalty/uploads/'.$this->batch_id.'/Cached-'.$macroUpload->file_name;
                    $upload_path = preg_replace('/\.[^.]+$/', '.json', $upload_path);

                    $jsonData = json_encode($jbmisFile[0], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    $success = $this->uploadData($jsonData, $upload_path);

                    // Clean up temporary files
                    if (file_exists($tempPath)) {
                        unlink($tempPath);
                    }
                } elseif ($macroUpload->file_type_id == MacroFileTypeEnum::POSData()->value) {
                    /** Read POS File */
                    // Create temporary directory for processing
                    $tempDir = sys_get_temp_dir().'/royalty_'.$this->batch_id;
                    if (! file_exists($tempDir)) {
                        mkdir($tempDir, 0755, true);
                    }
                    $tempPath = $tempDir.'/'.$macroUpload->file_name;
                    file_put_contents($tempPath, $this->readFile($macroUpload->file_path));
                    $posFile = Excel::toCollection([], $tempPath, null, \Maatwebsite\Excel\Excel::XLSX);

                    $upload_path = $this->generateUploadBasePath().'/royalty/uploads/'.$this->batch_id.'/Cached-'.$macroUpload->file_name;
                    $upload_path = preg_replace('/\.[^.]+$/', '.json', $upload_path);

                    $jsonData = json_encode($posFile[0], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    $success = $this->uploadData($jsonData, $upload_path);

                    // Clean up temporary files
                    if (file_exists($tempPath)) {
                        unlink($tempPath);
                    }
                } elseif ($macroUpload->file_type_id == MacroFileTypeEnum::MNSR()->value) {
                    /** Read manually uploaded MNSR File */

                    // Extract month and year from filename (Monthly-Natl-Sales-Rept-MMM-YYYY.xlsx)
                    $parts = explode('-', $macroUpload->file_name);
                    $monthAbbrev = $parts[4]; // MMM
                    $yearWithExt = $parts[5]; // YYYY.xlsx
                    $year = pathinfo($yearWithExt, PATHINFO_FILENAME); // strips .xlsx

                    // Create temporary directory for processing
                    $tempDir = sys_get_temp_dir().'/royalty_'.$this->batch_id;
                    if (! file_exists($tempDir)) {
                        mkdir($tempDir, 0755, true);
                    }
                    $tempPath = $tempDir.'/'.$macroUpload->file_name;
                    file_put_contents($tempPath, $this->readFile($macroUpload->file_path));

                    // Use PhpSpreadsheet to ensure formulas are calculated
                    $reader = IOFactory::createReader('Xlsx');
                    $reader->setReadDataOnly(true);
                    $reader->setReadEmptyCells(false);

                    $spreadsheet = $reader->load($tempPath);

                    //                    // First, trim all worksheets to remove unnecessary rows
                    //                    foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                    //                        $this->trimWorksheet($worksheet);
                    //                    }
                    //
                    //                    // Save the trimmed spreadsheet back to temp file
                    //                    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
                    //                    $writer->save($tempPath);

                    // Now read the trimmed sheets for JSON conversion
                    $mnsrData = [];
                    foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                        $sheetName = $worksheet->getTitle();
                        $mnsrData[$sheetName] = $worksheet->toArray(null, true, true, false);
                    }

                    // Define paths for generated folder
                    $generatedBasePath = $this->generateUploadBasePath().'/royalty/generated/'.$this->batch_id;
                    $generatedXlsxPath = $generatedBasePath.'/Monthly-Natl-Sales-Rept-'.$monthAbbrev.'-'.$year.'.xlsx';
                    $generatedCachedPath = $generatedBasePath.'/Cached-Monthly-Natl-Sales-Rept-'.$monthAbbrev.'-'.$year.'.json';

                    // If has_uploaded_mnsr is true, copy the XLSX file to generated folder
                    if ($macroBatchConfig && $macroBatchConfig->has_uploaded_mnsr) {
                        // Copy the original XLSX file to generated folder (preserving all sheets intact)
                        $this->upload($tempPath, $generatedXlsxPath);

                        // Create MacroOutput with both XLSX and JSON paths
                        $macroOutput = new MacroOutput;
                        $macroOutput->batch_id = $this->batch_id;
                        $macroOutput->status = MacroBatchStatusEnum::Successful()->value;
                        $macroOutput->file_name = 'Monthly-Natl-Sales-Rept-'.$monthAbbrev.'-'.$year.'.xlsx';
                        $macroOutput->file_type_id = MacroFileTypeEnum::MNSR()->value;
                        $macroOutput->file_revision_id = MacroFileRevisionEnum::MNSRDefault()->value;
                        $macroOutput->file_path = $generatedXlsxPath;
                        $macroOutput->cached_path = $generatedCachedPath;
                        $macroOutput->month = Carbon::createFromFormat('M', $monthAbbrev)->month;
                        $macroOutput->year = $year;
                        $macroOutput->completed_at = now();
                        $macroOutput->save();
                    }

                    // Always upload the cached JSON data
                    $upload_path = $generatedCachedPath;
                    $jsonData = json_encode($mnsrData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    $success = $this->uploadData($jsonData, $upload_path);

                    // Clean up temporary files
                    if (file_exists($tempPath)) {
                        unlink($tempPath);
                    }
                    if (is_dir($tempDir) && count(scandir($tempDir)) == 2) {
                        rmdir($tempDir);
                    }
                }

                if ($upload_path) {
                    $macroUpload->cached_path = $upload_path;
                    $macroUpload->save();
                }
            }

            $macroBatch = MacroBatch::find($this->batch_id);
            foreach ($macroUploads as $macroUpload) {
                if ($macroUpload->file_type_id == MacroFileTypeEnum::JBMISData()->value) {
                    $jbmisParts = explode('-', $macroUpload->file_name);
                    $jbmisRegion = $jbmisParts[2];

                    if ($jbmisRegion == 'JBC') {
                        continue;
                    }

                    $macroStep = MacroStep::firstOrNew([
                        'batch_id' => $macroBatch->id,
                        'upload_id' => $macroUpload->id,
                        'file_type_id' => MacroFileTypeEnum::MNSR()->value,
                        'file_revision_id' => MacroFileRevisionEnum::MNSRAddedFranchiseeData()->value,
                    ]);

                    $macroStep->save();
                }
            }

            $salesPerformance = SalesPerformance::orderBy('created_at', 'desc')->first();
            if ($salesPerformance) {
                $macroFixedCache = new MacroFixedCache;
                $macroFixedCache->sales_performance_id = $salesPerformance->id;
                $macroFixedCache->batch_id = $macroBatch->id;
                $macroFixedCache->file_type_id = MacroFileTypeEnum::JBSSalesHistory()->value;
                $macroFixedCache->file_revision_id = MacroFileRevisionEnum::JBSSalesHistoryDefault()->value;
                $macroFixedCache->cached_path = $salesPerformance->cached_path;
                $macroFixedCache->save();

                $macroFixedCache = new MacroFixedCache;
                $macroFixedCache->sales_performance_id = $salesPerformance->id;
                $macroFixedCache->batch_id = $macroBatch->id;
                $macroFixedCache->file_type_id = MacroFileTypeEnum::JBSSalesHistoryByStore()->value;
                $macroFixedCache->file_revision_id = MacroFileRevisionEnum::JBSSalesHistoryByStoreDefault()->value;
                $macroFixedCache->cached_path = $salesPerformance->by_store_cached_path;
                $macroFixedCache->save();

                $allowedStoreTypes = [
                    StoreTypeEnum::Branch()->value,
                    StoreTypeEnum::Express()->value,
                    StoreTypeEnum::Junior()->value,
                    StoreTypeEnum::Outlet()->value,
                ];

                $stores = Store::with('franchisee', 'histories')
                    ->where('store_code', 'like', 'B%')
                    ->whereIn('store_type', $allowedStoreTypes)
                    ->orderBy('store_code')
                    ->get();

                $this->cacheBranchFranData($stores, $macroBatch->id, $salesPerformance->id);

                // Create separate store collection for code conversion table (includes ALL store types)
                $codeConversionStores = Store::with(['histories' => function ($query) {
                    $query->whereIn('field', ['jbmis_code', 'cluster_code'])->orderBy('created_at', 'desc');
                }])
                    ->where('store_code', 'like', 'B%')
                    ->whereNotNull('cluster_code')
                    ->whereNotNull('jbmis_code')
                    ->orderBy('store_code')
                    ->get();

                $this->cacheCodeConversionTable($codeConversionStores, $macroBatch->id, $salesPerformance->id);
            }

            DB::commit();

            // Dispatch MNSR-related jobs based on has_uploaded_mnsr flag
            if ($macroBatchConfig->has_uploaded_mnsr) {
                // Trigger AddFranchiseeDataToMnsrJob if MNSR was manually uploaded
                dispatch(new AddFranchiseeDataToMnsrJob($this->batch_id));
            } else {
                // Trigger GenerateMnsrJob if MNSR needs to be generated
                dispatch(new GenerateMnsrJob($this->batch_id));
            }
        } catch (Exception $e) {
            DB::rollBack();
            // Store the exception to throw later after marking batch as failed
            $shouldThrow = $e;
        } finally {
            // Always mark batch as failed if there was an error
            if ($shouldThrow !== null) {
                $this->markBatchAsFailed($shouldThrow);
                throw $shouldThrow;
            }
        }
    }

    public function cacheBranchFranData($stores, $batch_id, $sales_performance_id)
    {
        $branch_fran_data = [
            [
                [], [], [], [], [], [],
            ],
            [
                [], [], [], [], [], [],
            ],
            [
                [], [], [], [], [], [],
            ],
            [
                [], [], [], [], [], [],
            ],
        ];

        $count = 0;
        foreach ($stores as $store) {

            $data = [
                [
                    1 => $store->store_code,
                    7 => $store->store_status,
                    11 => $store->franchisee->franchisee_code,
                    13 => $store->franchisee->corporation_name,
                    14 => $store->franchisee->last_name,
                    15 => $store->franchisee->first_name,
                    21 => $this->convertToDecimalPercentage($store->old_continuing_license_fee), // NOTES :: missing from imported data
                    22 => $this->convertToDecimalPercentage($store->current_continuing_license_fee), // NOTES :: missing from imported data
                    23 => $store->continuing_license_fee_in_effect, // NOTES :: missing from imported data
                    29 => $store->maintenance_temporary_closed_at,
                    30 => $store->maintenance_reopening_date,
                    31 => $store->grand_opening_date ?? $store->soft_opening_date,
                    58 => $store->cluster_code,
                    60 => $store->projected_peso_bread_sales_per_month, // NOTES :: missing from imported data
                    61 => $store->projected_peso_non_bread_sales_per_month, // NOTES :: missing from imported data
                ],
                [
                    16 => $store->maintenance_permanent_closure_date,
                ],
                [
                    15 => $store->jbs_name,
                    22 => $store->region,
                    24 => $store->district,
                    25 => $store->om_cost_center_code,
                    26 => $store->om_district_name,
                    73 => $store->warehouse,
                ],
                [
                    8 => $store->franchisee->franchisee_code,
                    24 => $store->franchisee->contact_number,
                    27 => $store->franchisee->email,
                ],
            ];

            $branch_fran_data[0][] = $data[0];
            $branch_fran_data[1][] = $data[1];
            $branch_fran_data[2][] = $data[2];
            $branch_fran_data[3][] = $data[3];

            $count++;
        }

        $upload_path = $this->generateUploadBasePath().'/royalty/generated/'.$batch_id.'/Cached-branch-fran-data.json';

        $jsonData = json_encode($branch_fran_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $success = $this->uploadData($jsonData, $upload_path);

        $macroFixedCache = new MacroFixedCache;
        $macroFixedCache->sales_performance_id = $sales_performance_id;
        $macroFixedCache->batch_id = $batch_id;
        $macroFixedCache->file_type_id = MacroFileTypeEnum::BranchFranMaster()->value;
        $macroFixedCache->file_revision_id = MacroFileRevisionEnum::BranchFranMasterDefault()->value;
        $macroFixedCache->cached_path = $upload_path;
        $macroFixedCache->save();
    }

    private function getAllJbmisClusterCodePairs($store): array
    {
        $pairs = [];

        // Add current values as a pair
        if ($store->jbmis_code && $store->cluster_code) {
            $pairs[] = [
                'jbmis_code' => $store->jbmis_code,
                'cluster_code' => $store->cluster_code,
                'effective_at' => now(), // Current values are effective now
            ];
        }

        // Get all historical changes for both codes
        $clusterHistories = $store->histories->where('field', 'cluster_code')->keyBy('change_group_id');
        $jbmisHistories = $store->histories->where('field', 'jbmis_code')->keyBy('change_group_id');

        // Process coordinated changes (those with change_group_id)
        foreach ($clusterHistories as $groupId => $clusterHistory) {
            if ($groupId && isset($jbmisHistories[$groupId])) {
                $jbmisHistory = $jbmisHistories[$groupId];

                // Add the old values pair (historical pairing)
                if ($clusterHistory->old_value && $jbmisHistory->old_value) {
                    $pairs[] = [
                        'jbmis_code' => $jbmisHistory->old_value,
                        'cluster_code' => $clusterHistory->old_value,
                        'effective_at' => $clusterHistory->created_at->subSecond(),
                    ];
                }

                // Add the new values pair (the coordinated change)
                if ($clusterHistory->new_value && $jbmisHistory->new_value) {
                    $pairs[] = [
                        'jbmis_code' => $jbmisHistory->new_value,
                        'cluster_code' => $clusterHistory->new_value,
                        'effective_at' => $clusterHistory->created_at,
                    ];
                }
            }
        }

        // Process uncoordinated historical changes (backward compatibility)
        // This handles existing data where changes weren't grouped
        $ungroupedClusterHistories = $clusterHistories->whereNull('change_group_id');
        $ungroupedJbmisHistories = $jbmisHistories->whereNull('change_group_id');

        // Try to match by timestamp proximity (within 5 minutes)
        foreach ($ungroupedClusterHistories as $clusterHistory) {
            $matchingJbmisHistory = $ungroupedJbmisHistories->first(function ($jbmisHistory) use ($clusterHistory) {
                return abs($clusterHistory->created_at->timestamp - $jbmisHistory->created_at->timestamp) <= 300; // 5 minutes
            });

            if ($matchingJbmisHistory && $clusterHistory->new_value && $matchingJbmisHistory->new_value) {
                $pairs[] = [
                    'jbmis_code' => $matchingJbmisHistory->new_value,
                    'cluster_code' => $clusterHistory->new_value,
                    'effective_at' => $clusterHistory->created_at,
                ];
            }
        }

        // Remove duplicates based on jbmis_code + cluster_code combination
        $uniquePairs = [];
        foreach ($pairs as $pair) {
            $key = $pair['jbmis_code'].'|'.$pair['cluster_code'];
            if (! isset($uniquePairs[$key])) {
                $uniquePairs[$key] = $pair;
            }
        }

        return array_values($uniquePairs);
    }

    public function cacheCodeConversionTable($stores, $batch_id, $sales_performance_id)
    {
        // Collect all conversion data first
        $codeConversionData = [];
        foreach ($stores as $store) {
            $allJbmisClusterPairs = $this->getAllJbmisClusterCodePairs($store);

            foreach ($allJbmisClusterPairs as $pair) {
                $codeConversionData[] = [
                    0 => '', // Column A: Empty
                    1 => $pair['jbmis_code'], // Column B: jbmis_code (service reads [1])
                    2 => $pair['cluster_code'], // Column C: cluster_code (service reads [2]) - NOW USES HISTORICAL CLUSTER CODE
                    3 => $store->store_code, // Column D: store_code (service reads [3])
                    4 => $store->jbs_name, // Column E: jbs_name (service reads [4])
                ];
            }
        }

        // Sort by JBMIS code (index 1) for proper matching logic
        usort($codeConversionData, function ($a, $b) {
            return (int) $a[1] <=> (int) $b[1];
        });

        // Create new spreadsheet and populate with sorted data
        $spreadsheet = new Spreadsheet;
        $worksheet = $spreadsheet->getActiveSheet();

        $row = 1;
        foreach ($codeConversionData as $rowData) {
            $worksheet->setCellValue('A'.$row, $rowData[0]); // Column A: Empty
            $worksheet->setCellValue('B'.$row, $rowData[1]); // Column B: jbmis_code
            $worksheet->setCellValue('C'.$row, $rowData[2]); // Column C: cluster_code
            $worksheet->setCellValue('D'.$row, $rowData[3]); // Column D: store_code
            $worksheet->setCellValue('E'.$row, $rowData[4]); // Column E: jbs_name
            $row++;
        }

        // Create temporary directory for file generation
        $tempDir = sys_get_temp_dir().'/royalty_'.$batch_id;
        if (! file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Generate Excel file
        $tempExcelPath = $tempDir.'/code_conversion_table_'.uniqid().'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempExcelPath);

        // Upload Excel file
        $excelUploadPath = $this->generateUploadBasePath().'/royalty/generated/'.$batch_id.'/Code-Conversion-Table.xlsx';
        $this->upload($tempExcelPath, $excelUploadPath);

        // Create and upload JSON cache file
        $jsonUploadPath = $this->generateUploadBasePath().'/royalty/generated/'.$batch_id.'/Cached-Code-Conversion-Table.json';
        $jsonData = json_encode($codeConversionData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->uploadData($jsonData, $jsonUploadPath);

        // Clean up temporary files
        @unlink($tempExcelPath);

        // Create MacroFixedCache record for the code conversion table
        $macroFixedCache = new MacroFixedCache;
        $macroFixedCache->sales_performance_id = $sales_performance_id;
        $macroFixedCache->batch_id = $batch_id;
        $macroFixedCache->file_type_id = MacroFileTypeEnum::JBMISCodeConversion()->value;
        $macroFixedCache->file_revision_id = MacroFileRevisionEnum::JBMISCodeConversionDefault()->value;
        $macroFixedCache->cached_path = $jsonUploadPath;
        $macroFixedCache->save();
    }

    private function markBatchAsFailed(Exception $e): void
    {
        try {
            // Use a separate transaction to ensure batch status update persists
            // even if the main transaction is rolled back
            DB::transaction(function () use ($e) {
                $this->markBatchAsFailedWithError($this->batch_id, $e, 'CacheUploadedRoyaltyFilesJob failed');
            });
        } catch (Exception $logException) {
            Log::error('Failed to mark batch as failed', [
                'batch_id' => $this->batch_id,
                'original_error' => $e->getMessage(),
                'log_error' => $logException->getMessage(),
            ]);
        }
    }

    /**
     * Trim worksheet to remove unnecessary rows from the actual XLSX file
     * Find the last row where both column A and B have data (>= row 10)
     * Keep that row and add 2 empty rows after it
     */
    private function trimWorksheet($worksheet): void
    {
        $highestRow = $worksheet->getHighestRow();

        // Find the last row with data in columns A or B, starting from row 10
        $lastDataRow = 10; // Start from row 10

        for ($i = max(10, $highestRow); $i >= 10; $i--) {
            $columnA = $worksheet->getCell('A'.$i)->getCalculatedValue();
            $columnB = $worksheet->getCell('B'.$i)->getCalculatedValue();

            // If either column A or B has data, this is our last data row
            if (! empty($columnA) || ! empty($columnB)) {
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

    /**
     * Convert percentage values to decimal format with edge case handling
     *
     * @param  mixed  $value  The percentage value to convert
     * @return float|null The converted decimal value or null
     */
    private function convertToDecimalPercentage($value)
    {
        // Handle null or empty values
        if ($value === null || $value === '' || $value === 0) {
            return null;
        }

        // Convert to float for calculations
        $floatValue = (float) $value;

        // If value is already in decimal format (≤ 1), keep it as-is
        if ($floatValue <= 1) {
            // Trim to 2 decimal places without rounding
            return floor($floatValue * 100) / 100;
        }

        // If value is greater than 1, convert from percentage to decimal
        $decimalValue = $floatValue / 100;

        // Ensure value doesn't exceed 1.0 (100%)
        if ($decimalValue > 1) {
            $decimalValue = 1.0;
        }

        // Trim to 2 decimal places without rounding
        return floor($decimalValue * 100) / 100;
    }
}
