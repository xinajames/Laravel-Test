<?php

namespace App\Models;

use App\Traits\HasMorphMap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoreRatingQuestionnaire extends Model
{
    use HasMorphMap;
    use SoftDeletes;

    protected $fillable = [
        'store_rating_id',
        'questionnaire_id',
        'question',
        'order',
        'answer',
    ];

    public function questionnaire()
    {
        return $this->belongsTo(Questionnaire::class, 'questionnaire_id');
    }

    public function remarks(): MorphMany
    {
        return $this->morphMany($this->getMorphMapValue('remark'), 'remarkable');
    }
}
