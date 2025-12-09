<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReminderInstance extends Model
{
    use SoftDeletes;

    // ReminderInstance explanation:
    // - Instances with `reminder_id` (not null) are based on global templates.
    // - If `is_custom` is true, it means the default reminder was updated/customized.
    // - Instances with `reminder_id` null and `is_custom` true are fully custom reminders (not from a template)
    //   and must have a `scheduled_at` date defined.
    protected $fillable = [
        'reminder_id',
        'remindable_id',
        'remindable_type',
        'reference_date_field',
        'title',
        'description',
        'days_before',
        'notify_number',
        'notify_unit',
        'is_custom',
        'is_enabled',
        'scheduled_at',
        'notified_at',
        'last_notified_at',
    ];

    public function reminder(): BelongsTo
    {
        return $this->belongsTo(Reminder::class);
    }

    public function remindable(): MorphTo
    {
        return $this->morphTo();
    }
}
