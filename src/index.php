<?php

use GtfsMerger\Console\Application;
use Nette\DI;
use Symfony\Component\Console;

require __DIR__ . '/../vendor/autoload.php';

$loader = new DI\ContainerLoader(sys_get_temp_dir(), TRUE);
$class = $loader->load(time(), function(DI\Compiler $compiler) {
    $compiler->addExtension('php', new DI\Extensions\PhpExtension());
    $compiler->loadConfig(__DIR__ . '/config.neon');
});
/** @var DI\Container $container */
$container = new $class();

$configParams = $container->getParameters();
$version = $configParams['version'];
$build = $configParams['build'];
$app = new Application('GTFS Merger', $version, $build);

$commands = $container->findByType(Console\Command\Command::class);
foreach ($commands as $command) {
    $app->add($container->getService($command));
}

$app->run();