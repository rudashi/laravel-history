<?php

declare(strict_types=1);

namespace Rudashi\LaravelHistory\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Rudashi\LaravelHistory\Models\Contracts\HistoryInterface;

/**
 * @property Carbon created_at
 * @property string action
 * @property array meta
 * @property int user_id
 * @property string user_type
 * @property User user
 * @property Model model
 */
class History extends Model implements HistoryInterface
{
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
        return (new static())->ofMorph('model', $type, $value, $foreignKey);
    }

    public static function ofUser(Model|string $type, mixed $value = null, string $foreignKey = 'id'): Collection
    {
        return (new static())->ofMorph('user', $type, $value, $foreignKey);
    }

    public function model(string $ownerKey = 'id'): MorphTo
    {
        return $this->morphTo(
            name: __FUNCTION__,
            ownerKey: $ownerKey
        );
    }

    public function user(string $ownerKey = 'id'): MorphTo
    {
        return $this->morphTo(
            name: __FUNCTION__,
            ownerKey: $ownerKey
        );
    }

    private function ofMorph(string $relation, Model|string $type, mixed $value = null, string $foreignKey = 'id'): Collection
    {
        if ($type instanceof Model) {
            $foreignKey = $type->getKeyName();
            $value = $type->getKey();
            $type = $type->getMorphClass();
        }

        return static::query()->whereMorphRelation(
            relation: Relation::noConstraints(fn () => $this->{$relation}($foreignKey)),
            types: $type,
            column: $foreignKey,
            operator: '=',
            value: $value
        )->latest()->get();
    }
}
