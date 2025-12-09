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
            $table->dropUnique(['email']);
            $table->dropUnique(['email_2']);
            $table->dropUnique(['email_3']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('franchisees', function (Blueprint $table) {
            $table->unique('email');
            $table->unique('email_2');
            $table->unique('email_3');
        });
    }
};
