<?php

namespace App\Console\Commands;

use App\Models\Franchisee;
use App\Models\Store;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixInvalidDatesCommand extends Command
{
    protected $signature = 'data:fix-invalid-dates {--fix : Automatically fix invalid dates}';

    protected $description = 'Fix 1970 dates (Unix epoch dates representing null values) in Store and Franchisee models';

    private array $storeDateFields = [
        'date_opened',
        'franchise_date',
        'original_franchise_date',
        'renewal_date',
        'last_renewal_date',
        'effectivity_date',
        'target_opening_date',
        'soft_opening_date',
        'grand_opening_date',
        'cctv_installed_at',
        'internet_installed_at',
        'pos_installed_at',
        'cgl_expiry_date',
        'fire_expiry_date',
        'contract_of_lease_start_date',
        'contract_of_lease_end_date',
        'lease_payment_date',
        'col_notarized_date',
        'maintenance_last_repaint_at',
        'maintenance_last_renovation_at',
        'maintenance_temporary_closed_at',
        'maintenance_reopening_date',
        'maintenance_permanent_closure_date',
        'maintenance_upgrade_date',
        'maintenance_downgrade_date',
        'maintenance_store_acquired_at',
        'maintenance_store_transferred_at',
    ];

    private array $franchiseeDateFields = [
        'birthdate',
        'spouse_birthdate',
        'wedding_date',
        'date_start_bakery_management_seminar',
        'date_end_bakery_management_seminar',
        'date_start_bread_baking_course',
        'date_end_bread_baking_course',
        'date_applied',
        'date_approved',
        'date_separated',
    ];

    public function handle()
    {
        $this->info('Checking for invalid dates (1970-01-01) in Store and Franchisee models...');
        $this->newLine();

        $storeIssues = $this->findInvalidDatesInStores();
        $franchiseeIssues = $this->findInvalidDatesInFranchisees();

        $totalIssues = count($storeIssues) + count($franchiseeIssues);

        if ($totalIssues === 0) {
            $this->info('No invalid dates found in any models.');

            return 0;
        }

        $this->displayIssues($storeIssues, $franchiseeIssues);

        if ($this->option('fix')) {
            $this->fixInvalidDates($storeIssues, $franchiseeIssues);
        } else {
            $this->newLine();
            $this->info('Run with --fix option to automatically fix these invalid dates:');
            $this->comment('php artisan data:fix-invalid-dates --fix');
        }

        return 0;
    }

    private function findInvalidDatesInStores(): array
    {
        $issues = [];
        $epochDate = '1970-01-01';

        foreach ($this->storeDateFields as $field) {
            $stores = Store::where($field, $epochDate)->get();

            if ($stores->isNotEmpty()) {
                $issues[] = [
                    'model' => 'Store',
                    'field' => $field,
                    'count' => $stores->count(),
                    'records' => $stores->map(function ($store) {
                        return [
                            'id' => $store->id,
                            'name' => $store->jbs_name ?? "Store #{$store->id}",
                            'store_code' => $store->store_code,
                        ];
                    })->toArray(),
                ];
            }
        }

        return $issues;
    }

    private function findInvalidDatesInFranchisees(): array
    {
        $issues = [];
        $epochDate = '1970-01-01';

        foreach ($this->franchiseeDateFields as $field) {
            $franchisees = Franchisee::where($field, $epochDate)->get();

            if ($franchisees->isNotEmpty()) {
                $issues[] = [
                    'model' => 'Franchisee',
                    'field' => $field,
                    'count' => $franchisees->count(),
                    'records' => $franchisees->map(function ($franchisee) {
                        return [
                            'id' => $franchisee->id,
                            'name' => $franchisee->full_name ?? "Franchisee #{$franchisee->id}",
                            'franchisee_code' => $franchisee->franchisee_code,
                        ];
                    })->toArray(),
                ];
            }
        }

        return $issues;
    }

    private function displayIssues(array $storeIssues, array $franchiseeIssues): void
    {
        $allIssues = array_merge($storeIssues, $franchiseeIssues);
        $totalIssues = array_sum(array_column($allIssues, 'count'));

        $this->warn("Found {$totalIssues} record(s) with invalid dates:");
        $this->newLine();

        // Display Store issues
        if (! empty($storeIssues)) {
            $this->info('Store Model Issues:');
            foreach ($storeIssues as $issue) {
                $this->line("  • {$issue['field']}: {$issue['count']} record(s)");
            }
            $this->newLine();
        }

        // Display Franchisee issues
        if (! empty($franchiseeIssues)) {
            $this->info('Franchisee Model Issues:');
            foreach ($franchiseeIssues as $issue) {
                $this->line("  • {$issue['field']}: {$issue['count']} record(s)");
            }
            $this->newLine();
        }

        // Show detailed table for first few records
        $this->info('Sample Records with Invalid Dates:');
        $tableData = [];

        foreach (array_slice($allIssues, 0, 5) as $issue) {
            foreach (array_slice($issue['records'], 0, 3) as $record) {
                $tableData[] = [
                    'Model' => $issue['model'],
                    'Field' => $issue['field'],
                    'ID' => $record['id'],
                    'Name' => $record['name'],
                    'Code' => $record['store_code'] ?? $record['franchisee_code'] ?? 'N/A',
                ];
            }
        }

        if (! empty($tableData)) {
            $this->table(
                ['Model', 'Field', 'ID', 'Name', 'Code'],
                $tableData
            );
        }
    }

    private function fixInvalidDates(array $storeIssues, array $franchiseeIssues): void
    {
        $this->newLine();

        if (! $this->confirm('Fix these invalid dates by setting them to null?')) {
            $this->info('Operation cancelled');

            return;
        }

        $fixed = 0;
        $errors = 0;

        // Fix Store issues
        foreach ($storeIssues as $issue) {
            try {
                DB::transaction(function () use ($issue, &$fixed, &$errors) {
                    $updated = Store::where($issue['field'], '1970-01-01')
                        ->update([$issue['field'] => null]);

                    $fixed += $updated;
                    $this->info("Fixed {$updated} Store record(s) for field '{$issue['field']}'");
                });
            } catch (Exception $e) {
                $this->error("Failed to fix Store field '{$issue['field']}': {$e->getMessage()}");
                $errors++;
            }
        }

        // Fix Franchisee issues
        foreach ($franchiseeIssues as $issue) {
            try {
                DB::transaction(function () use ($issue, &$fixed, &$errors) {
                    $updated = Franchisee::where($issue['field'], '1970-01-01')
                        ->update([$issue['field'] => null]);

                    $fixed += $updated;
                    $this->info("Fixed {$updated} Franchisee record(s) for field '{$issue['field']}'");
                });
            } catch (Exception $e) {
                $this->error("Failed to fix Franchisee field '{$issue['field']}': {$e->getMessage()}");
                $errors++;
            }
        }

        $this->newLine();
        $this->info("Successfully fixed: {$fixed} record(s)");
        if ($errors > 0) {
            $this->warn("Errors: {$errors} field(s) failed to update");
        }

        // Verify the fix
        $this->newLine();
        $this->info('Verifying fix...');
        $remainingStoreIssues = $this->findInvalidDatesInStores();
        $remainingFranchiseeIssues = $this->findInvalidDatesInFranchisees();

        $remainingTotal = count($remainingStoreIssues) + count($remainingFranchiseeIssues);

        if ($remainingTotal === 0) {
            $this->info('All invalid dates have been successfully fixed!');
        } else {
            $this->warn("{$remainingTotal} field(s) still have invalid dates. Please check manually.");
        }
    }
}
