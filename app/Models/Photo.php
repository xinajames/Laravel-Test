<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Photo extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'file_path',
        'disk',
        'description',
        'photoable_id',
        'photoable_type',
    ];

    public function photoable(): MorphTo
    {
        return $this->morphTo();
    }
}
