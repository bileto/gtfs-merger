<?php

namespace GtfsMerger\Console;

class Application extends \Symfony\Component\Console\Application
{
    /** @var string */
    private $build;

    /**
     * @param string $name
     * @param string $version
     * @param string $build
     */
    public function __construct($name, $version, $build)
    {
        parent::__construct($name, $version);
        $this->build = $build;
    }

    /**
     * @override
     */
    public function getLongVersion()
    {
        $version = $this->getVersion();
        if ($this->build !== null && $this->build !== '') {
            $version .= ' (' . $this->build . ')';
        }

        $longVer = sprintf(
            '<info>%s</info> version <comment>%s</comment>',
            $this->getName(),
            $version
        );
        return $longVer;
    }
}