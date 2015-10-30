<?php

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

$app = new Console\Application('GTFS Merger');

$commands = $container->findByType(Console\Command\Command::class);
foreach ($commands as $command) {
    $app->add($container->getService($command));
}

$app->run();