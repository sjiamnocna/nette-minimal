<?php declare(strict_types=1);

use APIcation\Bootstrap;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Bootstrap.php';

Bootstrap::boot()
	->getByName('Application')
	->run();