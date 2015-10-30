<?php

namespace GtfsMerger\Merger;

use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Nette\Caching\Cache;
use Nette\InvalidStateException;
use Ramsey\Uuid\UuidFactory;

class Routes
{
    /** @var Cache */
    private $agencyIdsCache;

    /** @var Cache */
    private $namesCache;

    /** @var Cache */
    private $idsCache;

    /** @var UuidFactory */
    private $uuidProvider;

    function __construct(
        Cache $agencyIdsCache,
        Cache $namesCache,
        Cache $idsCache,
        UuidFactory $uuidProvider
    )
    {
        $this->agencyIdsCache = $agencyIdsCache;
        $this->namesCache = $namesCache;
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
        $stream = $zip->readStream('routes.txt')['stream'];

        $items = [];
        $header = fgetcsv($stream);
        while(($data = fgetcsv($stream)) !== false) {
            $data = array_combine($header, $data);
            $key = $data['route_short_name'] . ';' . $data['route_long_name'];
            $currentId = $data['route_id'];
            $newId = $this->namesCache->load($key);
            if ($newId === null) {
                $newId = $this->uuidProvider->uuid4();
                $this->idsCache->save($currentId, $newId);
                $this->namesCache->save($key, $newId);

                $data['route_id'] = $newId;
                $data['agency_id'] = $this->getAgencyId($data['agency_id']);
                $items[] = $data;
            } else {
                $this->idsCache->save($currentId, $newId);
            }
        }
        return $items;
    }

    /**
     * @param string $oldAgencyId
     * @return string
     */
    private function getAgencyId($oldAgencyId)
    {
        $newId = $this->agencyIdsCache->load($oldAgencyId);
        if ($newId === null) {
            throw new InvalidStateException('Unknown agency ID: ' . $oldAgencyId);
        }
        return $newId;
    }
}