<?php

namespace Bitrix\Tasks\Integration\Socialnetwork\Context;

use Bitrix\Main\LoaderException;
use Bitrix\Tasks\Integration\Socialnetwork\Space\SpaceService;

abstract class Context
{
	private static ?string $spaces = null;
	private static string $default = 'default';

	/**
	 * @throws LoaderException
	 */
	public static function getSpaces(): ?string
	{
		if (!is_null(self::$spaces))
		{
			return self::$spaces;
		}

		if (SpaceService::isAvailable())
		{
			self::$spaces = \Bitrix\Socialnetwork\Livefeed\Context\Context::SPACES;
		}

		return self::$spaces;
	}

	public static function getDefault(): string
	{
		return self::$default;
	}
}