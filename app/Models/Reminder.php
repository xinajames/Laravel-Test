<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reminder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'reference_date_field',
        'title',
        'description',
        'days_before',
        'notify_number',
        'notify_unit',
        'type',
        'is_enabled',
    ];

    public function instances(): HasMany
    {
        return $this->hasMany(ReminderInstance::class);
    }
}
