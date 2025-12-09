<?php

namespace App\Models;

use App\Enums\StoreRatingStepEnum;
use App\Traits\HasCreatedUpdatedBy;
use App\Traits\HasMorphMap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoreRating extends Model
{
    use HasCreatedUpdatedBy;
    use HasMorphMap;
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'reviewer_id',
        'overall_rating',
        'ratings_per_type',
        'rated_at',
        'step',
        'is_draft',
        'created_by_id',
        'updated_by_id',
    ];

    protected $appends = [
        'step_label',
    ];

    public function getStepLabelAttribute()
    {
        return $this->step ? StoreRatingStepEnum::getDescription($this->step) : null;
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function photos(): MorphMany
    {
        return $this->morphMany($this->getMorphMapValue('photo'), 'photoable');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function questionnaires()
    {
        return $this->belongsToMany(Questionnaire::class, 'store_rating_questionnaires')
            ->where('type', 'store_rating')
            ->withPivot(['question', 'order', 'answer'])
            ->withTimestamps()
            ->using(StoreRatingQuestionnaire::class);
    }
}
