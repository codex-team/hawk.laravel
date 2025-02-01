<?php

declare(strict_types=1);

namespace src\Services;

class ErrorLoggerService
{
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function logException(\Throwable $exception)
    {
        \Hawk\Catcher::get()->sendException($exception);
    }
}
