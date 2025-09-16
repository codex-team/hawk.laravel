<?php

declare(strict_types=1);

namespace HawkBundle\Addons;

use Hawk\Addons\AddonInterface;

class Context implements AddonInterface
{
    public function getName(): string
    {
        return 'Context';
    }

    public function resolve(): array
    {
        if (class_exists(\Illuminate\Support\Facades\Context::class)) {
            try {
                return \Illuminate\Support\Facades\Context::all();
            } catch (\Throwable $ignored) {
            }
        }

        return [];
    }
}
