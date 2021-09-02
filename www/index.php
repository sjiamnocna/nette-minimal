<?php declare(strict_types=1);

use APIcation\Application;
use APIcation\Bootstrap;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/Bootstrap.php';
require_once __DIR__ . '/../app/overload/Configurator.php';

Bootstrap::boot()
	->createContainer()
	->getByName('Application')
	->run();