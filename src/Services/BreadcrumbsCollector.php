<?php

declare(strict_types=1);

namespace HawkBundle\Services;

class BreadcrumbsCollector
{
    protected $breadcrumbs = [];

    public function add(string $category, string $message, array $data = [], string $level = 'info'): void
    {
        $this->breadcrumbs[] = [
            'category'  => $category, // route, db.query, log, queue
            'message'   => $message,  // description
            'data'      => $data,     // additional
            'level'     => $level,    // info, warning, error
            'timestamp' => microtime(true),
        ];
    }

    public function all(): array
    {
        return $this->breadcrumbs;
    }

    public function reset(): void
    {
        $this->breadcrumbs = [];
    }
}
