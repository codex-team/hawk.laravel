<?php

declare(strict_types=1);

namespace HawkBundle\Addons;

use Hawk\Addons\AddonInterface;
use HawkBundle\Services\BreadcrumbsCollector;

class Breadcrumbs implements AddonInterface
{
    private $breadcrumbs;

    public function __construct(BreadcrumbsCollector $breadcrumbs)
    {
        $this->breadcrumbs = $breadcrumbs;
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'Breadcrumbs';
    }

    /**
     * @inheritDoc
     */
    public function resolve(): array
    {
        return $this->breadcrumbs->all();
    }
}
