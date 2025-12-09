<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    public function causer(): MorphTo
    {
        // This allows fetching soft-deleted users
        return parent::causer()->withTrashed();
    }
}
