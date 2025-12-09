<?php

namespace App\Models\Royalty;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MacroFixedCache extends Model
{
    protected $fillable = [
        'sales_performance_id', 'batch_id', 'file_type_id', 'file_revision_id',
        'cached_path',
    ];

    public function macroBatch(): BelongsTo
    {
        return $this->belongsTo(MacroBatch::class, 'batch_id');
    }
}
