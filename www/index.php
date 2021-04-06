<?php declare(strict_types=1);

define('SITE_ROOT_DIR', __DIR__);
define('ALLOWED_REFERRER', 'localhost');

// do basic checks before doing anything

if((empty($_REQUEST['key'])) || (empty($_SERVER['HTTP_REFERER']) || stripos($_SERVER['HTTP_REFERER'], ALLOWED_REFERRER) === -1)) { 
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