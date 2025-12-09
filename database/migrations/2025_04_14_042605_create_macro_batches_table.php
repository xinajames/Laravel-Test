<?php

use App\Enums\MacroBatchStatusEnum;
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
        Schema::create('macro_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('code')->index();
            $table->string('title', 256)->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedInteger('status')->default(MacroBatchStatusEnum::Pending()->value);
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('macro_batches');
    }
};
