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
        Schema::table('franchisees', function (Blueprint $table) {
            $table->string('custom_background')->nullable()->after('background');
            $table->string('custom_source_of_information')->nullable()->after('source_of_information');
            $table->string('custom_generation')->nullable()->after('generation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('franchisees', function (Blueprint $table) {
            $table->dropColumn('custom_background');
            $table->dropColumn('custom_source_of_information');
            $table->dropColumn('custom_generation');
        });
    }
};
