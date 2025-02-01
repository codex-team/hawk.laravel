<?php

declare(strict_types=1);

namespace HawkBundle;

use HawkBundle\Services\ErrorLoggerService;
use Illuminate\Foundation\Configuration\Exceptions;

class Integration
{
    public static function handles(Exceptions $exceptions): void
    {
        $exceptions->reportable(static function (Throwable $exception) {
            app(ErrorLoggerService::class)->logException($exception);
        });
    }
}
