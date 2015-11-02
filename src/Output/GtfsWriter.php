<?php

namespace GtfsMerger\Output;

use League\Flysystem\Config;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;

class GtfsWriter
{
    /** @var array */
    private $tmpFiles = [];

    public function append($path, array $items)
    {
        if (count($items) === 0) {
            return;
        }
        if (!isset($this->tmpFiles[$path])) {
            $tmpFile = tmpfile();
            $first = reset($items);
            fputcsv($tmpFile, array_keys($first));
            $this->tmpFiles[$path] = $tmpFile;
        }
        foreach ($items as $item) {
            fputcsv($this->tmpFiles[$path], $item);
        }
    }

    public function save($filename)
    {
        $zip = new ZipArchiveAdapter($filename);
        foreach ($this->tmpFiles as $path => $tmpFile) {
            $zip->writeStream($path, $tmpFile, new Config());
        }
    }

    public function clean()
    {
        foreach ($this->tmpFiles as $path => $tmpFile) {
            fclose($tmpFile);
        }
        $this->tmpFiles = [];
    }
}