<?php

declare(strict_types=1);

namespace Rudashi\LaravelHistory\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Rudashi\LaravelHistory\Models\History;

/**
 * @property \Illuminate\Database\Eloquent\Collection<int, \Rudashi\LaravelHistory\Models\History> $operations
 */
trait HasOperations
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<\Rudashi\LaravelHistory\Models\History, \Illuminate\Database\Eloquent\Model>
     */
    public function operations(): MorphMany
    {
        return $this->morphMany(
            related: History::class,
            name: 'user',
        );
    }
}
