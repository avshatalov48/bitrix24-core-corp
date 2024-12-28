<?php

namespace Bitrix\Crm\Service\Logger;

use Psr\Log\NullLogger;
use Bitrix\Main\Diag\Logger;
use Bitrix\Main\Config\Configuration;
use Bitrix\Main\DI\ServiceLocator;

class LoggerFactory
{
	public static function create(string $loggerId): \Psr\Log\LoggerInterface
	{
		$loggerFromSettings = Logger::create('crm.' . $loggerId);
		if ($loggerFromSettings)
		{
			return $loggerFromSettings;
		}
		$configuration = Configuration::getInstance('crm');
		$loggerData = $configuration->get('loggers')[$loggerId] ?? null;
		if (!$loggerData)
		{
			// logger is not defined
			return new NullLogger();
		}
		if ($loggerData instanceof \Closure)
		{
			return $loggerData();
		}

		return self::createFromConfigArray((array)$loggerData);
	}

	private static function createFromConfigArray(array $config): \Psr\Log\LoggerInterface
	{
		if (isset($config['className']))
		{
			$class = $config['className'];

			$args = $config['constructorParams'] ?? [];
			if ($args instanceof \Closure)
			{
				$args = $args();
			}

			$logger = new $class(...array_values($args));
		}
		elseif (isset($config['constructor']))
		{
			$closure = $config['constructor'];
			if ($closure instanceof \Closure)
			{
				$logger = $closure();
			}
		}

		if ($logger instanceof \Bitrix\Main\Diag\Logger)
		{
			if (isset($config['level']))
			{
				$logger->setLevel($config['level']);
			}

			if (isset($config['formatter']))
			{
				$serviceLocator = ServiceLocator::getInstance();
				if ($serviceLocator->has($config['formatter']))
				{
					$logger->setFormatter($serviceLocator->get($config['formatter']));
				}
			}
		}

		if (!$logger)
		{
			$logger = new NullLogger();
		}

		return $logger;
	}
}
