<?php

namespace App\Console\Commands\Royalty;

use App\Jobs\Royalty\MigrateRoyaltyFileJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateRoyaltyFilesCommand extends Command
{
    protected $signature = 'royalty:migrate-files';
    protected $description = 'Migrate royalty files from local storage - processes MNSR and Royalty workbook pairs';

    public function handle()
    {
        $this->info('Starting royalty files migration...');

        // Define the migration directory path
        $migrationPath = storage_path('app/private/royalty/migration');
        
        if (!is_dir($migrationPath)) {
            $this->error("Migration directory does not exist: {$migrationPath}");
            return 1;
        }

        // Scan for Excel files
        $files = scandir($migrationPath);
        $mnsrFiles = [];
        $royaltyFiles = [];

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) !== 'xlsx') {
                continue;
            }

            // Check if it's an MNSR file (Monthly-Natl-Sales-Rept-MMM-YYYY.xlsx)
            if (preg_match('/^Monthly-Natl-Sales-Rept-([A-Za-z]{3})-(\d{4})\.xlsx$/i', $file, $matches)) {
                $month = $matches[1];
                $year = $matches[2];
                $mnsrFiles["{$year}-{$month}"] = $file;
            }
            // Check if it's a Royalty workbook (Royalty-Workbook-YYYY-MMM.xlsx)
            elseif (preg_match('/^Royalty-Workbook-(\d{4})-([A-Za-z]{3})\.xlsx$/i', $file, $matches)) {
                $year = $matches[1];
                $month = $matches[2];
                $royaltyFiles["{$year}-{$month}"] = $file;
            }
        }

        // Find paired files
        $pairs = [];
        foreach ($mnsrFiles as $key => $mnsrFile) {
            if (isset($royaltyFiles[$key])) {
                $pairs[$key] = [
                    'mnsr' => $mnsrFile,
                    'royalty' => $royaltyFiles[$key]
                ];
            }
        }

        // Log unpaired files
        foreach ($mnsrFiles as $key => $file) {
            if (!isset($pairs[$key])) {
                $this->warn("Skipping unpaired MNSR file: {$file}");
            }
        }
        foreach ($royaltyFiles as $key => $file) {
            if (!isset($pairs[$key])) {
                $this->warn("Skipping unpaired Royalty file: {$file}");
            }
        }

        if (empty($pairs)) {
            $this->info('No valid file pairs found to process.');
            return 0;
        }

        // Process each pair
        $this->info('Found ' . count($pairs) . ' file pair(s) to process.');
        
        foreach ($pairs as $key => $filePair) {
            [$year, $monthAbbrev] = explode('-', $key);
            
            // Convert month abbreviation to month number using proper mapping
            $monthMap = [
                'Jan' => 1, 'Feb' => 2, 'Mar' => 3, 'Apr' => 4,
                'May' => 5, 'Jun' => 6, 'Jul' => 7, 'Aug' => 8,
                'Sep' => 9, 'Oct' => 10, 'Nov' => 11, 'Dec' => 12
            ];
            
            $month = $monthMap[$monthAbbrev] ?? null;
            
            if (!$month) {
                $this->error("Invalid month abbreviation: {$monthAbbrev}");
                continue;
            }
            
            $this->info("Dispatching job for {$monthAbbrev} {$year} (month: {$month})...");
            
            // Dispatch job with full file paths
            dispatch(new MigrateRoyaltyFileJob(
                $migrationPath . '/' . $filePair['mnsr'],
                $migrationPath . '/' . $filePair['royalty'],
                (int) $month,
                (int) $year
            ));
        }

        $this->info('Migration jobs dispatched successfully.');
        return 0;
    }
}
