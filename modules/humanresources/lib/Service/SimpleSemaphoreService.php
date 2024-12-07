<?php

namespace Bitrix\HumanResources\Service;

use Bitrix\HumanResources\Contract\Service\SemaphoreService;

class SimpleSemaphoreService implements SemaphoreService
{
	private static $storage = [];

	public function lock(string $key): bool
	{
		if (isset(self::$storage[$key]))
		{
			return false;
		}
		self::$storage[$key] = true;

		return true;
	}

	public function unlock(string $key): bool
	{
		if (isset(self::$storage[$key]))
		{
			unset(self::$storage[$key]);

			return true;
		}

		return false;
	}

	public function isLocked(string $key): bool
	{
		return isset(self::$storage[$key]);
	}
}