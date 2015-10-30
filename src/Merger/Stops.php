<?php

namespace GtfsMerger\Merger;

use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Nette\Caching\Cache;
use Nette\InvalidStateException;
use Ramsey\Uuid\UuidFactory;

class Stops
{
    /** @var Cache */
    private $namesCache;

    /** @var Cache */
    private $latLngCache;

    /** @var Cache */
    private $idsCache;

    /** @var UuidFactory */
    private $uuidProvider;

    function __construct(
        Cache $idsCache,
        Cache $namesCache,
        Cache $latLngCache,
        UuidFactory $uuidProvider
    )
    {
        $this->idsCache = $idsCache;
        $this->namesCache = $namesCache;
        $this->latLngCache = $latLngCache;
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
        $stream = $zip->readStream('stops.txt')['stream'];

        $items = [];
        $header = fgetcsv($stream);
        while(($data = fgetcsv($stream)) !== false) {
            $data = array_combine($header, $data);
            $currentId = $data['stop_id'];
            $newId = $this->findInCache($data);
            if ($newId === null) {
                $newId = $this->uuidProvider->uuid4();

                $this->idsCache->save($currentId, $newId);
                $this->saveInNamesCache($data, $newId);
                $this->saveInLatLngCache($data, $newId);

                $data['stop_id'] = $newId;
                $items[] = $data;
            } else {
                $this->idsCache->save($currentId, $newId);
            }
        }
        return $items;
    }

    private function saveInNamesCache(array $stop, $stopId)
    {
        $key = $this->getNamesCacheKey($stop);
        if (is_string($key)) {
            $this->namesCache->save($key, $stopId);
        }
    }

    private function saveInLatLngCache(array $stop, $stopId)
    {
        $key = $this->getLatLngCacheKey($stop);
        if (is_string($key)) {
            $this->latLngCache->save($key, $stopId);
        }
    }

    /**
     * @param array $stop
     * @return string
     */
    private function findInCache(array $stop)
    {
        $id = $this->findInNamesCache($stop);
        if (is_string($id)) {
            return $id;
        }
        return $this->findInLatLngCache($stop);
    }

    /**
     * @param array $stop
     * @return string
     */
    private function findInNamesCache(array $stop)
    {
        $key = $this->getNamesCacheKey($stop);
        if (!is_string($key)) {
            return null;
        }
        return $this->namesCache->load($key);
    }

    private function findInLatLngCache(array $stop)
    {
        $key = $this->getLatLngCacheKey($stop);
        if (!is_string($key)) {
            return null;
        }
        return $this->latLngCache->load($key);
    }

    private function getNamesCacheKey(array $stop)
    {
        $cols = ['stop_name', 'stop_city', 'stop_country', 'stop_admin_level1', 'stop_admin_level2', 'stop_admin_level3'];
        $parts = [];
        foreach ($cols as $col) {
            if (isset($stop[$col]) && $stop[$col] !== null && $stop[$col] !== '') {
                $parts[] = $stop[$col];
            }
        }
        if (count($parts) === 0) {
            return null;
        }
        $key = implode(';', $parts);
        return $key;
    }

    private function getLatLngCacheKey(array $stop)
    {
        if (!is_numeric($stop['stop_lat']) || !is_numeric($stop['stop_lon'])) {
            return null;
        }
        $key =  $stop['stop_lat'] . ',' . $stop['stop_lon'];
        return $key;
    }
}