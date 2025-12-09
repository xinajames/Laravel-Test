<?php

namespace App\Models\Royalty;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MacroBatchConfig extends Model
{
    protected $fillable = [
        'batch_id', 'has_uploaded_mnsr', 'gen_mnsr', 'gen_rwb',
    ];

    public function macroBatch(): BelongsTo
    {
        return $this->belongsTo(MacroBatch::class);
    }
}
