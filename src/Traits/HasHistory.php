<?php

namespace Rudashi\LaravelHistory\Traits;

use Rudashi\LaravelHistory\Models\History;
use Rudashi\LaravelHistory\Observers\HistoryObserver;

trait HasHistory
{

    public function history(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(History::class, 'model');
    }

    public static function bootHasHistory(): void
    {
        static::observe(HistoryObserver::class);
    }

}