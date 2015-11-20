<?php

namespace GtfsMerger\Cache;

use Nette\Caching\Cache;

class Cleaner
{
    /** @var Cache */
    private $stopsIdsCache;

    /** @var Cache */
    private $serviceIdsCache;

    /** @var Cache */
    private $tripsIdsCache;

    function __construct(
        Cache $stopsIdsCache,
        Cache $serviceIdsCache,
        Cache $tripsIdsCache
    )
    {
        $this->stopsIdsCache = $stopsIdsCache;
        $this->serviceIdsCache = $serviceIdsCache;
        $this->tripsIdsCache = $tripsIdsCache;
    }

    public function clean()
    {
        $this->stopsIdsCache->clean([Cache::ALL => true]);
        $this->serviceIdsCache->clean([Cache::ALL => true]);
        $this->tripsIdsCache->clean([Cache::ALL => true]);
    }
}