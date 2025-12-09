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
        Schema::create('stores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('franchisee_id')->nullable()->constrained('franchisees');
            $table->string('store_code')->nullable();
            $table->string('store_status')->nullable();
            $table->string('cluster_code')->nullable();
            $table->string('jbmis_code')->nullable();

            $table->string('jbs_name')->nullable();
            $table->string('store_type')->nullable();
            $table->string('store_group')->nullable();
            $table->string('sales_point_code')->nullable();
            $table->string('region')->nullable();
            $table->string('area')->nullable();
            $table->string('district')->nullable();
            $table->text('google_maps_link')->nullable();

            $table->integer('old_continuing_license_fee')->nullable();
            $table->integer('current_continuing_license_fee')->nullable();
            $table->integer('continuing_license_fee_in_effect')->nullable();
            $table->integer('brf_in_effect')->nullable();
            $table->integer('report_percent')->nullable();

            $table->string('om_district_code')->nullable();
            $table->string('om_district_name')->nullable();
            $table->string('om_district_manager')->nullable();
            $table->string('om_cost_center_code')->nullable();

            $table->date('date_opened')->nullable();
            $table->date('franchise_date')->nullable();
            $table->date('original_franchise_date')->nullable();
            $table->date('renewal_date')->nullable();
            $table->date('last_renewal_date')->nullable();
            $table->date('effectivity_date')->nullable();
            $table->date('target_opening_date')->nullable();
            $table->date('soft_opening_date')->nullable();
            $table->date('grand_opening_date')->nullable();

            $table->string('store_province')->nullable();
            $table->string('store_city')->nullable();
            $table->string('store_barangay')->nullable();
            $table->string('store_street')->nullable();
            $table->string('store_postal_code')->nullable();
            $table->string('store_phone_number')->nullable();
            $table->string('store_mobile_number')->nullable();
            $table->string('store_email')->nullable();

            $table->boolean('with_cctv')->nullable();
            $table->date('cctv_installed_at')->nullable();
            $table->boolean('with_internet')->nullable();
            $table->date('internet_installed_at')->nullable();
            $table->boolean('with_pos')->nullable();
            $table->date('pos_installed_at')->nullable();

            $table->string('warehouse')->nullable();
            $table->text('warehouse_remarks')->nullable();

            $table->string('bir_2303')->nullable();
            $table->string('cgl_insurance_policy_number')->nullable();
            $table->date('cgl_expiry_date')->nullable();
            $table->string('fire_insurance_policy_number')->nullable();
            $table->date('fire_expiry_date')->nullable();

            $table->string('area_population')->nullable();
            $table->string('catchment')->nullable();
            $table->string('foot_traffic')->nullable();
            $table->string('manpower')->nullable();
            $table->string('rental')->nullable();
            $table->string('square_meter')->nullable();
            $table->decimal('sales_per_capital', 15)->nullable();
            $table->decimal('projected_peso_bread_sales_per_month', 15)->nullable();
            $table->decimal('projected_peso_non_bread_sales_per_month', 15)->nullable();
            $table->decimal('projected_total_cost', 15)->nullable();

            $table->date('contract_of_lease_start_date')->nullable();
            $table->date('contract_of_lease_end_date')->nullable();
            $table->string('escalation')->nullable();
            $table->string('lessor_name')->nullable();
            $table->date('lease_payment_date')->nullable();
            $table->string('notarized_stamp_payment_receipt_number')->nullable();
            $table->date('col_notarized_date')->nullable();
            $table->string('col_notarized_by')->nullable();

            $table->date('maintenance_last_repaint_at')->nullable();
            $table->date('maintenance_last_renovation_at')->nullable();
            $table->date('maintenance_temporary_closed_at')->nullable();
            $table->string('maintenance_temporary_closed_reason')->nullable();
            $table->date('maintenance_reopening_date')->nullable();
            $table->date('maintenance_permanent_closure_date')->nullable();
            $table->string('maintenance_permanent_closure_reason')->nullable();
            $table->date('maintenance_upgrade_date')->nullable();
            $table->date('maintenance_downgrade_date')->nullable();
            $table->text('maintenance_remarks')->nullable();
            $table->date('maintenance_store_acquired_at')->nullable();
            $table->date('maintenance_store_transferred_at')->nullable();
            $table->string('maintenance_old_franchisee_code')->nullable();
            $table->string('maintenance_old_branch_code')->nullable();

            $table->boolean('is_draft')->default(true);
            $table->boolean('is_active')->default(true);
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
        Schema::dropIfExists('stores');
    }
};
