<?php

namespace Rudashi\LaravelHistory\Tests;

use Illuminate\Foundation\Auth\User;
use Rudashi\LaravelHistory\Traits\HasOperations;

class FakeUser extends User
{
    use HasOperations;

    public $timestamps = false;
    protected $guarded = [];
    protected $table = 'users';

}
