<?php

namespace GtfsMerger\Cache;

use Nette\Caching\Cache;

class Cleaner
{
    /** @var Cache */
    private $agencyIdsCache;

    /** @var Cache */
    private $routesIdsCache;

    /** @var Cache */
    private $stopsIdsCache;

    /** @var Cache */
    private $serviceIdsCache;

    /** @var Cache */
    private $tripsIdsCache;

    function __construct(
        Cache $agencyIdsCache,
        Cache $routesIdsCache,
        Cache $stopsIdsCache,
        Cache $serviceIdsCache,
        Cache $tripsIdsCache
    )
    {
        $this->agencyIdsCache = $agencyIdsCache;
        $this->routesIdsCache = $routesIdsCache;
        $this->stopsIdsCache = $stopsIdsCache;
        $this->serviceIdsCache = $serviceIdsCache;
        $this->tripsIdsCache = $tripsIdsCache;
    }

    public function clean()
    {
        $this->agencyIdsCache->clean();
        $this->routesIdsCache->clean();
        $this->stopsIdsCache->clean();
        $this->serviceIdsCache->clean();
        $this->tripsIdsCache->clean();
    }
}