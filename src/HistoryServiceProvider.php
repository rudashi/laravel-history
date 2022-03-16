<?php

declare(strict_types=1);

namespace Rudashi\LaravelHistory;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

class HistoryServiceProvider extends ServiceProvider
{

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        $this->publishes([
            __DIR__.'/database/migrations' => database_path('migrations'),
        ], 'migrations');

        $this->publishes([
            __DIR__ . '/config/config.php' => config_path('laravel-history.php'),
        ], 'config');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/config.php', 'laravel-history');

        foreach (array_filter($this->getListenEvents()) as $class => $listener) {
            $this->getDispatcher()->listen($class, $listener);
        }
    }

    private function getDispatcher(): Dispatcher
    {
        return $this->app['events'];
    }

    private function getListenEvents(): array
    {
        return $this->app['config']->get('laravel-history.events', []);
    }

}
