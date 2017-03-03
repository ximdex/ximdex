#!/usr/bin/env php
<?php

use Symfony\Component\Console\Application;

include_once __DIR__  . '/bootstrap/start.php';

$application = new Application();

// global Ximdex commands
$application->add(new \Ximdex\Commands\ModuleListCommand());
$application->add(new \Ximdex\Commands\ModuleInstallCommand());
$application->add(new \Ximdex\Commands\ModuleUninstallCommand());

// Custom module commands
foreach (ModulesManager::getEnabledModules() as $module) {
    $name = $module[ 'name' ];
    $mManager->instanceModule($name)->addCommands( $application );
}

try {
    $application->run();
} catch (\Error $e){
    echo $e->getMessage();
    echo $e->getTraceAsString();
}