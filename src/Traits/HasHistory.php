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

    public function disableHistory(): self
    {
        $this::flushEventListeners();

        return $this;
    }

    public function history(): MorphMany
    {
        return $this->morphMany(
            related: History::class,
            name: 'model',
            localKey: $this->getLocalKeyName()
        );
    }

    public function getLocalKeyName(): string
    {
        return $this->getKeyName();
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
