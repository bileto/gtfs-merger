<?php

namespace GtfsMerger\Merger;

class FeedInfo implements MergerInterface
{

    /**
     * @param resource $stream
     * @return array
     */
    public function merge($stream)
    {
        $items = [];
        $header = fgetcsv($stream);
        while(($data = fgetcsv($stream)) !== false) {
            $items[] = array_combine($header, $data);
        }
        return $items;
    }
}