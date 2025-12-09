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
        Schema::create('franchisees', function (Blueprint $table) {
            $table->id();

            $table->tinyInteger('status')->nullable();

            $table->string('franchisee_code')->unique()->nullable();
            $table->string('corporation_name')->nullable();
            $table->string('tin')->nullable();

            $table->string('franchisee_profile_photo')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('name_suffix')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('gender')->nullable();
            $table->string('nationality')->nullable();
            $table->string('religion')->nullable();

            $table->string('marital_status')->nullable();
            $table->string('spouse_name')->nullable();
            $table->date('spouse_birthdate')->nullable();
            $table->date('wedding_date')->nullable();
            $table->integer('number_of_children')->nullable();

            $table->string('residential_address_province')->nullable();
            $table->string('residential_address_city')->nullable();
            $table->string('residential_address_barangay')->nullable();
            $table->string('residential_address_street')->nullable();
            $table->string('residential_address_postal')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('contact_number_2')->nullable();
            $table->string('contact_number_3')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('email_2')->unique()->nullable();
            $table->string('email_3')->unique()->nullable();

            $table->string('fm_point_person')->nullable();
            $table->string('fm_district_manager')->nullable();
            $table->string('fm_region')->nullable();
            $table->string('fm_contact_number')->nullable();
            $table->string('fm_contact_number_2')->nullable();
            $table->string('fm_contact_number_3')->nullable();
            $table->string('fm_email_address')->nullable();
            $table->string('fm_email_address_2')->nullable();
            $table->string('fm_email_address_3')->nullable();

            $table->date('date_start_bakery_management_seminar')->nullable();
            $table->date('date_end_bakery_management_seminar')->nullable();
            $table->date('date_start_bread_baking_course')->nullable();
            $table->date('date_end_bread_baking_course')->nullable();
            $table->string('operations_manual_number')->nullable();
            $table->string('operations_manual_release')->nullable();

            $table->date('date_applied')->nullable();
            $table->date('date_approved')->nullable();
            $table->date('date_separated')->nullable();

            $table->string('background')->nullable();
            $table->string('education')->nullable();
            $table->string('course')->nullable();
            $table->string('occupation')->nullable();
            $table->string('source_of_information')->nullable();
            $table->string('legacy')->nullable();
            $table->string('generation')->nullable();

            $table->text('remarks')->nullable();
            $table->boolean('is_draft')->default(true);
            $table->string('application_step')->default('basic-details');

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
        Schema::dropIfExists('franchisees');
    }
};
