<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoreHistory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'user_id',
        'change_group_id',
        'field',
        'old_value',
        'new_value',
        'effective_at',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
