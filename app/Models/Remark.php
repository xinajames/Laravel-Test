<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Remark extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'remarkable_id',
        'remarkable_type',
        'remark',
        'remarked_by',
    ];

    public function remarkable(): MorphTo
    {
        return $this->morphTo();
    }

    public function remarkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'remarked_by');
    }
}
