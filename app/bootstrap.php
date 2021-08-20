<?php declare(strict_types=1);

namespace App;

use Nette\Configurator;


class Bootstrap
{
	public static function boot(): Configurator
	{
		$configurator = new Configurator;
		$appdir = dirname(__DIR__);

		$configurator->setDebugMode(TRUE);
		$configurator->enableTracy(__DIR__ . '/../log');

		$configurator->setTimeZone('Europe/Prague');
		$configurator->setTempDirectory(__DIR__ . '/../temp');

		$configurator->createRobotLoader()
			->addDirectory(__DIR__)
			->register();

		$configurator->addConfig(__DIR__ . '/config/local.neon');
		$configurator->addConfig(__DIR__ . '/config/common.neon');

		return $configurator;
	}
}