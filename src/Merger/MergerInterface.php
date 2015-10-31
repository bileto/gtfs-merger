<?php


namespace GtfsMerger\Merger;


/**
 * Interface MergerInterface
 *
 * @package GtfsMerger\Merger
 * @author Rudolf Kočičák Dobiáš
 */
interface MergerInterface
{
    /**
     * @param resource $stream
     * @return mixed
     */
    public function merge($stream);
}