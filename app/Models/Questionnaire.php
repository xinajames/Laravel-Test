<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Questionnaire extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'question',
        'order',
        'type',
        'category_id',
        'subcategory_id',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    public function storeRatings()
    {
        return $this->belongsToMany(StoreRating::class, 'store_rating_questionnaires')
            ->where('type', 'store_rating')
            ->withPivot(['question', 'order', 'answer'])
            ->withTimestamps()
            ->using(StoreRatingQuestionnaire::class);
    }
}
