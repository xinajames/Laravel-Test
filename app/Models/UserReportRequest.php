<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserReportRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'report_type',
        'report_name',
        'file_name',
        'file_path',
        'disk',
        'filter_data',
        'status',
        'attempts',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
