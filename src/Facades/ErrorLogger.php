<?php

declare(strict_types=1);

namespace src\Facades;

use Illuminate\Support\Facades\Facade;
use src\Services\ErrorLoggerService;

class ErrorLogger extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ErrorLoggerService::class;
    }
}
