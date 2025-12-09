<?php

namespace App\Models;

use App\Enums\UserStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'password_updated_at',
        'profile_photo',
        'status',
        'timezone',
        'user_type_id',
        'user_role_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'admin_type',
        'profile_photo_url',
        'is_active',
    ];

    public function getProfilePhotoUrlAttribute(): string
    {
        return $this->profile_photo
            ? $this->retrieveFile($this->profile_photo, config('filesystems.upload_disk', 'local'))
            : 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&color=A32130&background=F5C3C9';
    }

    public function retrieveFile($path, $disk = 'local'): string
    {
        try {
            $storageDisk = Storage::disk($disk);

            return $storageDisk->temporaryUrl(substr($path, 1, strlen($path) - 1), now()->addHour());
        } catch (\Exception $exception) {
            try {
                return Storage::disk($disk)->url($path);
            } catch (\Exception $fallbackException) {
                return '';
            }
        }
    }

    public function getAdminTypeAttribute(): string
    {
        return $this->user_role_id ? UserRole::find($this->user_role_id)->type : '';
    }

    public function pendingFranchiseeApplication(): HasOne
    {
        return $this->hasOne(Franchisee::class, 'created_by_id')
            ->where('is_draft', true)
            ->whereNull('deleted_at');
    }

    public function pendingStoreApplication(): HasOne
    {
        return $this->hasOne(Store::class, 'created_by_id')
            ->where('is_draft', true)
            ->whereNull('deleted_at');
    }

    public function ongoingStoreRating(): HasMany
    {
        return $this->hasMany(StoreRating::class, 'created_by_id')
            ->where('is_draft', true)
            ->whereNull('deleted_at');
    }

    public function userRole()
    {
        return $this->belongsTo(UserRole::class);
    }

    public function hasUserRoleType($type): bool
    {
        return $this->userRole?->type === $type;
    }

    public function isUserAuditor(): bool
    {
        return $this->userRole?->type === 'Store Auditor';
    }

    public function auditedStores()
    {
        return $this->belongsToMany(Store::class, 'store_auditors');
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected function getIsActiveAttribute(): bool
    {
        return $this->status === UserStatusEnum::Active()->value;
    }
}
