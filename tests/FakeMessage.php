<?php

namespace Rudashi\LaravelHistory\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Rudashi\LaravelHistory\Traits\HasHistory;
use Rudashi\LaravelHistory\Contracts\HasHistoryInterface;

class FakeMessage extends Model implements HasHistoryInterface
{
    use SoftDeletes,
        HasHistory;

    public $timestamps = false;
    protected $guarded = [];
    protected $table = 'messages';

}