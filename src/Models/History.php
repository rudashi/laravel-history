<?php

declare(strict_types=1);

namespace Rudashi\LaravelHistory\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Rudashi\LaravelHistory\Models\Contracts\HistoryInterface;

/**
 * @property \Carbon\Carbon $created_at
 * @property string $action
 * @property array<int, array{key: string, old: mixed, new: mixed}> $meta
 * @property int $user_id
 * @property string $user_type
 * @property \App\Models\User $user
 * @property \Illuminate\Database\Eloquent\Model $model
 *
 * @phpstan-consistent-constructor
 */
class History extends Model implements HistoryInterface
{
    public static string $customOwnerKey = 'id';

    public function __construct(array $attributes = [])
    {
        $this->setTable(config('laravel-history.table'));

        $this->fillable([
            'action',
            'meta',
        ]);
        $this->mergeCasts([
            'meta' => 'json',
        ]);

        parent::__construct($attributes);
    }

    public static function ofModel(Model|string $type, mixed $value = null, string $foreignKey = 'id'): Collection
    {
        static::$customOwnerKey = $foreignKey;

        return (new static())->ofMorph('model', $type, $value);
    }

    public static function ofUser(Model|string $type, mixed $value = null, string $foreignKey = 'id'): Collection
    {
        static::$customOwnerKey = $foreignKey;

        return (new static())->ofMorph('user', $type, $value);
    }

    public function setCustomOwnerKey(string $customOwnerKey): static
    {
        static::$customOwnerKey = $customOwnerKey;

        return $this;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function model(): MorphTo
    {
        return $this->morphTo(
            name: __FUNCTION__,
            ownerKey: static::$customOwnerKey
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function user(): MorphTo
    {
        return $this->morphTo(
            name: __FUNCTION__,
            ownerKey: static::$customOwnerKey
        );
    }

    public function saveModel(Model|null $model = null): static
    {
        $this->model()->associate($model);

        return $this;
    }

    public function saveUser(Model|null $model = null): static
    {
        $this->user()->associate($model);

        return $this;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, \Rudashi\LaravelHistory\Models\History>
     */
    private function ofMorph(string $relation, Model|string $type, mixed $value = null): Collection
    {
        if ($type instanceof Model) {
            $value = $type->getKey();
            $type = $type->getMorphClass();
        }

        return $this->newQuery()->whereMorphRelation(
            relation: Relation::noConstraints(fn () => $this->{$relation}()),
            types: $type,
            column: static::$customOwnerKey,
            operator: '=',
            value: $value
        )->latest()->get();
    }
}
