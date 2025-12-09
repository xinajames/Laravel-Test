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
        Schema::create('store_ratings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')->constrained();

            $table->string('reviewer_id')->nullable();
            $table->decimal('overall_rating', 3)->default(0);
            $table->json('ratings_per_type')->nullable();
            $table->date('rated_at')->nullable();
            $table->string('step')->default('authorized-products');
            $table->boolean('is_draft')->default(true);
            $table->foreignId('created_by_id')->nullable()->constrained('users');
            $table->foreignId('updated_by_id')->nullable()->constrained('users');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_ratings');
    }
};
