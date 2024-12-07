<?php

namespace Bitrix\Crm\Terminal;

use Bitrix\Main;
use Bitrix\Mobile;

class AuthLink
{
	private const INTENT = 'terminal';
	private const PRESET = 'preset_terminal';

	public static function getIntent(): string
	{
		if (self::isInstallMobileApp())
		{
			return self::INTENT;
		}

		return self::PRESET;
	}

	private static function isInstallMobileApp(): bool
	{
		$userId = Main\Engine\CurrentUser::get()->getId();

		return
			\CUserOptions::GetOption('mobile', 'AndroidLastActivityDate', 0, $userId)
			|| \CUserOptions::GetOption('mobile', 'iOsLastActivityDate', 0, $userId)
		;
	}
}