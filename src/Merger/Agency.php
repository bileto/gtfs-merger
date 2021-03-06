<?php

namespace GtfsMerger\Merger;

use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Nette\Caching\Cache;
use Ramsey\Uuid\UuidFactory;

class Agency implements MergerInterface
{
    /** @var Cache */
    private $namesCache;

    /** @var Cache */
    private $idsCache;

    /** @var UuidFactory */
    private $uuidProvider;

    function __construct(Cache $namesCache, Cache $idsCache, UuidFactory $uuidProvider)
    {
        $this->namesCache = $namesCache;
        $this->idsCache = $idsCache;
        $this->uuidProvider = $uuidProvider;
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
            $key = $data['agency_name'];
            $currentId = $data['agency_id'];
            $newId = $this->namesCache->load($key);
            if ($newId === null) {
                $newId = $this->uuidProvider->uuid4()->toString();
                $this->idsCache->save($currentId, $newId);
                $this->namesCache->save($key, $newId);

                $data['agency_id'] = $newId;
                $items[] = $data;
            } else {
                $this->idsCache->save($currentId, $newId);
            }
        }
        return $items;
    }
}