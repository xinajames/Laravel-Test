<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\Relation;

trait HasMorphMap
{
    protected function getMorphMapKey(string $class)
    {
        return array_search($class, Relation::morphMap());
    }

    protected function getMorphMapValue(string $key)
    {
        return Relation::morphMap()[$key];
    }
}
