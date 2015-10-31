<?php

namespace GtfsMerger\Merger;

use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Nette\Caching\Cache;
use Ramsey\Uuid\UuidFactory;

class Calendar implements MergerInterface
{
    /** @var Cache */
    private $idsCache;

    /** @var UuidFactory */
    private $uuidProvider;

    function __construct(
        Cache $idsCache,
        UuidFactory $uuidProvider
    )
    {
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
            $currentId = $data['service_id'];
            $newId = $this->idsCache->load($currentId);
            if ($newId === null) {
                $newId = $this->uuidProvider->uuid4()->toString();

                $this->idsCache->save($currentId, $newId);

                $data['service_id'] = $newId;
                $items[] = $data;
            } else {
                $this->idsCache->save($currentId, $newId);
            }
        }
        return $items;
    }
}