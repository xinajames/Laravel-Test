<?php

namespace App\Console\Commands;

use App\Models\Franchisee;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckMissingFranchiseeCodesCommand extends Command
{
    protected $signature = 'franchisee:check-missing-codes {--fix : Automatically generate missing codes}';

    protected $description = 'Check for franchisees missing codes (non-draft, created from Jul 23, 2025)';

    public function handle()
    {
        $this->info('Checking for franchisees with missing codes...');

        // Find franchisees missing codes from July 23, 2025 onwards
        $franchisees = Franchisee::whereNull('franchisee_code')
            ->where('is_draft', false)
            // ->where('created_at', '>=', Carbon::create(2025, 7, 23))
            ->orderBy('created_at', 'asc')
            ->get();

        if ($franchisees->isEmpty()) {
            $this->info('✅ No franchisees found with missing codes.');

            return 0;
        }

        $this->warn("Found {$franchisees->count()} franchisee(s) with missing codes:");
        $this->newLine();

        // Display results in a table
        $tableData = $franchisees->map(function ($franchisee) {
            return [
                'ID' => $franchisee->id,
                'Name' => $franchisee->full_name ?? "{$franchisee->first_name} {$franchisee->last_name}",
                'Email' => $franchisee->email,
                'Created' => $franchisee->created_at->format('Y-m-d H:i:s'),
                'Status' => $franchisee->status,
                'Draft' => $franchisee->is_draft ? 'Yes' : 'No',
            ];
        })->toArray();

        $this->table(
            ['ID', 'Name', 'Email', 'Created', 'Status', 'Draft'],
            $tableData
        );

        // Option to fix the issues
        if ($this->option('fix')) {
            $this->fixMissingCodes($franchisees);
        } else {
            $this->newLine();
            $this->info('Run with --fix option to automatically generate missing codes:');
            $this->comment('php artisan franchisee:check-missing-codes --fix');
        }

        return 0;
    }

    private function fixMissingCodes($franchisees)
    {
        $this->newLine();

        if (! $this->confirm('Generate missing franchisee codes for these records?')) {
            $this->info('Operation cancelled.');

            return;
        }

        $fixed = 0;
        $errors = 0;

        foreach ($franchisees as $franchisee) {
            try {
                DB::transaction(function () use ($franchisee) {
                    // Use the same logic as the service
                    $nextCode = $this->generateFranchiseeCode();
                    $franchisee->update(['franchisee_code' => $nextCode]);

                    $this->info("✅ Generated code {$nextCode} for franchisee ID {$franchisee->id}");
                });

                $fixed++;
            } catch (Exception $e) {
                $this->error("❌ Failed to generate code for franchisee ID {$franchisee->id}: {$e->getMessage()}");
                $errors++;
            }
        }

        $this->newLine();
        $this->info("✅ Successfully fixed: {$fixed} franchisees");
        if ($errors > 0) {
            $this->warn("⚠️  Errors: {$errors} franchisees (each failed individually)");
        }
    }

    /**
     * @throws Exception
     */
    private function generateFranchiseeCode(): string
    {
        $latest = Franchisee::whereNotNull('franchisee_code')
            ->where('is_draft', false)
            ->orderByDesc('franchisee_code')
            ->first();

        if ($latest && preg_match('/^F(\d+)$/', $latest->franchisee_code, $matches)) {
            $lastNumber = (int) $matches[1];
            $nextNumber = max($lastNumber + 1, 20000);
        } else {
            $nextNumber = 20000;
        }

        $generatedCode = 'F'.$nextNumber;

        // Verify the generated code is not null or empty
        if (empty($generatedCode) || $generatedCode === 'F' || $nextNumber <= 0) {
            throw new Exception('Failed to generate valid franchisee code');
        }

        // Verify uniqueness before returning
        $exists = Franchisee::where('franchisee_code', $generatedCode)->exists();
        if ($exists) {
            throw new Exception("Franchisee code {$generatedCode} already exists");
        }

        return $generatedCode;
    }
}
