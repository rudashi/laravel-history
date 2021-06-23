<?php

namespace Rudashi\LaravelHistory\Contracts;

/**
 * @property \Illuminate\Database\Eloquent\Collection|\Rudashi\LaravelHistory\Models\History[] $history
 * @mixin \Illuminate\Database\Eloquent\Model
 */
interface HasHistoryInterface
{

    public function history(): \Illuminate\Database\Eloquent\Relations\MorphMany;

    public function excludedHistoryAttributes(): array;

}
