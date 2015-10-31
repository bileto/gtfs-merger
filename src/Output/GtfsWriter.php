<?php

namespace GtfsMerger\Output;

use League\Flysystem\Config;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Class GtfsWriter
 *
 * @package GtfsMerger\Output
 */
class GtfsWriter
{
    /** @var array */
    private $tmpFiles = [];

    /**
     * @param $path
     * @param array $items
     */
    public function append($path, array $items)
    {
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

    /**
     * @param $filename
     * @param ProgressBar $progressBar
     */
    public function save($filename, ProgressBar $progressBar)
    {
        $progressBar->start(count($this->tmpFiles));
        $progressBar->setMessage($filename, 'file');
        $zip = new ZipArchiveAdapter($filename);
        foreach ($this->tmpFiles as $path => $tmpFile) {
            $progressBar->advance();
            $zip->writeStream($path, $tmpFile, new Config());
        }
    }

    /**
     *
     */
    public function clean()
    {
        foreach ($this->tmpFiles as $path => $tmpFile) {
            fclose($tmpFile);
        }
        $this->tmpFiles = [];
    }
}