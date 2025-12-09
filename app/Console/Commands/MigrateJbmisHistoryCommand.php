<?php

namespace App\Console\Commands;

use App\Enums\StoreTypeEnum;
use App\Models\Store;
use App\Models\StoreHistory;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToArray;

class MigrateJbmisHistoryCommand extends Command
{
    protected $signature = 'migrate:jbmis-history';
    protected $description = 'Migrate JBMIS history data from Excel file into store history';

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

    public function handle()
    {
        $this->info('Starting JBMIS history migration...');

        // Define the Excel file path
        $filePath = storage_path('app/private/royalty/history-migration/jbmis-import.xlsx');

        if (!file_exists($filePath)) {
            $this->error("Excel file does not exist: {$filePath}");
            return 1;
        }

        try {
            // Read the Excel file
            $data = Excel::toArray(new class implements ToArray {
                public function array(array $array): array
                {
                    return $array;
                }
            }, $filePath);

            if (empty($data) || empty($data[0])) {
                $this->error('Excel file is empty or invalid.');
                return 1;
            }

            $rows = $data[0]; // Get first sheet

            // Remove header row (first row)
            array_shift($rows);

            if (empty($rows)) {
                $this->error('No data rows found in Excel file.');
                return 1;
            }

            $this->info('Found ' . count($rows) . ' rows to process.');

            $successCount = 0;
            $errorCount = 0;
            $skippedCount = 0;
            $errors = [];

            // Process each row
            $progressBar = $this->output->createProgressBar(count($rows));
            $progressBar->start();

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

                    // Find stores by cluster code and sales point code
                    $stores = Store::where('cluster_code', $clusterCode)
                        ->where('sales_point_code', $salesPointCode)
                        ->get();

                    if ($stores->isEmpty()) {
                        throw new \Exception("No stores found with cluster code: {$clusterCode} and sales point code: {$salesPointCode}");
                    }

                    // Select priority store if multiple matches
                    $store = $this->selectPriorityStore($stores);

                    if ($stores->count() > 1) {
                        $this->warn("Multiple stores found for cluster: {$clusterCode}, sales point: {$salesPointCode}. Selected store ID: {$store->id} (type: {$store->store_type}) at row {$rowNumber}");
                    }

                    // Skip if JBMIS code matches current store data
                    if ($store->jbmis_code === $jbmisCode) {
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

                    // Create store history entry
                    StoreHistory::create([
                        'store_id' => $store->id,
                        'user_id' => null, // System migration
                        'field' => 'jbmis_code',
                        'old_value' => null,
                        'new_value' => $jbmisCode,
                        'effective_at' => $effectivityDate,
                        'created_at' => $effectivityDate,
                        'updated_at' => now(),
                    ]);

                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Row {$rowNumber}: " . $e->getMessage();
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();

            // Display results
            $this->info("Migration completed!");
            $this->info("Successfully processed: {$successCount} records");

            if ($skippedCount > 0) {
                $this->info("Skipped (unchanged data): {$skippedCount} records");
            }

            if ($errorCount > 0) {
                $this->warn("Failed to process: {$errorCount} records");
                $this->newLine();
                $this->error("Errors:");
                foreach ($errors as $error) {
                    $this->line("  - {$error}");
                }
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Failed to read Excel file: " . $e->getMessage());
            return 1;
        }
    }
}
