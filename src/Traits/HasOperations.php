<?php

declare(strict_types=1);

namespace Rudashi\LaravelHistory\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Rudashi\LaravelHistory\Models\History;

/**
 * @property Collection|History[] $operations
 */
trait HasOperations
{
    public function operations(): MorphMany
    {
        return $this->morphMany(History::class, 'user');
    }
}
