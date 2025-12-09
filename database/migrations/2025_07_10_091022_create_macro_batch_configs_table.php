<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('macro_batch_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->references('id')->on('macro_batches');
            $table->boolean('has_uploaded_mnsr')->default(false);
            $table->boolean('gen_mnsr');
            $table->boolean('gen_rwb');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('macro_batch_configs');
    }
};
