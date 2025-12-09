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
        Schema::table('macro_fixed_caches', function (Blueprint $table) {
            $table->foreignId('sales_performance_id')->references('id')->on('sales_performances')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('macro_fixed_caches', function (Blueprint $table) {
            $table->dropColumn('sales_performance_id');
        });
    }
};
