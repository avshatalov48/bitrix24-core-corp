<?php

namespace Bitrix\Tasks\Integration\Socialnetwork\Context;

use Bitrix\Main\LoaderException;
use Bitrix\Tasks\Integration\Socialnetwork\Space\SpaceService;
use \Bitrix\Socialnetwork\Collab\CollabFeature;

abstract class Context
{
	private static ?string $spaces = null;
	private static ?string $collab = null;
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

	public static function getCollab(): ?string
	{
		if (!is_null(self::$collab))
		{
			return self::$collab;
		}

		if (CollabFeature::isOn())
		{
			self::$collab = \Bitrix\Socialnetwork\Livefeed\Context\Context::COLLAB;
		}

		return self::$collab;
	}

	public static function getDefault(): string
	{
		return self::$default;
	}
}
