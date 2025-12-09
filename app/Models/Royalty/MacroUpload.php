<?php

namespace App\Models\Royalty;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MacroUpload extends Model
{
    protected $fillable = [
        'batch_id', 'file_name', 'file_type_id', 'file_path', 'cached_path',
        'file_size',
    ];

    public function macroBatch(): BelongsTo
    {
        return $this->belongsTo(MacroBatch::class, 'batch_id');
    }
}
