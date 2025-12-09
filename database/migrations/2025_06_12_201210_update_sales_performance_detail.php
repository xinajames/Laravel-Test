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
        Schema::table('sales_performance_details', function (Blueprint $table) {
            $table->string('store_code')->after('sales_performance_id')->index();
            $table->string('cluster_code')->after('store_code')->index();
            $table->string('franchise_code')->after('cluster_code')->index();
            $table->string('area')->after('region')->index();
            $table->string('district')->after('area')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
