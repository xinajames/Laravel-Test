<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('store_histories', function (Blueprint $table) {
            $table->timestamp('effective_at')->nullable()->after('new_value');
            $table->index(['store_id', 'field', 'effective_at']);
            $table->index(['field', 'effective_at']); // For batch querying
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_histories', function (Blueprint $table) {
            $table->dropIndex(['field', 'effective_at']);
            $table->dropIndex(['store_id', 'field', 'effective_at']);
            $table->dropColumn('effective_at');
        });
    }
};
