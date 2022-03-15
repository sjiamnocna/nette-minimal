<?php declare(strict_types=1);

namespace APIcation;

use Nette\DI\Container;
use Nette\Configurator;

class Bootstrap
{
	public static function boot(): Container
	{
		$configurator = new Configurator();

		$configurator->setDebugMode('localhost');
		$configurator->enableTracy(__DIR__ . '/../log');

		$configurator->setTimeZone('Europe/Prague');
		$configurator->setTempDirectory(__DIR__ . '/../temp');

		$configurator->createRobotLoader()
			->addDirectory(__DIR__)
			->register();

		$configurator->addConfig(__DIR__ . '/config/local.neon');
		$configurator->addConfig(__DIR__ . '/config/common.neon');

		$container = $configurator->createContainer();

		return $container;
	}
}