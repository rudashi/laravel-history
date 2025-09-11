<?php

declare(strict_types=1);

namespace Rudashi\LaravelHistory\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property \Illuminate\Database\Eloquent\Collection<int, \Rudashi\LaravelHistory\Models\History> $history
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
interface HasHistoryInterface
{
    public function disableHistory(): void;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<\Rudashi\LaravelHistory\Models\History, \Illuminate\Database\Eloquent\Model>
     */
    public function history(): MorphMany;

    public function getLocalKeyName(): string;

    /**
     * @return array<int, string>
     */
    public function excludedHistoryAttributes(): array;

    /**
     * @return array<int, string>
     */
    public function excludedHistoryModelEvents(): array;
}
