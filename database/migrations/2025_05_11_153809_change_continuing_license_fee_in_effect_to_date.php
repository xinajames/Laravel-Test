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
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('continuing_license_fee_in_effect');
            $table->date('continuing_license_fee_in_effect')->nullable()->after('current_continuing_license_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('continuing_license_fee_in_effect');
            $table->integer('continuing_license_fee_in_effect')->nullable()->after('current_continuing_license_fee');
        });
    }
};
