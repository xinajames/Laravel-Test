<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesPerformance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'path', 'cached_path',
        'by_store_path', 'by_store_cached_path',
        'recorded_at',
    ];

    public function salesPerformanceDetail(): HasMany
    {
        return $this->hasMany(SalesPerformanceDetail::class);
    }
}
