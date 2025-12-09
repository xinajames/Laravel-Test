<?php

namespace App\Console\Commands\SalesPerformance;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Command\Command as CommandAlias;

class ResetSalesPerformanceDataCommand extends Command
{
    protected $signature = 'sales-performance:reset-data';

    protected $description = 'Reset Sales Performance Data by dropping sales_performance_* tables and re-running migrations';

    public function handle()
    {
        $this->info('Starting sales performance data reset...');

        // Step 1: Drop all sales_performance_* tables
        $this->dropSalesPerformanceTables();
        $this->removeSalesPerformanceMigrationRecords();

        // Step 2: Run migrations
        $this->info('🔄 Running migrations...');
        Artisan::call('migrate');
        $this->info('✅ Migrations completed.');

        // Step 3: Run seeder
        $this->info('🌱 Running seeder...');
        Artisan::call('db:seed --class=SalesPerformanceSeeder');
        $this->info('✅ Seeder completed.');

        $this->info('✅ Sales performance data reset completed successfully!');

        return CommandAlias::SUCCESS;
    }

    private function dropSalesPerformanceTables()
    {
        $this->info('🗑️  Dropping sales performance tables...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tables = DB::select('SHOW TABLES');
        $databaseName = DB::getDatabaseName();
        $tableKey = "Tables_in_{$databaseName}";

        foreach ($tables as $table) {
            $tableName = $table->{$tableKey};
            if (str_starts_with($tableName, 'sales_performance_') || $tableName === 'sales_performances') {
                $this->line("  Dropping: {$tableName}");
                DB::statement("DROP TABLE IF EXISTS `{$tableName}`");
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $this->info('✅ Sales performance tables dropped.');
    }

    private function removeSalesPerformanceMigrationRecords()
    {
        $this->info('🧹 Removing sales performance migration records...');

        $deletedCount = DB::table('migrations')
            ->where(function ($query) {
                $query->where('migration', 'LIKE', '%sales_performance%')
                    ->orWhere('migration', 'LIKE', '%sales_performances%');
            })
            ->delete();

        $this->info("✅ Removed {$deletedCount} sales performance migration records.");
    }
}
