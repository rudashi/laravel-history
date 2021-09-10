<?php

namespace Rudashi\LaravelHistory\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string action
 * @property array meta
 * @property int user_id
 * @property string user_type
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Contracts\Auth\Authenticatable $user
 * @property \Rudashi\LaravelHistory\Contracts\HasHistoryInterface $model
 * @method \Illuminate\Database\Eloquent\Builder ofModel(string $model, string $uuid)
 */
class History extends Model
{

    public const UPDATED_AT = null;

    protected $casts = [
        'meta' => 'array'
    ];

    public function __construct(array $attributes = [])
    {
        $this->setTable(config('laravel-history.table'));

        $this->fillable([
            'action',
            'user_id',
            'user_type',
            'meta',
        ]);
        parent::__construct($attributes);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function model(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function scopeOfModel(\Illuminate\Database\Eloquent\Builder $query, string $model, string $uuid): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where(['model_type' => $model, 'model_id' => $uuid]);
    }

}
