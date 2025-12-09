<?php

namespace App\Jobs;

use App\Enums\StoreTypeEnum;
use App\Models\Store;
use App\Models\StoreHistory;
use App\Models\User;
use App\Notifications\DataImportNotification;
use App\Traits\ManageFilesystems;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class JbmisHistoryImportJob implements ShouldQueue
{
    use Queueable, ManageFilesystems;

    private string $filePath;

    private int $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $filePath, int $userId)
    {
        $this->filePath = $filePath;
        $this->userId = $userId;
        $this->queue = 'default';
    }

    /**
     * Get store type priority order (lower number = higher priority)
     */
    private function getStoreTypePriority(): array
    {
        return [
            StoreTypeEnum::Branch()->value => 1,
            StoreTypeEnum::Express()->value => 2,
            StoreTypeEnum::Junior()->value => 3,
            StoreTypeEnum::Outlet()->value => 4,
        ];
    }

    /**
     * Select the priority store from multiple matches
     */
    private function selectPriorityStore($stores)
    {
        if ($stores->count() === 1) {
            return $stores->first();
        }

        $priorities = $this->getStoreTypePriority();

        // Sort by store type priority first, then by ID (ascending)
        $sortedStores = $stores->sortBy(function ($store) use ($priorities) {
            $priority = $priorities[$store->store_type] ?? 999; // Unknown types get low priority
            return [$priority, $store->id];
        });

        return $sortedStores->first();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = User::find($this->userId);
        if (!$user) {
            Log::error('JBMIS history import job failed: User not found', ['user_id' => $this->userId]);
            return;
        }

        try {
            Log::info('JBMIS history import starting file handling', [
                'user_id' => $this->userId,
                'file_path' => $this->filePath,
                'file_exists_local' => file_exists($this->filePath)
            ]);

            // Handle S3 private files by downloading to temporary location (same as JFMDataMigrationService)
            $tempFilePath = null;
            $actualFilePath = $this->filePath;

            // Check if file is stored in cloud storage (not a local path) - using same logic as JFMDataMigrationService
            if (!file_exists($this->filePath) && $this->fileExists($this->filePath)) {
                // Create temporary file
                $tempFileName = 'temp_jbmis_import_'.Str::random(10).'.xlsx';
                $tempFilePath = storage_path('app/temp/'.$tempFileName);

                // Ensure temp directory exists
                if (!is_dir(dirname($tempFilePath))) {
                    mkdir(dirname($tempFilePath), 0755, true);
                }

                // Download file from cloud storage to temporary location - using same method as JFMDataMigrationService
                $fileContents = $this->readFile($this->filePath);
                file_put_contents($tempFilePath, $fileContents);
                $actualFilePath = $tempFilePath;
            } elseif (!file_exists($this->filePath)) {
                throw new \Exception("File does not exist: {$this->filePath}");
            }

            Log::info('JBMIS history import file resolved', [
                'original_path' => $this->filePath,
                'actual_path' => $actualFilePath,
                'using_temp_file' => $tempFilePath !== null
            ]);

            // Read the Excel file
            $data = Excel::toArray(new class implements ToArray {
                public function array(array $array): array
                {
                    return $array;
                }
            }, $actualFilePath);

            if (empty($data) || empty($data[0])) {
                throw new \Exception('Excel file is empty or invalid.');
            }

            $rows = $data[0]; // Get first sheet

            // Remove header row (first row)
            array_shift($rows);

            if (empty($rows)) {
                throw new \Exception('No data rows found in Excel file.');
            }

            Log::info('JBMIS history import starting', [
                'user_id' => $this->userId,
                'rows_count' => count($rows),
                'file_path' => $this->filePath
            ]);

            $successCount = 0;
            $errorCount = 0;
            $skippedCount = 0;
            $errors = [];

            // Process each row
            foreach ($rows as $rowIndex => $row) {
                $rowNumber = $rowIndex + 2; // +2 because we removed header and Excel starts at 1

                try {
                    // Extract data from row
                    $clusterCode = isset($row[0]) ? trim($row[0]) : null;
                    $salesPointCode = isset($row[1]) ? trim($row[1]) : null;
                    $jbmisCode = isset($row[2]) ? trim($row[2]) : null;
                    $effectivityDate = isset($row[3]) ? $row[3] : null;

                    // Validate required fields
                    if (empty($clusterCode)) {
                        throw new \Exception("Cluster code is required");
                    }

                    if (empty($salesPointCode)) {
                        throw new \Exception("Sales point code is required");
                    }

                    if (empty($jbmisCode)) {
                        throw new \Exception("JBMIS code is required");
                    }

                    // Find stores by sales point code only, filtered by allowed store types
                    $allowedStoreTypes = [
                        StoreTypeEnum::Branch()->value,
                        StoreTypeEnum::Express()->value,
                        StoreTypeEnum::Junior()->value,
                        StoreTypeEnum::Outlet()->value,
                    ];
                    
                    $stores = Store::where('sales_point_code', $salesPointCode)
                        ->whereIn('store_type', $allowedStoreTypes)
                        ->get();
                    
                    if ($stores->isEmpty()) {
                        throw new \Exception("No stores found with sales point code: {$salesPointCode} (allowed types: Branch, Express, Junior, Outlet)");
                    }

                    // Select priority store if multiple matches
                    $store = $this->selectPriorityStore($stores);
                    
                    if ($stores->count() > 1) {
                        Log::info("Multiple stores found for sales point: {$salesPointCode}. Selected store ID: {$store->id} (type: {$store->store_type}) at row {$rowNumber}");
                    }

                    // Skip if both cluster and JBMIS codes match current store data
                    if ($store->cluster_code === $clusterCode && $store->jbmis_code === $jbmisCode) {
                        $skippedCount++;
                        continue;
                    }

                    // Handle effectivity date - use current date if empty
                    if (empty($effectivityDate)) {
                        $effectivityDate = now()->format('Y-m-d');
                    } else {
                        // Parse effectivity date
                        if (is_numeric($effectivityDate)) {
                            // Excel date serial number
                            $effectivityDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($effectivityDate)->format('Y-m-d');
                        } else {
                            // Try to parse as string
                            $effectivityDate = date('Y-m-d', strtotime($effectivityDate));
                        }

                        if (!$effectivityDate) {
                            throw new \Exception("Invalid effectivity date format");
                        }
                    }

                    // Create coordinated history entries for both cluster and JBMIS codes
                    $changeGroupId = 'jbmis_import_' . now()->timestamp . '_' . uniqid();
                    
                    // Add cluster code history entry
                    StoreHistory::create([
                        'store_id' => $store->id,
                        'user_id' => null, // System migration
                        'field' => 'cluster_code',
                        'old_value' => null,
                        'new_value' => $clusterCode,
                        'effective_at' => $effectivityDate,
                        'change_group_id' => $changeGroupId,
                        'created_at' => $effectivityDate,
                        'updated_at' => now(),
                    ]);
                    
                    // Add JBMIS code history entry
                    StoreHistory::create([
                        'store_id' => $store->id,
                        'user_id' => null, // System migration
                        'field' => 'jbmis_code',
                        'old_value' => null,
                        'new_value' => $jbmisCode,
                        'effective_at' => $effectivityDate,
                        'change_group_id' => $changeGroupId,
                        'created_at' => $effectivityDate,
                        'updated_at' => now(),
                    ]);

                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Row {$rowNumber}: " . $e->getMessage();
                }
            }

            // Send notification (success or with errors)
            $user->notify(new DataImportNotification($successCount, $errors));

            Log::info('JBMIS history import completed', [
                'user_id' => $this->userId,
                'success_count' => $successCount,
                'skipped_count' => $skippedCount,
                'error_count' => $errorCount,
                'total_errors' => count($errors),
            ]);

        } catch (Throwable $exception) {
            Log::error('JBMIS history import job failed', [
                'user_id' => $this->userId,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            // Send failure notification
            $user->notify(new DataImportNotification(0, [$exception->getMessage()]));

            throw $exception;
        } finally {
            // Clean up temporary file if it was created
            if ($tempFilePath && file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }
        }
    }

    public function failed(Throwable $exception): void
    {
        $user = User::find($this->userId);
        if ($user) {
            $user->notify(new DataImportNotification(0, ['Job failed: ' . $exception->getMessage()]));
        }
    }
}