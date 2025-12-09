<?php

namespace App\Console\Commands\Royalty;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Command\Command as CommandAlias;

class ResetRoyaltyDataCommand extends Command
{
    protected $signature = 'royalty:reset-data';

    protected $description = 'Reset Royalty Data by dropping macro_* and sales_performance* tables and re-running migrations';

    public function handle()
    {
        $this->info('Starting royalty data reset...');

        // Step 1: Drop all macro_* and sales_performance* tables
        $this->dropMacroTables();
        $this->removeMacroMigrationRecords();
        $this->dropSalesPerformanceTables();
        $this->removeSalesPerformanceMigrationRecords();

        // Step 2: Run migrations
        $this->info('🔄 Running migrations...');
        Artisan::call('migrate');
        $this->info('✅ Migrations completed.');

        // Step 3: Run seeders
        $this->info('🌱 Running seeders...');
        Artisan::call('db:seed --class=MacroFileTypeAndRevisionSeeder');
        Artisan::call('db:seed --class=MacroFixedCacheSeeder');
        Artisan::call('db:seed --class=SalesPerformanceSeeder');
        $this->info('✅ Seeders completed.');

        $this->info('✅ Royalty data reset completed successfully!');

        return CommandAlias::SUCCESS;
    }

    private function dropMacroTables()
    {
        $this->info('🗑️  Dropping macro_* tables...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tables = DB::select('SHOW TABLES');
        $databaseName = DB::getDatabaseName();
        $tableKey = "Tables_in_{$databaseName}";

        foreach ($tables as $table) {
            $tableName = $table->{$tableKey};

            if (str_starts_with($tableName, 'macro_')) {
                $this->line("  Dropping: {$tableName}");
                DB::statement("DROP TABLE IF EXISTS `{$tableName}`");
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $this->info('✅ Macro tables dropped.');
    }

    private function removeMacroMigrationRecords()
    {
        $this->info('🧹 Removing macro migration records...');

        $deletedCount = DB::table('migrations')
            ->where('migration', 'LIKE', '%macro%')
            ->delete();

        $this->info("✅ Removed {$deletedCount} macro migration records.");
    }

    private function dropSalesPerformanceTables()
    {
        $this->info('🗑️  Dropping sales_performance* tables...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tables = DB::select('SHOW TABLES');
        $databaseName = DB::getDatabaseName();
        $tableKey = "Tables_in_{$databaseName}";

        foreach ($tables as $table) {
            $tableName = $table->{$tableKey};

            if (str_starts_with($tableName, 'sales_performance')) {
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
            ->where('migration', 'LIKE', '%sales_performance%')
            ->delete();

        $this->info("✅ Removed {$deletedCount} sales performance migration records.");
    }
}
