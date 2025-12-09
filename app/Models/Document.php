<?php

namespace App\Models;

use App\Traits\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasCreatedUpdatedBy;
    use SoftDeletes;

    protected $fillable = [
        'external_id',
        'documentable_type',
        'documentable_id',
        'document_name',
        'file_path',
        'disk',
        'file_type',
        'file_size',
        'created_by_id',
        'updated_by_id',
    ];

    protected $appends = [
        'documentable_name',
        'formatted_file_size',
        'formatted_file_type',
        'created_by_name',
        'updated_by_name',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getDocumentableNameAttribute(): ?string
    {
        if (! $this->relationLoaded('documentable')) {
            $this->load('documentable');
        }

        if ($this->documentable instanceof Franchisee) {
            return $this->documentable->full_name;
        }

        if ($this->documentable instanceof Store) {
            return $this->documentable->jbs_name;
        }

        return null;
    }

    public function getFormattedFileSizeAttribute()
    {
        if (empty($this->file_size) || $this->file_size === 0) {
            return null;
        }

        if ($this->file_size >= 1048576) {
            return round($this->file_size / 1048576, 2).' MB';
        }

        if ($this->file_size >= 1024) {
            return round($this->file_size / 1024, 2).' KB';
        }

        return $this->file_size.' B';
    }

    public function getFormattedFileTypeAttribute(): string
    {
        [$major, $minor] = explode('/', $this->file_type) + [null, null];

        return $minor
            ? strtoupper($minor)
            : strtoupper($this->file_type);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function getCreatedByNameAttribute()
    {
        return $this->createdBy->name ?? null;
    }

    public function getUpdatedByNameAttribute()
    {
        return $this->updatedBy->name ?? null;
    }
}
