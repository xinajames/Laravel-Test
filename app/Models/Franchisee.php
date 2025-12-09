<?php

namespace App\Models;

use App\Traits\HasCreatedUpdatedBy;
use App\Traits\ManageFilesystems;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Franchisee extends Model
{
    use HasCreatedUpdatedBy;
    use ManageFilesystems;
    use SoftDeletes;

    protected $fillable = [
        'status',
        'franchisee_code',
        'corporation_name',
        'tin',
        'franchisee_profile_photo',
        'first_name',
        'middle_name',
        'last_name',
        'name_suffix',
        'birthdate',
        'gender',
        'nationality',
        'religion',
        'marital_status',
        'spouse_name',
        'spouse_birthdate',
        'wedding_date',
        'number_of_children',
        'residential_address_province',
        'residential_address_city',
        'residential_address_barangay',
        'residential_address_street',
        'residential_address_postal',
        'contact_number',
        'contact_number_2',
        'contact_number_3',
        'email',
        'email_2',
        'email_3',
        'fm_point_person',
        'fm_district_manager',
        'fm_region',
        'fm_contact_number',
        'fm_contact_number_2',
        'fm_contact_number_3',
        'fm_email_address',
        'fm_email_address_2',
        'fm_email_address_3',
        'date_start_bakery_management_seminar',
        'date_end_bakery_management_seminar',
        'date_start_bread_baking_course',
        'date_end_bread_baking_course',
        'operations_manual_number',
        'operations_manual_release',
        'date_applied',
        'date_approved',
        'date_separated',
        'background',
        'custom_background',
        'education',
        'course',
        'occupation',
        'source_of_information',
        'custom_source_of_information',
        'legacy',
        'generation',
        'custom_generation',
        'remarks',
        'is_draft',
        'application_step',
        'created_by_id',
        'updated_by_id',
        'created_at',
        'updated_at',
    ];

    protected $appends = [
        'full_name',
        'full_residential_address',
        'franchisee_profile_photo_url',
    ];

    public function getFullNameAttribute(): string
    {
        $name = [$this->first_name, $this->middle_name, $this->last_name];
        $name = array_filter($name);

        return implode(' ', $name);
    }

    public function getFullResidentialAddressAttribute(): string
    {
        return implode(', ', array_filter([
            $this->residential_address_street,
            $this->residential_address_barangay,
            $this->residential_address_city,
            $this->residential_address_province,
            $this->residential_address_postal,
        ]));
    }

    public function getFranchiseeProfilePhotoUrlAttribute(): string
    {
        return $this->franchisee_profile_photo
            ? $this->retrieveFile($this->franchisee_profile_photo)
            : 'https://ui-avatars.com/api/?name='.urlencode($this->first_name.' '.$this->last_name).'&color=A32130&background=F5C3C9';
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class, 'franchisee_id')->where('is_active', true)->where('is_draft', false);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
