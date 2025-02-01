**This project is currently in the testing phase and may undergo changes.**

# Hawk Laravel

Laravel errors Catcher for [Hawk.so](https://hawk.so).

## Setup

1. [Register](https://garage.hawk.so/sign-up) an account, create a Project and get an Integration Token.

2. Install SDK via [composer](https://getcomposer.org) to install the Catcher

- Catcher provides support for PHP 7.2 or later
- Your Laravel version needs to be 11.x or higher

### Install command

```bash
$ composer require codex-team/hawk.laravel:dev-main
```

To enable capturing unhandled exceptions for reporting to `Hawk`, modify your `bootstrap/app.php` file as follows:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        HawkBundle\Integration::handles($exceptions);
    })->create();
```

### Register the Service Provider

To complete the setup, add the `Hawk` service provider to your `config/app.php` or `bootstrap/providers.php` file in the providers array:

```php
'providers' => [
    // Other service providers...
    HawkBundle\ErrorLoggerServiceProvider::class,
],
```

### Configuration

Set up Hawk using the following command:

```bash
$ php artisan hawkbundle:publish
```

This will create the configuration file (`config/hawk.php`), and you can manually add the `HAWK_TOKEN` in your `.env` file:

```env
HAWK_TOKEN=<your integration token>
```

## Issues and improvements

Feel free to ask questions or improve the project.

## Links

Repository: https://github.com/codex-team/hawk.laravel

Report a bug: https://github.com/codex-team/hawk.laravel/issues

Composer Package: https://packagist.org/packages/codex-team/hawk.laravel

CodeX Team: https://codex.so

## License

MIT
