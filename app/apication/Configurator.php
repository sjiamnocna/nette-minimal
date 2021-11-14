<?php declare(strict_types=1);

namespace APIcation;

use Nette;
use Nette\Configurator as NetteConfigurator;
use Tracy;


/**
 * Initial system DI container generator.
 */
class Configurator extends NetteConfigurator
{
    /** @var array overload default service list */
	public $defaultExtensions = [
		'cache' => [Nette\Bridges\CacheDI\CacheExtension::class, ['%tempDir%']],
		'constants' => Extensions\ConstantsExtension::class,
		'database' => [Nette\Bridges\DatabaseDI\DatabaseExtension::class, ['%debugMode%']],
		'decorator' => Nette\DI\Extensions\DecoratorExtension::class,
		'di' => [Nette\DI\Extensions\DIExtension::class, ['%debugMode%']],
		'extensions' => Nette\DI\Extensions\ExtensionsExtension::class,
		'http' => [Nette\Bridges\HttpDI\HttpExtension::class, ['%consoleMode%']],
		'inject' => Nette\DI\Extensions\InjectExtension::class,
		'php' => Extensions\PhpExtension::class,
		'search' => [Nette\DI\Extensions\SearchExtension::class, ['%tempDir%/cache/nette.search']],
		'security' => [Nette\Bridges\SecurityDI\SecurityExtension::class, ['%debugMode%']],
		'session' => [Nette\Bridges\HttpDI\SessionExtension::class, ['%debugMode%', '%consoleMode%']],
		'tracy' => [Tracy\Bridges\Nette\TracyExtension::class, ['%debugMode%', '%consoleMode%']],
	];
}