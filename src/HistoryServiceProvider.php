<?php

namespace Rudashi\LaravelHistory;

use Illuminate\Support\ServiceProvider;

class HistoryServiceProvider extends ServiceProvider
{

    public function boot(): void
    {
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
    }

}