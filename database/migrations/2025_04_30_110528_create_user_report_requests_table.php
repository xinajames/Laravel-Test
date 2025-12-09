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
        Schema::create('user_report_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('report_type');
            $table->string('report_name');
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->text('filter_data')->nullable();
            $table->string('status')->default(\App\Enums\UserReportRequestStatusEnum::Pending()->value);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_report_requests');
    }
};
