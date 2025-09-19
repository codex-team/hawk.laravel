<?php

declare(strict_types=1);

namespace HawkBundle\Services;

use Hawk\EventPayload;

interface BeforeSendServiceInterface
{
    public function __invoke(EventPayload $eventPayload): ?EventPayload;
}
