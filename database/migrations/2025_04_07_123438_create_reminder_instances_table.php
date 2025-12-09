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
        Schema::create('reminder_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reminder_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('remindable'); // remindable_type & remindable_id (e.g. Store, Franchisee)
            $table->string('reference_date_field')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('days_before')->nullable();
            $table->unsignedInteger('notify_number')->nullable();
            $table->string('notify_unit')->nullable();
            $table->boolean('is_custom')->default(false);
            $table->boolean('is_enabled')->default(true);
            $table->date('scheduled_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('last_notified_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminder_instances');
    }
};
