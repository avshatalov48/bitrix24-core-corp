<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;

class Configuration extends Sale\Configuration
{
	private static bool $enabledEntitySynchronization = true;

	public static function isEnabledEntitySynchronization(): bool
	{
		return self::$enabledEntitySynchronization;
	}

	public static function setEnabledEntitySynchronization(bool $enabledEntitySynchronization): void
	{
		self::$enabledEntitySynchronization = $enabledEntitySynchronization;
	}
}
