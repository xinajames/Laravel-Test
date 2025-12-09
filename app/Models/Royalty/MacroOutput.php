<?php

namespace App\Models\Royalty;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MacroOutput extends Model
{
    protected $fillable = [
        'batch_id', 'step_id', 'status', 'file_name', 'file_type_id', 'file_revision_id', 'file_path', 'cached_path',
        'month', 'year', 'completed_at', 'file_size',
    ];

    public function macroBatch(): BelongsTo
    {
        return $this->belongsTo(MacroBatch::class, 'batch_id');
    }

    public function macroStep(): BelongsTo
    {
        return $this->belongsTo(MacroStep::class, 'step_id');
    }
}
