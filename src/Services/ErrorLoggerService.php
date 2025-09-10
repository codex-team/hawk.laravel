<?php

declare(strict_types=1);

namespace HawkBundle\Services;

class ErrorLoggerService
{
    protected $config;
    protected $breadcrumbs;

    /** @var array<string,bool> */
    protected $sent = [];

    public function __construct(array $config, BreadcrumbsCollector $breadcrumbs)
    {
        $this->config = $config;
        $this->breadcrumbs = $breadcrumbs;
    }

    public function logException(\Throwable $exception)
    {
        $objectHash = spl_object_hash($exception);
        if (isset($this->sent[$objectHash])) {
            return;
        }

        $this->sent[$objectHash] = true;

        $context = [
            'laravel' => [
                'env' => app()->environment(),
                'user' => auth()->check() ? (auth()->user() ? auth()->user()->getAuthIdentifier() : null) : null,
                'console' => app()->runningInConsole(),
            ],
            'breadcrumbs' => $this->breadcrumbs->all(),
        ];

        \Hawk\Catcher::get()->sendException($exception, $context);

        $this->breadcrumbs->reset();
    }
}
