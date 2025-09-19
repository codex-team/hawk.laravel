# Hawk Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codex-team/hawk.laravel.svg?style=flat-square)](https://packagist.org/packages/codex-team/hawk.laravel)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/codex-team/hawk.laravel?style=flat-square)](https://www.php.net/)

Laravel error catcher for [Hawk.so](https://hawk.so).

> **Note:** This project is currently in the testing phase and may change in the future.

---

## Setup

1. [Register](https://garage.hawk.so/sign-up) an account, create a project, and get an **Integration Token**.
2. Install the SDK via [Composer](https://getcomposer.org):

   ```bash
   composer require codex-team/hawk.laravel
   ```

### Requirements

- PHP **7.2+**
- Laravel **11.x+**

---

## Configuration

### Enable exception capturing

Update your `bootstrap/app.php`:

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
    })
    ->create();
```

### Register the Service Provider

Add the `Hawk` service provider to your `config/app.php` or `bootstrap/providers.php`:

```php
'providers' => [
    // Other service providers...
    HawkBundle\ErrorLoggerServiceProvider::class,
],
```

### Publish configuration

Run:

```bash
php artisan hawkbundle:publish
```

This will create `config/hawk.php`.  
Then add your token in `.env`:

```env
HAWK_TOKEN=<your_integration_token>
```

---

## Usage

### Adding User & Context Information

```php
app(\HawkBundle\Catcher::class)->setUser([
    'name' => 'John Doe',
    'photo' => 'https://example.com/avatar.png',
]);

app(\HawkBundle\Catcher::class)->setContext([
    'page' => 'checkout',
    'cart_id' => 123,
]);
```

### Sending Exceptions Manually

Inject `\HawkBundle\Catcher` and call `sendException`:

```php
use HawkBundle\Catcher;

class TestController extends Controller
{
    private Catcher $catcher;

    public function __construct(Catcher $catcher)
    {
        $this->catcher = $catcher;
    }

    public function test()
    {
        try {
            // Code that may fail
        } catch (\Exception $e) {
            $this->catcher->sendException($e);
        }
    }
}
```

### Sending Custom Messages

```php
app(\HawkBundle\Catcher::class)->sendMessage(
    'Checkout started',
    [
        'cart_id' => 123,
        'step' => 'payment',
    ]
);
```

---

## BeforeSend Hook

If you want to modify or filter errors before they are sent to Hawk,  
implement the `BeforeSendServiceInterface`.

Example:

```php
<?php

namespace App\Hawk;

use Hawk\EventPayload;
use HawkBundle\Services\BeforeSendServiceInterface;

class BeforeSendService implements BeforeSendServiceInterface
{
    public function __invoke(EventPayload $eventPayload): ?EventPayload
    {
        $user = $eventPayload->getUser();

        // Remove sensitive data
        if (!empty($user['email'])) {
            unset($user['email']);
            $eventPayload->setUser($user);
        }

        // Skip sending in specific cases
        if ($eventPayload->getContext()['skip_sending'] ?? false) {
            return null;
        }

        return $eventPayload;
    }
}
```

Register it in `config/hawk.php`:

```php
return [
    'integration_token' => env('HAWK_TOKEN'),
    'before_send_service' => \App\Hawk\BeforeSendService::class,
];
```

---

## Issues & Contributions

- Found a bug? [Open an issue](https://github.com/codex-team/hawk.laravel/issues)
- Want to improve the package? Pull requests are welcome!

---

## Useful Links

- **Repository:** [github.com/codex-team/hawk.laravel](https://github.com/codex-team/hawk.laravel)
- **Composer Package:** [packagist.org/packages/codex-team/hawk.laravel](https://packagist.org/packages/codex-team/hawk.laravel)
- **CodeX Team:** [codex.so](https://codex.so)

---

## License

[MIT](LICENSE)
