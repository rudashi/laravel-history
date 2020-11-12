<?php

namespace Rudashi\LaravelHistory\Traits;

use Rudashi\LaravelHistory\Models\History;

trait HasOperations
{

    public function operations()
    {
        return $this->morphMany(History::class, 'user');
    }

}