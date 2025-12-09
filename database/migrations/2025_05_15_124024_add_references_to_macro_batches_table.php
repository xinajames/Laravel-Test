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
        Schema::table('macro_batches', function (Blueprint $table) {
            $table->unsignedInteger('month')->after('user_id')->nullable();
            $table->unsignedInteger('year')->after('month')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('macro_batches', function (Blueprint $table) {
            $table->dropColumn('month');
            $table->dropColumn('year');
        });
    }
};
