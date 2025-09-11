<?php

declare(strict_types=1);

namespace Rudashi\LaravelHistory\Models\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface HistoryInterface
{
    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, \Rudashi\LaravelHistory\Models\History>
     */
    public static function ofModel(Model|string $type, mixed $value = null, string $foreignKey = 'id'): Collection;

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, \Rudashi\LaravelHistory\Models\History>
     */
    public static function ofUser(Model|string $type, mixed $value = null, string $foreignKey = 'id'): Collection;
}
