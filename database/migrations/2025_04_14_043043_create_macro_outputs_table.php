<?php

use App\Enums\MacroOutputStatusEnum;
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
        Schema::create('macro_outputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->references('id')->on('macro_batches')->onDelete('cascade');
            $table->foreignId('step_id')->nullable()->references('id')->on('macro_steps')->onDelete('cascade');
            $table->unsignedInteger('status')->default(MacroOutputStatusEnum::Pending()->value);
            $table->text('file_name');
            $table->foreignId('file_type_id')->references('id')->on('macro_file_types')->onDelete('cascade');
            $table->foreignId('file_revision_id')->references('id')->on('macro_file_revisions')->onDelete('cascade');
            $table->text('file_path')->nullable();
            $table->text('cached_path')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('macro_outputs');
    }
};
