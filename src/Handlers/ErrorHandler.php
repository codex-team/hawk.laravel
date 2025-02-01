<?php

declare(strict_types=1);

namespace src\Handlers;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use src\Services\ErrorLoggerService;

class ErrorHandler extends ExceptionHandler
{
    protected $errorLogger;

    public function __construct($app, ErrorLoggerService $errorLogger)
    {
        parent::__construct($app);

        $this->errorLogger = $errorLogger;
    }

    public function report(\Throwable $exception)
    {
        $this->errorLogger->logException($exception);

        parent::report($exception);
    }

    public function render($request, \Throwable $exception)
    {
        return parent::render($request, $exception);
    }
}
