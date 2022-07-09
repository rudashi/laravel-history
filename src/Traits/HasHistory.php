<?php

declare(strict_types=1);

namespace Rudashi\LaravelHistory\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Rudashi\LaravelHistory\Models\History;
use Rudashi\LaravelHistory\Observers\HistoryObserver;

trait HasHistory
{
    public static function bootHasHistory(): void
    {
        static::observe(HistoryObserver::class);
    }

    public function initializeHasHistory(): void
    {
        foreach ($this->excludedHistoryModelEvents() as $event) {
            $this::getEventDispatcher()->forget("eloquent.$event: " . $this::class);
        }
    }

    public function disableHistory(): self
    {
        $this::flushEventListeners();

        return $this;
    }

    public function history(): MorphMany
    {
        return $this->morphMany(History::class, 'model');
    }

    public function excludedHistoryAttributes(): array
    {
        return [
            //
        ];
    }

    public function excludedHistoryModelEvents(): array
    {
        return [
            //
        ];
    }
}
