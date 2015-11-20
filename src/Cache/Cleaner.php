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
        $this->agencyIdsCache->clean([Cache::ALL => true]);
        $this->routesIdsCache->clean([Cache::ALL => true]);
        $this->stopsIdsCache->clean([Cache::ALL => true]);
        $this->serviceIdsCache->clean([Cache::ALL => true]);
        $this->tripsIdsCache->clean([Cache::ALL => true]);
    }
}