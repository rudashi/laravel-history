<?php

namespace Rudashi\LaravelHistory\Traits;

use Rudashi\LaravelHistory\Models\History;

trait HasOperations
{

    public function operations(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(History::class, 'user');
    }

}
