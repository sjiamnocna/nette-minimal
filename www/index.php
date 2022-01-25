<?php declare(strict_types=1);

use APIcation\Application;
use APIcation\Bootstrap;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Bootstrap.php';
require_once __DIR__ . '/../app/apication/CConfigurator.php';
require_once __DIR__ . '/../app/apication/CSecurity.php';

Bootstrap::boot()
	->getByName('Application')
	->run();