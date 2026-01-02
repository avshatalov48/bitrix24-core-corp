<?php

namespace Bitrix\Crm\RepeatSale\Service\Handler;

use Bitrix\Main\ArgumentException;

enum HandlerType: int
{
	case SystemHandler = 1;
	case ConfigurableHandler = 2;

	public static function fromValue(int $value): self
	{
		if ($value === 1)
		{
			return self::SystemHandler;
		}

		if ($value === 2)
		{
			return self::ConfigurableHandler;
		}

		throw new ArgumentException('Unknown HandlerType value: ' . $value, 'value');
	}
}
