<?php

namespace App\Models\Royalty;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MacroFileRevision extends Model
{
    protected $fillable = [
        'file_type_id', 'stage',
    ];

    public function fileType(): BelongsTo
    {
        return $this->belongsTo(MacroFileType::class, 'file_type_id');
    }
}
