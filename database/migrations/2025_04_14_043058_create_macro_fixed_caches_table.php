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
        Schema::create('macro_fixed_caches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->references('id')->on('macro_batches')->onDelete('cascade');
            $table->foreignId('file_type_id')->references('id')->on('macro_file_types');
            $table->foreignId('file_revision_id')->references('id')->on('macro_file_revisions');
            $table->text('cached_path');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('macro_fixed_caches');
    }
};
