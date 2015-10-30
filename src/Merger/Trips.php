<?php

namespace GtfsMerger\Merger;

use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Nette\Caching\Cache;
use Nette\InvalidStateException;
use Ramsey\Uuid\UuidFactory;

class Trips
{
    /** @var Cache */
    private $routesIdsCache;

    /** @var Cache */
    private $serviceIdsCache;

    /** @var Cache */
    private $idsCache;

    /** @var UuidFactory */
    private $uuidProvider;

    function __construct(
        Cache $routesIdsCache,
        Cache $serviceIdsCache,
        Cache $idsCache,
        UuidFactory $uuidProvider
    )
    {
        $this->routesIdsCache = $routesIdsCache;
        $this->serviceIdsCache = $serviceIdsCache;
        $this->idsCache = $idsCache;
        $this->uuidProvider = $uuidProvider;
    }

    /**
     * @param string $file
     * @return array
     */
    public function merge($file)
    {
        // TODO: next two lines into service
        $zip = new ZipArchiveAdapter($file);
        $stream = $zip->readStream('trips.txt')['stream'];

        $items = [];
        $header = fgetcsv($stream);
        while(($data = fgetcsv($stream)) !== false) {
            $data = array_combine($header, $data);

            $currentId = $data['trip_id'];
            $newId = $this->uuidProvider->uuid4()->toString();

            $data['trip_id'] = $newId;
            $data['route_id'] = $this->getRouteId($data['route_id']);
            $data['service_id'] = $this->getServiceId($data['service_id']);

            $this->idsCache->save($currentId, $newId);
            $items[] = $data;
        }
        return $items;
    }

    /**
     * @param string $oldRouteId
     * @return string
     */
    private function getRouteId($oldRouteId)
    {
        $newId = $this->routesIdsCache->load($oldRouteId);
        if ($newId === null) {
            throw new InvalidStateException('Unknown route ID: ' . $oldRouteId);
        }
        return $newId;
    }

    /**
     * @param string $oldServiceId
     * @return string
     */
    private function getServiceId($oldServiceId)
    {
        $newId = $this->serviceIdsCache->load($oldServiceId);
        if ($newId === null) {
            throw new InvalidStateException('Unknown service ID: ' . $oldServiceId);
        }
        return $newId;
    }
}