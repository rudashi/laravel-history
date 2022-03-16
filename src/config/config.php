<?php

use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\CurrentDeviceLogout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Auth\Events\Validated;
use Rudashi\LaravelHistory\Listeners\AuthenticationListeners;

return [

    /*
    |--------------------------------------------------------------------------
    | Database table name
    |--------------------------------------------------------------------------
    | This is the name of the table that will be created by the migration and
    | used by the History model.
    |
    */
    'table'  => 'model_histories',

    /*
    |--------------------------------------------------------------------------
    | Default Events
    |--------------------------------------------------------------------------
    | The list of events that the package is listening to. By default,
    | the authentication events is logged to history.
    |
    */
    'events' => [
        Validated::class => false,
        Login::class => AuthenticationListeners::class,
        Authenticated::class => false,
        Failed::class => false,
        Logout::class => AuthenticationListeners::class,
        OtherDeviceLogout::class => false,
        CurrentDeviceLogout::class => false,
    ],

];
