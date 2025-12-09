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
        Schema::create('sales_performance_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_performance_id')->references('id')->on('sales_performances');
            $table->string('region');
            $table->unsignedInteger('year');
            $table->unsignedInteger('month');
            $table->decimal('bread', 14, 2);
            $table->decimal('non_bread', 14, 2);
            $table->decimal('combined', 14, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_performance_details');
    }
};
