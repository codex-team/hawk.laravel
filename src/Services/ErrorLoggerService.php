<?php

declare(strict_types=1);

namespace HawkBundle\Services;

use HawkBundle\Catcher;

class ErrorLoggerService
{
    protected $config;

    /** @var array<string,bool> */
    protected $sent = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function logException(\Throwable $exception)
    {
        $objectHash = spl_object_hash($exception);
        if (isset($this->sent[$objectHash])) {
            return;
        }

        $this->sent[$objectHash] = true;

        app(Catcher::class)->sendException($exception);
    }
}
