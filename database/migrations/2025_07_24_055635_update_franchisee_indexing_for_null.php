<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Only apply this migration if the default database connection is sqlsrv
        if (config('database.default') !== 'sqlsrv') {
            return;
        }

        // Drop the specific constraint by its exact name from the error message
        DB::statement('DROP INDEX franchisees_franchisee_code_unique ON franchisees');

        // Create filtered unique index for franchisee_code only
        DB::statement('CREATE UNIQUE INDEX ix_franchisees_franchisee_code ON franchisees(franchisee_code) WHERE franchisee_code IS NOT NULL');
    }

    public function down(): void
    {
        // Only apply this migration rollback if the default database connection is sqlsrv
        if (config('database.default') !== 'sqlsrv') {
            return;
        }

        // Drop the filtered index
        DB::statement('DROP INDEX ix_franchisees_franchisee_code ON franchisees');

        // Recreate original constraint
        DB::statement('CREATE UNIQUE INDEX franchisees_franchisee_code_unique ON franchisees(franchisee_code)');
    }
};
