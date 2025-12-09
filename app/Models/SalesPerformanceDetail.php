<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesPerformanceDetail extends Model
{
    protected $fillable = [
        'sales_performance_id',
        'cluster_code',
        'store_code',
        'franchise_code',
        'region',
        'area',
        'district',
        'year',
        'month',
        'bread',
        'non_bread',
        'combined',
        'recorded_at',
    ];

    public function salesPerformance(): BelongsTo
    {
        return $this->belongsTo(SalesPerformance::class);
    }
}
