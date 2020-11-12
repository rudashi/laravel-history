<?php

namespace Rudashi\LaravelHistory\Models;

use Illuminate\Database\Eloquent\Model;

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

}