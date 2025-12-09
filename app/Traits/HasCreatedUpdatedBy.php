<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasCreatedUpdatedBy
{
    public static function bootHasCreatedUpdatedBy(): void
    {
        static::creating(function ($model) {
            $user = auth()->user();

            if (! $model->isDirty('created_by_id') && $user) {
                $model->created_by_id = $user->id;
            }

            if (! $model->isDirty('updated_by_id') && $user) {
                $model->updated_by_id = $user->id;
            }
        });

        static::updating(function ($model) {
            $user = auth()->user();

            if (! $model->isDirty('updated_by_id') && $user) {
                $model->updated_by_id = $user->id;
            }
        });
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
