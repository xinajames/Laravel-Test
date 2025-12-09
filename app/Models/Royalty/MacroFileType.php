<?php

namespace App\Models\Royalty;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MacroFileType extends Model
{
    protected $fillable = [
        'type', 'description',
    ];

    public function fileRevisions(): HasMany
    {
        return $this->hasMany(MacroFileRevision::class, 'file_type_id');
    }
}
