<?php

declare(strict_types=1);

namespace HawkBundle;

use HawkBundle\Console\Commands\PublishHawkConfig;
use HawkBundle\Handlers\ErrorHandler;
use HawkBundle\Services\BeforeSendServiceInterface;
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
use InvalidArgumentException;

class ErrorLoggerServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/hawk.php', 'hawk');

        $this->app->singleton(ErrorLoggerService::class, function ($app) {
            return new ErrorLoggerService($app['config']['hawk']);
        });

        $this->app->singleton(BreadcrumbsCollector::class, function () {
            return new BreadcrumbsCollector();
        });

        $this->app->singleton(DataFilter::class, function () {
            return new DataFilter();
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

        $this->app->singleton(Catcher::class, function ($app) {
            $breadcrumbsCollector = $app->make(BreadcrumbsCollector::class);

            $options = [
                'integrationToken' => config('hawk.integration_token') ?: '',
            ];

            $beforeSendService = config('hawk.before_send_service');
            if (!empty($beforeSendService)) {
                if (!class_exists($beforeSendService)) {
                    throw new InvalidArgumentException(sprintf(
                        'The before_send_service class "%s" does not exist.',
                        $beforeSendService
                    ));
                }

                if (!is_subclass_of($beforeSendService, BeforeSendServiceInterface::class)) {
                    throw new InvalidArgumentException(sprintf(
                        'The service "%s" must implement "%s".',
                        $beforeSendService,
                        BeforeSendServiceInterface::class
                    ));
                }

                $options['before_send'] = $app->make($beforeSendService);

                return Catcher::init($options, $breadcrumbsCollector);
            }
        });

        $this->app->singleton('Illuminate\Contracts\Debug\ExceptionHandler', function ($app) {
            return new ErrorHandler($app, $app->make(ErrorLoggerService::class));
        });

        Event::listen(RouteMatched::class, function (RouteMatched $event) {
            $filter = resolve(DataFilter::class);
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
