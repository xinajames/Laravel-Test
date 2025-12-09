<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class UploadRoyaltyToS3Command extends Command
{
    protected $signature = 'upload:royalty-to-s3 
                            {--dry-run : Run in dry-run mode to see what would be uploaded}
                            {--batch-size=100 : Number of files to process at once}
                            {--skip-existing : Skip files that already exist on S3 instead of overwriting}';

    protected $description = 'Upload local royalty files to S3 storage (overwrites existing files by default)';

    private int $totalFiles = 0;
    private int $uploadedFiles = 0;
    private int $skippedFiles = 0;
    private int $failedFiles = 0;

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');

        if ($dryRun) {
            $this->info('🔍 Running in DRY-RUN mode - no files will be uploaded');
        }

        $this->info('Starting royalty files upload to S3...');
        $this->newLine();

        // Get environment-based prefix
        $env = config('app.env');
        $sourcePrefix = "jform-{$env}-filesystem/royalty";

        $skipExisting = $this->option('skip-existing');
        
        $this->line("📁 Processing royalty files from: {$sourcePrefix}");
        if (!$skipExisting) {
            $this->line("⚠️  Files will be overwritten if they already exist on S3");
        }
        $this->processRoyaltyFiles($sourcePrefix, $dryRun, $batchSize, $skipExisting);

        $this->newLine();
        $this->displaySummary();
    }

    private function processRoyaltyFiles(string $sourcePrefix, bool $dryRun, int $batchSize, bool $skipExisting): void
    {
        $localDisk = Storage::disk('local');
        $s3Disk = Storage::disk('s3');

        // Check if source directory exists
        if (!$localDisk->exists($sourcePrefix)) {
            $this->error("❌ Source directory does not exist: {$sourcePrefix}");
            return;
        }

        // Get all files recursively from the royalty directory
        $allFiles = $localDisk->allFiles($sourcePrefix);
        $this->totalFiles = count($allFiles);

        $this->info("Found {$this->totalFiles} files to process");

        if ($this->totalFiles === 0) {
            $this->info('No files found to upload');
            return;
        }

        $processedFiles = 0;
        $fileChunks = array_chunk($allFiles, $batchSize);

        foreach ($fileChunks as $chunk) {
            foreach ($chunk as $file) {
                $this->processFile($file, $localDisk, $s3Disk, $dryRun, $skipExisting);
                $processedFiles++;

                if ($processedFiles % 50 === 0) {
                    $this->info("Processed {$processedFiles}/{$this->totalFiles} files");
                }
            }
        }
    }

    private function processFile(string $file, $localDisk, $s3Disk, bool $dryRun, bool $skipExisting): void
    {
        $this->line("  📄 {$file}");

        if ($dryRun) {
            $this->line("      [DRY-RUN] Would upload file to S3");
            $this->skippedFiles++;
            return;
        }

        // Check if we should skip existing files
        if ($skipExisting && $s3Disk->exists($file)) {
            $this->line("      ⚠️  File already exists on S3, skipping");
            $this->skippedFiles++;
            return;
        }

        // Check if source file still exists (safety check)
        if (!$localDisk->exists($file)) {
            $this->warn("      ❌ Source file no longer exists, skipping");
            $this->failedFiles++;
            return;
        }

        // Upload the file
        try {
            $fileExists = $s3Disk->exists($file);
            $fileContents = $localDisk->get($file);
            $s3Disk->put($file, $fileContents);
            
            if ($fileExists) {
                $this->line("      ✅ File overwritten successfully");
            } else {
                $this->line("      ✅ File uploaded successfully");
            }
            $this->uploadedFiles++;
        } catch (\Exception $e) {
            $this->error("      ❌ Failed to upload file: " . $e->getMessage());
            $this->failedFiles++;
        }

        $this->newLine();
    }

    private function displaySummary(): void
    {
        $this->info('📊 Upload Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total files processed', $this->totalFiles],
                ['Files uploaded successfully', $this->uploadedFiles],
                ['Files skipped', $this->skippedFiles],
                ['Files failed', $this->failedFiles],
            ]
        );

        if ($this->failedFiles > 0) {
            $this->warn("⚠️  {$this->failedFiles} files failed to upload. Please review the logs above.");
        } else {
            $this->info('✅ Upload completed successfully!');
        }
    }
}