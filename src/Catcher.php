<?php

declare(strict_types=1);

namespace HawkBundle;

use Hawk\Addons\Environment;
use Hawk\Addons\Headers;
use Hawk\EventPayloadBuilder;
use Hawk\Handler;
use Hawk\Options;
use Hawk\Serializer;
use Hawk\StacktraceFrameBuilder;
use Hawk\Transport\CurlTransport;
use HawkBundle\Addons\Breadcrumbs;
use HawkBundle\Addons\Context;
use HawkBundle\Services\BreadcrumbsCollector;
use Throwable;

final class Catcher
{
    /**
     * Catcher SDK private instance. Created once
     *
     * @var self
     */
    private static $instance;

    /**
     * SDK handler: contains methods that catch errors and exceptions
     *
     * @var Handler
     */
    private $handler;

    /**
     * Static method to initialize Catcher
     *
     * @param array $options
     *
     * @return self
     */
    public static function init(array $options, BreadcrumbsCollector $breadcrumbs): Catcher
    {
        if (!self::$instance) {
            self::$instance = new self($options, $breadcrumbs);
        }

        return self::$instance;
    }

    /**
     * Returns initialized instance or throws an exception if it is not created yet
     *
     * @return Catcher
     *
     * @throws \Exception
     */
    public static function get(): Catcher
    {
        if (self::$instance === null) {
            throw new \Exception('Catcher is not initialized');
        }

        return self::$instance;
    }

    /**
     * @param array $user
     *
     * @return $this
     */
    public function setUser(array $user): self
    {
        $this->handler->setUser($user);

        return $this;
    }

    /**
     * @param array $context
     *
     * @return $this
     */
    public function setContext(array $context): self
    {
        $this->handler->setContext($context);

        return $this;
    }

    /**
     * @param string $message
     * @param array  $context
     *
     * @example
     * app(HawkBundle\Catcher::class)->get()
     *  ->sendMessage('my special message', [
     *      ... // context
     *  ])
     */
    public function sendMessage(string $message, array $context = []): void
    {
        $this->handler->sendEvent([
            'title'   => $message,
            'context' => $context
        ]);
    }

    /**
     * @param Throwable $throwable
     * @param array     $context
     *
     * @throws Throwable
     *
     * @example
     * app(HawkBundle\Catcher::class)->get()
     *  ->sendException($exception, [
     *      ... // context
     *  ])
     */
    public function sendException(Throwable $throwable, array $context = [])
    {
        $this->handler->handleException($throwable, $context);
    }

    /**
     * @example
     * app(HawkBundle\Catcher::class)->get()
     *  ->sendEvent([
     *      ... // payload
     * ])
     *
     * @param array $payload
     */
    public function sendEvent(array $payload): void
    {
        $this->handler->sendEvent($payload);
    }

    /**
     * @param array $options
     */
    private function __construct(array $options, BreadcrumbsCollector $breadcrumbs)
    {
        $options = new Options($options);

        /**
         * Init stacktrace frames builder and inject serializer
         */
        $serializer = new Serializer();
        $stacktraceBuilder = new StacktraceFrameBuilder($serializer);

        /**
         * Prepare Event payload builder
         */
        $builder = new EventPayloadBuilder($stacktraceBuilder);
        $builder->registerAddon(new Headers());
        $builder->registerAddon(new Environment());
        $builder->registerAddon(new Breadcrumbs($breadcrumbs));
        $builder->registerAddon(new Context());

        $transport = new CurlTransport($options->getUrl(), $options->getTimeout());

        $this->handler = new Handler($options, $transport, $builder);

        $this->handler->registerErrorHandler();
        $this->handler->registerExceptionHandler();
        $this->handler->registerFatalHandler();
    }
}
