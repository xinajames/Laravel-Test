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
        Schema::create('store_rating_questionnaires', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_rating_id')->constrained('store_ratings');
            $table->foreignId('questionnaire_id')->constrained('questionnaires');

            $table->text('question');
            $table->integer('order')->nullable();
            $table->string('answer')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_rating_questionnaires');
    }
};
