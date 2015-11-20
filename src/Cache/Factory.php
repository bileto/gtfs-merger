<?php

namespace GtfsMerger\Cache;

use Nette\Caching\Cache;
use Nette\Caching\Storages\MemoryStorage;

class Factory
{
    public static function create($namespace)
    {
        return new Cache(new MemoryStorage(), $namespace);
    }
}