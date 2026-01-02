<?php

namespace Bitrix\Tasks\Integration\Socialnetwork\Context;

use Bitrix\Main\Loader;

abstract class Context
{
	private static ?string $collab = null;
	private static string $default = 'default';

	public static function getCollab(): ?string
	{
		if (self::$collab === null && Loader::includeModule('socialnetwork'))
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
