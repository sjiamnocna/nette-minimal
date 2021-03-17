<?php declare(strict_types=1);

define('PROTO', 'https://');
define('SITE_ROOT_URI', 'example.cz');

// do basic checks before doing anything

if((empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest')
	|| (empty($_SERVER['HTTP_REFERER']) || $_SERVER['HTTP_REFERER'] == PROTO . SITE_ROOT_URI)) { 
	// this is not AJAX call or it came from different site than allowed
	header('HTTP/1.0 403 Forbidden');
	die('403 - Forbidden');
}

// now get required tools and run the application

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/bootstrap.php';

App\Bootstrap::boot()
	->createContainer()
	->getByType(Nette\Application\Application::class)
	->run();