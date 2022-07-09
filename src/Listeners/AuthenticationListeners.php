<?php

declare(strict_types=1);

namespace Rudashi\LaravelHistory\Listeners;

use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\CurrentDeviceLogout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Auth\Events\Validated;
use Rudashi\LaravelHistory\Models\History;

class AuthenticationListeners
{
    public function handle(Authenticated|Login|CurrentDeviceLogout|Failed|Logout|OtherDeviceLogout|Validated $event): void
    {
        if ($event->user && method_exists($event->user, 'operations')) {
            $event->user->operations()->save(
                new History([
                    'action' => class_basename($event),
                    'meta' => [],
                ])
            );
        }
    }
}
