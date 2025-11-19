<?php

declare(strict_types=1);

namespace Rudashi\LaravelHistory\Listeners;

use Rudashi\LaravelHistory\Models\History;

class AuthenticationListeners
{
    public function handle(mixed $event): void
    {
        $user = $event->user ?? null;

        if (is_object($user) && method_exists($user, 'operations')) {
            $event->user->operations()->save(
                new History([
                    'action' => class_basename($event),
                    'meta' => [],
                ])
            );
        }
    }
}
