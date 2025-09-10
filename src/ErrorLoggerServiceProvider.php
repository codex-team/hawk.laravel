<?php

declare(strict_types=1);

namespace HawkBundle;

use HawkBundle\Console\Commands\PublishHawkConfig;
use HawkBundle\Handlers\ErrorHandler;
use HawkBundle\Services\BreadcrumbsCollector;
use HawkBundle\Services\DataFilter;
use HawkBundle\Services\ErrorLoggerService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Log\Events\MessageLogged;

class ErrorLoggerServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/hawk.php', 'hawk');

        $this->app->singleton(ErrorLoggerService::class, function ($app) {
            return new ErrorLoggerService(
                $app['config']['hawk'],
                $app->make(BreadcrumbsCollector::class)
            );
        });

        $this->app->singleton(BreadcrumbsCollector::class, function () {
            return new BreadcrumbsCollector();
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

        Event::listen(RouteMatched::class, function (RouteMatched $event) {
            $filter = new DataFilter();
            app(BreadcrumbsCollector::class)->add(
                'route',
                sprintf('%s %s → %s', $event->request->method(), $event->route->uri(), $event->route->getActionName()),
                [
                    'method'     => $event->request->method(),
                    'uri'        => $event->route->uri(),
                    'controller' => $event->route->getActionName(),
                    'parameters' => $filter->process($event->request->all()),
                ]
            );
        });

        Event::listen(QueryExecuted::class, function (QueryExecuted $query) {
            app(BreadcrumbsCollector::class)->add(
                'db.query',
                $query->sql,
                [
                    'bindings' => $query->bindings,
                    'time'     => $query->time,
                ]
            );
        });

        Event::listen(JobProcessing::class, function (JobProcessing $event) {
            app(BreadcrumbsCollector::class)->add(
                'queue.job',
                'Job processing: ' . $event->job->resolveName(),
                ['job' => $event->job->resolveName()]
            );
        });

        Event::listen(JobFailed::class, function (JobFailed $event) {
            app(BreadcrumbsCollector::class)->add(
                'queue.job',
                'Job failed: ' . $event->job->resolveName(),
                ['job' => $event->job->resolveName()],
                'error'
            );
        });

        Event::listen(MessageLogged::class, function (MessageLogged $event) {
            app(BreadcrumbsCollector::class)->add(
                'log',
                $event->message,
                $event->context ?? [],
                $event->level
            );
        });
    }
}
