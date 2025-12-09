<?php

namespace App\Models\Royalty;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MacroStep extends Model
{
    protected $fillable = [
        'file_type_id', 'file_revision_id',
        'batch_id', 'upload_id', 'status',
    ];

    public function macroUpload(): BelongsTo
    {
        return $this->belongsTo(MacroUpload::class, 'upload_id');
    }
}
