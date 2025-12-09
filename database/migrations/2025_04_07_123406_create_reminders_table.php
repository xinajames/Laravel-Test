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
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('reference_date_field')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('days_before')->nullable();
            $table->unsignedInteger('notify_number')->nullable();
            $table->string('notify_unit')->nullable();
            $table->string('type')->default(0);
            $table->boolean('is_enabled')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
