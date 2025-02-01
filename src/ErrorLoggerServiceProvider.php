<?php

declare(strict_types=1);

namespace src;

use Illuminate\Support\ServiceProvider;
use src\Console\Commands\PublishHawkConfig;
use src\Handlers\ErrorHandler;
use src\Services\ErrorLoggerService;

class ErrorLoggerServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/hawk.php', 'hawk');

        $this->app->singleton(ErrorLoggerService::class, function ($app) {
            return new ErrorLoggerService($app['config']['hawk']);
        });
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([PublishHawkConfig::class]);
        }

        $this->publishes([
            __DIR__ . '/../config/hawk.php' => config_path('hawk.php'),
        ]);

        \Hawk\Catcher::init([
            'integrationToken' => config('hawk.integration_token') ?: ''
        ]);

        $this->app->singleton('Illuminate\Contracts\Debug\ExceptionHandler', function ($app) {
            return new ErrorHandler($app, $app->make(ErrorLoggerService::class));
        });
    }
}
