<?php

namespace Rudashi\LaravelHistory\Tests;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Notifiable;
use Rudashi\LaravelHistory\Traits\HasOperations;

/**
 * @property Collection $operations
 */
class FakeUser extends \Illuminate\Foundation\Auth\User
{
    use Notifiable,
        HasOperations;

    public $timestamps = false;
    protected $guarded = [];
    protected $table = 'users';

}