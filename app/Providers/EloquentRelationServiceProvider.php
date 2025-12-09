<?php

namespace App\Providers;

use App\Models\Document;
use App\Models\Franchisee;
use App\Models\Photo;
use App\Models\Remark;
use App\Models\Store;
use App\Models\StoreRating;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class EloquentRelationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'document' => Document::class,
            'franchisee' => Franchisee::class,
            'remark' => Remark::class,
            'store' => Store::class,
            'store_rating' => StoreRating::class,
            'users' => User::class,
            'photo' => Photo::class,
        ]);
    }
}
