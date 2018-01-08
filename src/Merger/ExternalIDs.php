<?php

namespace GtfsMerger\Merger;

use Nette\ArgumentOutOfRangeException;
use Nette\Caching\Cache;

class ExternalIDs implements MergerInterface
{
    /** @var Cache */
    private $idsCache;

    /** @var Cache */
    private $externalIdsCache;

    function __construct(
        Cache $idsCache,
        Cache $externalIdsCache
    )
    {
        $this->idsCache = $idsCache;
        $this->externalIdsCache = $externalIdsCache;
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
            $currentId = $data['stop_id'];
            $newId = $this->idsCache->load($currentId);
            if ($newId === null) {
                throw new ArgumentOutOfRangeException('Unknown stop id: ' . $currentId);
            }
            $data['stop_id'] = $newId;

            $externalIdData = $this->externalIdsCache->load($newId);

            if ($externalIdData) {
                if ($externalIdData['type'] !== $data['type'] || $externalIdData['id'] !== $data['id']) {
                    throw new ArgumentOutOfRangeException('Different station external IDs: ' . $currentId);
                }
            } else {
                $this->externalIdsCache->save($newId, $data);
                $items[] = $data;
            }
        }
        return $items;
    }
}