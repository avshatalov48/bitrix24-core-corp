<?php

namespace Bitrix\Crm\Traits;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\DI\ServiceLocator;

trait Singleton
{
	private function __construct()
	{
	}

	private function __clone()
	{
	}

	public function __wakeup()
	{
	}

	public static function getInstance(): static
	{
		$code = Container::getIdentifierByClassName(static::class);
		$serviceLocator = ServiceLocator::getInstance();

		if (!$serviceLocator->has($code))
		{
			$serviceLocator->addInstance($code, new static());
		}

		return $serviceLocator->get($code);
	}
}
