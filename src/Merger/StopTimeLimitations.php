<?php

namespace GtfsMerger\Merger;

use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Nette\Caching\Cache;
use Nette\InvalidStateException;

class StopTimeLimitations implements MergerInterface
{
    /** @var Cache */
    private $tripsIdsCache;

    /** @var Cache */
    private $stopsIdsCache;

    function __construct(
        Cache $tripsIdsCache,
        Cache $stopsIdsCache
    )
    {
        $this->tripsIdsCache = $tripsIdsCache;
        $this->stopsIdsCache = $stopsIdsCache;
    }

    /**
     * @param resource $stream
     * @return array
     */
    public function merge($stream)
    {
        $items = [];
        $header = fgetcsv($stream);
        while(($data = fgetcsv($stream)) !== false) {
            $data = array_combine($header, $data);

            $currentId = $data['trip_id'];
            $newId = $this->tripsIdsCache->load($currentId);
            if ($newId === null) {
                throw new InvalidStateException('Unknown trip ID: ' . $currentId);
            }
            $data['trip_id'] = $newId;

            $currentId = $data['stop_id'];
            $newId = $this->stopsIdsCache->load($currentId);
            if ($newId === null) {
                throw new InvalidStateException('Unknown stop ID: ' . $currentId);
            }
            $data['stop_id'] = $newId;

            $items[] = $data;
        }
        return $items;
    }
}
