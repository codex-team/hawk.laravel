<?php

declare(strict_types=1);

namespace HawkBundle\Facades;

use HawkBundle\Services\ErrorLoggerService;
use Illuminate\Support\Facades\Facade;

class ErrorLogger extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ErrorLoggerService::class;
    }
}
