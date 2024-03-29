<?php

declare(strict_types=1);

namespace Rudashi\LaravelHistory\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Rudashi\LaravelHistory\Models\History;

/**
 * @property Collection<History>|History[] $history
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
interface HasHistoryInterface
{
    public function disableHistory();

    public function history(): MorphMany;

    public function getLocalKeyName(): string;

    public function excludedHistoryAttributes(): array;

    public function excludedHistoryModelEvents(): array;
}
