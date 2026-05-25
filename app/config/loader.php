<?php

$loader = new \Phalcon\Autoload\Loader();

/**
 * We're a registering a set of directories taken from the configuration file
 */
$loader->setDirectories(
    [
        $config->application->controllersDir,
        $config->application->modelsDir,
        $config->application->servicesDir,
        $config->application->libraryDir,
    ]
);

$loader->setNamespaces([
    'App\Models' => $config->application->modelsDir,
    'App\Services' => $config->application->servicesDir,
    'App\Controllers' => $config->application->controllersDir,
    'App\Library' => $config->application->libraryDir,
]);

$loader->register();
