<?php

namespace GtfsMerger\Merger;

use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Nette\Caching\Cache;
use Nette\InvalidStateException;
use Ramsey\Uuid\UuidFactory;

class StopTimes
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
     * @param string $file
     * @return array
     */
    public function merge($file)
    {
        // TODO: next two lines into service
        $zip = new ZipArchiveAdapter($file);
        $stream = $zip->readStream('stop_times.txt')['stream'];

        $items = [];
        $header = fgetcsv($stream);
        while(($data = fgetcsv($stream)) !== false) {
            $data = array_combine($header, $data);

            $data['trip_id'] = $this->getTripId($data['trip_id']);
            $data['stop_id'] = $this->getStopId($data['stop_id']);

            $items[] = $data;
        }
        return $items;
    }

    /**
     * @param string $oldTripId
     * @return string
     */
    private function getTripId($oldTripId)
    {
        $newId = $this->tripsIdsCache->load($oldTripId);
        if ($newId === null) {
            throw new InvalidStateException('Unknown trip ID: ' . $oldTripId);
        }
        return $newId;
    }

    /**
     * @param string $oldStopId
     * @return string
     */
    private function getStopId($oldStopId)
    {
        $newId = $this->stopsIdsCache->load($oldStopId);
        if ($newId === null) {
            throw new InvalidStateException('Unknown stop ID: ' . $oldStopId);
        }
        return $newId;
    }
}