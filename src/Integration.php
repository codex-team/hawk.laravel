<?php

declare(strict_types=1);

namespace src;

use HawkBundle\Throwable;
use Illuminate\Foundation\Configuration\Exceptions;
use src\Services\ErrorLoggerService;

class Integration
{
    public static function handles(Exceptions $exceptions): void
    {
        $exceptions->reportable(static function (Throwable $exception) {
            app(ErrorLoggerService::class)->logException($exception);
        });
    }
}
