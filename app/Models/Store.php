<?php

namespace App\Models;

use App\Enums\StoreGroupEnum;
use App\Enums\StoreTypeEnum;
use App\Traits\HasCreatedUpdatedBy;
use App\Traits\HasMorphMap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use HasCreatedUpdatedBy;
    use HasMorphMap;
    use SoftDeletes;

    protected $fillable = [
        'franchisee_id',
        'store_code',
        'store_status',
        'cluster_code',
        'jbmis_code',
        'jbs_name',
        'store_type',
        'store_group',
        'franchisee_code',
        'sales_point_code',
        'region',
        'area',
        'district',
        'google_maps_link',
        'om_district_code',
        'om_district_name',
        'om_district_manager',
        'om_cost_center_code',
        'old_continuing_license_fee',
        'current_continuing_license_fee',
        'continuing_license_fee_in_effect',
        'brf_in_effect',
        'report_percent',
        'date_opened',
        'franchise_date',
        'original_franchise_date',
        'renewal_date',
        'last_renewal_date',
        'effectivity_date',
        'target_opening_date',
        'soft_opening_date',
        'grand_opening_date',
        'store_province',
        'store_city',
        'store_barangay',
        'store_street',
        'store_postal_code',
        'store_phone_number',
        'store_mobile_number',
        'store_email',
        'with_cctv',
        'cctv_installed_at',
        'with_internet',
        'internet_installed_at',
        'with_pos',
        'pos_name',
        'pos_installed_at',
        'warehouse',
        'custom_warehouse_name',
        'warehouse_remarks',
        'bir_2303',
        'cgl_insurance_policy_number',
        'cgl_expiry_date',
        'fire_insurance_policy_number',
        'fire_expiry_date',
        'area_population',
        'catchment',
        'foot_traffic',
        'manpower',
        'rental',
        'square_meter',
        'sales_per_capita',
        'projected_peso_bread_sales_per_month',
        'projected_peso_non_bread_sales_per_month',
        'projected_total_cost',
        'contract_of_lease_start_date',
        'contract_of_lease_end_date',
        'escalation',
        'lessor_name',
        'lease_payment_date',
        'notarized_stamp_payment_receipt_number',
        'col_notarized_date',
        'col_notarized_by',
        'maintenance_last_repaint_at',
        'maintenance_last_renovation_at',
        'maintenance_temporary_closed_at',
        'maintenance_temporary_closed_reason',
        'maintenance_reopening_date',
        'maintenance_permanent_closure_date',
        'maintenance_permanent_closure_reason',
        'maintenance_upgrade_date',
        'maintenance_downgrade_date',
        'maintenance_remarks',
        'maintenance_store_acquired_at',
        'maintenance_store_transferred_at',
        'maintenance_old_franchisee_code',
        'maintenance_old_branch_code',
        'is_draft',
        'is_active',
        'application_step',
        'created_by_id',
        'updated_by_id',
    ];

    protected $appends = [
        'full_store_address',
        'store_group_label',
    ];

    public function getFullStoreAddressAttribute(): string
    {
        return implode(', ', array_filter([
            $this->store_street,
            $this->store_barangay,
            $this->store_city,
            $this->store_province,
            $this->store_postal_code,
        ]));
    }

    public function getStoreGroupLabelAttribute()
    {
        return $this->store_group ? StoreGroupEnum::getDescription($this->store_group) : '—';
    }

    public function getStoreTypeLabelAttribute()
    {
        return $this->store_type ? StoreTypeEnum::getDescription($this->store_type) : '—';
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(StoreHistory::class);
    }

    public function storeRatings(): HasMany
    {
        return $this->hasMany(StoreRating::class);
    }

    public function franchisee(): BelongsTo
    {
        return $this->belongsTo(Franchisee::class, 'franchisee_id');
    }

    public function auditors()
    {
        return $this->belongsToMany(User::class, 'store_auditors');
    }

    public function photos(): MorphMany
    {
        return $this->morphMany($this->getMorphMapValue('photo'), 'photoable');
    }

    public function reminderInstances(): MorphMany
    {
        return $this->morphMany(ReminderInstance::class, 'remindable');
    }
}
