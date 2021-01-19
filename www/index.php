<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/bootstrap.php';

App\Bootstrap::boot()
	->createContainer()
	->getByType(Nette\Application\Application::class)
	->run();