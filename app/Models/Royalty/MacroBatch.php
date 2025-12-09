<?php

namespace App\Models\Royalty;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MacroBatch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'title', 'remarks', 'status', 'errors',
        'month', 'year', 'completed_at',
    ];

    public function macroBatchConfigs(): HasMany
    {
        return $this->hasMany(MacroBatchConfig::class, 'batch_id');
    }

    public function macroOutputs(): HasMany
    {
        return $this->hasMany(MacroOutput::class, 'batch_id');
    }

    public function macroUploads(): HasMany
    {
        return $this->hasMany(MacroUpload::class, 'batch_id');
    }
}
