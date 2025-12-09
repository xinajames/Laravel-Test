<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales_performances', function (Blueprint $table) {
            $table->string('by_store_path')->after('path')->nullable();
            $table->string('by_store_cached_path')->after('cached_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_performances', function (Blueprint $table) {
            $table->dropColumn('by_store_path');
            $table->dropColumn('by_store_cached_path');
        });
    }
};
