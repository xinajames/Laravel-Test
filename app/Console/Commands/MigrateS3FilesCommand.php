<?php

namespace App\Console\Commands;

use App\Models\Royalty\MacroUpload;
use App\Models\Royalty\MacroOutput;
use App\Models\Royalty\MacroFixedCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class MigrateS3FilesCommand extends Command
{
    protected $signature = 'migrate:s3-files 
                            {--dry-run : Run in dry-run mode to see what would be done}
                            {--batch-size=100 : Number of records to process at once}';

    protected $description = 'Migrate S3 files from /jform-uat-filesystem to /jform-local-filesystem prefix';

    private int $totalFiles = 0;
    private int $copiedFiles = 0;
    private int $skippedFiles = 0;
    private int $failedFiles = 0;

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');

        if ($dryRun) {
            $this->info('🔍 Running in DRY-RUN mode - no files will be copied or database records updated');
        }

        $this->info('Starting S3 file migration...');
        $this->newLine();

        // Process MacroUpload files
        $this->line('📁 Processing MacroUpload files...');
        $this->processMacroUploads($dryRun, $batchSize);

        $this->newLine();

        // Process MacroOutput files  
        $this->line('📁 Processing MacroOutput files...');
        $this->processMacroOutputs($dryRun, $batchSize);

        $this->newLine();

        // Process MacroFixedCache files
        $this->line('📁 Processing MacroFixedCache files...');
        $this->processMacroFixedCache($dryRun, $batchSize);

        $this->newLine();
        $this->displaySummary();
    }

    private function processMacroUploads(bool $dryRun, int $batchSize): void
    {
        $query = MacroUpload::where(function ($query) {
            $query->where('file_path', 'like', '/jform-uat-filesystem%')
                  ->orWhere('cached_path', 'like', '/jform-uat-filesystem%');
        });

        $totalRecords = $query->count();
        $this->info("Found {$totalRecords} MacroUpload records to process");

        if ($totalRecords === 0) {
            return;
        }

        $processedRecords = 0;
        $query->chunk($batchSize, function ($uploads) use ($dryRun, &$processedRecords, $totalRecords) {
            foreach ($uploads as $upload) {
                $this->processRecord($upload, $dryRun, 'MacroUpload');
                $processedRecords++;
                
                if ($processedRecords % 50 === 0) {
                    $this->info("Processed {$processedRecords}/{$totalRecords} MacroUpload records");
                }
            }
        });
    }

    private function processMacroOutputs(bool $dryRun, int $batchSize): void
    {
        $query = MacroOutput::where(function ($query) {
            $query->where('file_path', 'like', '/jform-uat-filesystem%')
                  ->orWhere('cached_path', 'like', '/jform-uat-filesystem%');
        });

        $totalRecords = $query->count();
        $this->info("Found {$totalRecords} MacroOutput records to process");

        if ($totalRecords === 0) {
            return;
        }

        $processedRecords = 0;
        $query->chunk($batchSize, function ($outputs) use ($dryRun, &$processedRecords, $totalRecords) {
            foreach ($outputs as $output) {
                $this->processRecord($output, $dryRun, 'MacroOutput');
                $processedRecords++;
                
                if ($processedRecords % 50 === 0) {
                    $this->info("Processed {$processedRecords}/{$totalRecords} MacroOutput records");
                }
            }
        });
    }

    private function processRecord($record, bool $dryRun, string $modelType): void
    {
        $updates = [];
        $filesToCopy = [];

        // Process file_path
        if ($record->file_path && str_starts_with($record->file_path, '/jform-uat-filesystem')) {
            $newFilePath = str_replace('/jform-uat-filesystem', '/jform-local-filesystem', $record->file_path);
            $updates['file_path'] = $newFilePath;
            $filesToCopy[] = [
                'source' => ltrim($record->file_path, '/'),
                'destination' => ltrim($newFilePath, '/'),
                'field' => 'file_path'
            ];
        }

        // Process cached_path
        if ($record->cached_path && str_starts_with($record->cached_path, '/jform-uat-filesystem')) {
            $newCachedPath = str_replace('/jform-uat-filesystem', '/jform-local-filesystem', $record->cached_path);
            $updates['cached_path'] = $newCachedPath;
            $filesToCopy[] = [
                'source' => ltrim($record->cached_path, '/'),
                'destination' => ltrim($newCachedPath, '/'),
                'field' => 'cached_path'
            ];
        }

        if (empty($filesToCopy)) {
            return;
        }

        $this->totalFiles += count($filesToCopy);
        
        foreach ($filesToCopy as $fileInfo) {
            $this->line("  📄 {$modelType} ID:{$record->id} - {$fileInfo['field']}");
            $this->line("      Source: {$fileInfo['source']}");
            $this->line("      Destination: {$fileInfo['destination']}");

            if ($dryRun) {
                $this->line("      [DRY-RUN] Would copy file");
                $this->skippedFiles++;
                continue;
            }

            // Check if source file exists
            if (!Storage::disk('s3')->exists($fileInfo['source'])) {
                $this->warn("      ⚠️  Source file does not exist, skipping");
                $this->skippedFiles++;
                continue;
            }

            // Check if destination already exists
            if (Storage::disk('s3')->exists($fileInfo['destination'])) {
                $this->line("      ✅ Destination file already exists, skipping copy");
                $this->skippedFiles++;
                continue;
            }

            // Copy the file
            try {
                $fileContents = Storage::disk('s3')->get($fileInfo['source']);
                Storage::disk('s3')->put($fileInfo['destination'], $fileContents);
                $this->line("      ✅ File copied successfully");
                $this->copiedFiles++;
            } catch (\Exception $e) {
                $this->error("      ❌ Failed to copy file: " . $e->getMessage());
                $this->failedFiles++;
                continue;
            }
        }

        // Update database record if not in dry-run mode and we have updates
        if (!$dryRun && !empty($updates)) {
            try {
                $record->update($updates);
                $this->line("      📝 Database record updated");
            } catch (\Exception $e) {
                $this->error("      ❌ Failed to update database record: " . $e->getMessage());
            }
        } elseif ($dryRun && !empty($updates)) {
            $this->line("      [DRY-RUN] Would update database record");
        }

        $this->newLine();
    }

    private function processMacroFixedCache(bool $dryRun, int $batchSize): void
    {
        $query = MacroFixedCache::where('cached_path', 'like', '/jform-uat-filesystem%');

        $totalRecords = $query->count();
        $this->info("Found {$totalRecords} MacroFixedCache records to process");

        if ($totalRecords === 0) {
            return;
        }

        $processedRecords = 0;
        $query->chunk($batchSize, function ($caches) use ($dryRun, &$processedRecords, $totalRecords) {
            foreach ($caches as $cache) {
                $this->processRecord($cache, $dryRun, 'MacroFixedCache');
                $processedRecords++;
                
                if ($processedRecords % 50 === 0) {
                    $this->info("Processed {$processedRecords}/{$totalRecords} MacroFixedCache records");
                }
            }
        });
    }

    private function displaySummary(): void
    {
        $this->info('📊 Migration Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total files processed', $this->totalFiles],
                ['Files copied successfully', $this->copiedFiles],
                ['Files skipped', $this->skippedFiles],
                ['Files failed', $this->failedFiles],
            ]
        );

        if ($this->failedFiles > 0) {
            $this->warn("⚠️  {$this->failedFiles} files failed to copy. Please review the logs above.");
        } else {
            $this->info('✅ Migration completed successfully!');
        }
    }
}