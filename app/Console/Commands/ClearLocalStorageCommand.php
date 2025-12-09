<?php

namespace App\Console\Commands;

use App\Traits\ManageFilesystems;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ClearLocalStorageCommand extends Command
{
    use ManageFilesystems;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:clear 
                            {--force : Force the operation without confirmation}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all uploaded files from the configured upload disk';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $uploadDisk = $this->getDefaultUploadDisk();
        $basePath = $this->generateUploadBasePath();

        // Warning for all environments about disk usage
        $this->warn("⚠️  You are about to delete ALL files in: {$basePath}");
        $this->warn("🗂️  Using disk: {$uploadDisk}");
        $this->newLine();

        // Enhanced production safety check
        if (app()->environment('production')) {
            $this->error('🚨 DANGER: This command is running in PRODUCTION environment!');
            $this->error("This will DELETE ALL files in the upload base path: {$basePath}");
            $this->error("Using disk: {$uploadDisk}");
            $this->error('This action is PERMANENT and CANNOT be undone!');
            $this->newLine();

            if (! $this->option('force')) {
                $confirmed = $this->confirm('Are you absolutely sure you want to continue? This will delete ALL uploaded files.');
                if (! $confirmed) {
                    $this->info('Operation cancelled.');

                    return 0;
                }

                // Double confirmation for production
                $this->error('Final confirmation required. This will permanently delete ALL files in:');
                $this->error($basePath);
                $this->newLine();

                if ($this->ask('Type exactly "DELETE ALL FILES" to confirm (case-sensitive)') !== 'DELETE ALL FILES') {
                    $this->info('Operation cancelled. Confirmation failed.');

                    return 0;
                }
            }
        } else {
            // Non-production confirmation
            if (! $this->option('force')) {
                $confirmed = $this->confirm("Continue with deleting all files in {$basePath}?");
                if (! $confirmed) {
                    $this->info('Operation cancelled.');

                    return 0;
                }
            }
        }

        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No files will actually be deleted');
            $this->newLine();
        }

        $this->info("🧹 Clearing files from upload disk: {$uploadDisk}");
        $this->info("📁 Target path: {$basePath}");
        $this->newLine();

        $result = $this->clearDirectory($basePath, $isDryRun, $uploadDisk);

        $this->newLine();

        if ($isDryRun) {
            $this->info('📊 Summary (DRY RUN):');
            $this->info("Would delete: {$result['files']} files");
            $this->info('Would free: '.$this->formatBytes($result['size']));
        } else {
            $this->info('✅ Clear operation completed!');
            $this->info("Deleted: {$result['files']} files");
            $this->info('Freed: '.$this->formatBytes($result['size']));
        }

        if (! empty($result['errors'])) {
            $this->newLine();
            $this->error('⚠️  Some errors occurred:');
            foreach ($result['errors'] as $error) {
                $this->error("  - {$error}");
            }
        }

        return 0;
    }

    /**
     * Clear a specific directory
     */
    private function clearDirectory(string $directory, bool $isDryRun = false, ?string $diskName = null): array
    {
        $diskName = $diskName ?? $this->getDefaultUploadDisk();
        $disk = Storage::disk($diskName);
        $files = 0;
        $size = 0;
        $errors = [];

        if (! $disk->exists($directory)) {
            $this->line("📁 {$directory} - Directory doesn't exist on {$diskName} disk, nothing to clear");

            return ['files' => 0, 'size' => 0, 'errors' => []];
        }

        try {
            $allFiles = $disk->allFiles($directory);
            $directorySize = 0;

            // Calculate size and count
            foreach ($allFiles as $file) {
                try {
                    $directorySize += $disk->size($file);
                } catch (\Exception $e) {
                    $errors[] = "Could not get size for: {$file} on {$diskName} disk";
                }
            }

            $files = count($allFiles);
            $size = $directorySize;

            if ($isDryRun) {
                $this->line("📁 {$directory} - Would delete {$files} files (".$this->formatBytes($size).") from {$diskName} disk");
            } else {
                // Actually delete the entire directory
                if ($files > 0) {
                    $disk->deleteDirectory($directory);
                    $this->line("📁 {$directory} - Deleted {$files} files (".$this->formatBytes($size).") from {$diskName} disk");
                } else {
                    $this->line("📁 {$directory} - Directory was empty on {$diskName} disk");
                }
            }

        } catch (\Exception $e) {
            $error = "Error processing {$directory} on {$diskName} disk: ".$e->getMessage();
            $errors[] = $error;
            $this->error("❌ {$error}");
        }

        return [
            'files' => $files,
            'size' => $size,
            'errors' => $errors,
        ];
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}
